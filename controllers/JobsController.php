<?php
/**
 * Print jobs — merging several requests onto one physical plate.
 *
 * Managing jobs is an administrator task (it decides printer + filament),
 * but members can see which job their own request belongs to.
 */
class JobsController extends Controller
{
    private PrintJob $jobs;
    private PrintRequest $requests;

    public function __construct()
    {
        $this->jobs = new PrintJob();
        $this->requests = new PrintRequest();
    }

    /** List every plate. */
    public function index(): void
    {
        $this->requireAdmin();
        $this->view('jobs/index', [
            'pageTitle' => 'Print Jobs',
            'jobs'      => $this->jobs->allWithCounts(),
        ]);
    }

    /** One plate: its requests, combined weight and workflow. */
    public function show(string $id = '0'): void
    {
        $this->requireAdmin();
        $id  = (int) $id;
        $job = $this->jobs->findFull($id);
        if (!$job) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $this->view('jobs/show', [
            'pageTitle'  => 'Job #' . $id,
            'job'        => $job,
            'requests'   => $this->jobs->requests($id),
            'byRequester'=> $this->jobs->requestsByRequester($id),
            'candidates' => $this->requests->mergeable(),
            'printers'   => (new Printer())->allSorted(),
            'filament'   => (new Filament())->options(),
            'estimated'  => $this->jobs->estimatedTotal($id),
        ]);
    }

    /**
     * Merge the selected requests into a plate — either a brand new one or an
     * existing open job. Called from the requests list or a request page.
     */
    public function merge(): void
    {
        $this->requireAdmin();
        $this->requireCsrf();

        $ids = array_values(array_unique(array_map('intval', (array) ($_POST['request_ids'] ?? []))));
        $ids = array_filter($ids, fn($i) => $i > 0);
        if (!$ids) {
            Flash::set('error', 'Select at least one request to merge.');
            redirect('requests');
        }

        $targetJobId = (int) ($_POST['job_id'] ?? 0);

        // Only requests that are still open and free can join a plate.
        $usable = [];
        foreach ($ids as $rid) {
            $r = $this->requests->find($rid);
            if (!$r) continue;
            if (!in_array($r['status'], ['Submitted', 'Approved'], true)) continue;
            if (!empty($r['job_id']) && (int) $r['job_id'] !== $targetJobId) continue;
            $usable[] = $r;
        }
        if (!$usable) {
            Flash::set('error', 'None of the selected requests can be merged (they must be Submitted or Approved and not already in another job).');
            redirect('requests');
        }

        if ($targetJobId > 0) {
            $job = $this->jobs->findFull($targetJobId);
            if (!$job || !in_array($job['status'], ['Planned', 'Approved'], true)) {
                Flash::set('error', 'That print job can no longer take new requests.');
                redirect('requests');
            }
        } else {
            $title = trim($_POST['title'] ?? '');
            if ($title === '') {
                $title = 'Plate ' . date('Y-m-d H:i');
            }
            $targetJobId = $this->jobs->create($title, Auth::id(), trim($_POST['notes'] ?? '') ?: null);
            $job = $this->jobs->findFull($targetJobId);
        }

        // Any filament already committed per-request goes back; the plate owns it now.
        $fil = new Filament();
        foreach ($usable as $r) {
            if (!empty($r['filament_deducted']) && !empty($r['filament_id']) && $r['actual_weight'] !== null) {
                $fil->refund((int) $r['filament_id'], (float) $r['actual_weight']);
                $this->requests->clearDeducted((int) $r['id']);
            }
            $this->jobs->attach($targetJobId, (int) $r['id']);
        }
        // Keep member-facing statuses consistent with the plate.
        $this->jobs->syncRequestStatus($targetJobId, $job['status']);

        ActivityLog::record('job_merge',
            'Merged ' . count($usable) . ' request(s) into print job #' . $targetJobId);

        // Tell the admin how many WhatsApp messages this saves.
        $people = count($this->jobs->requestsByRequester($targetJobId));
        Flash::set('success',
            count($usable) . ' request(s) merged. Use "Notify requesters" to send '
            . $people . ' WhatsApp message' . ($people === 1 ? '' : 's') . ' — one per member.');
        redirect('jobs/show/' . $targetJobId);
    }

    /**
     * Send the plate's update to every requester through the WhatsApp API —
     * one message per member, covering all of their parts.
     * Falls back to the click-to-chat links when the API is not usable.
     */
    public function notifyAll(string $id = '0'): void
    {
        $this->requireAdmin();
        $this->requireCsrf();
        $id = (int) $id;

        $job = $this->jobs->findFull($id);
        if (!$job) {
            redirect('jobs');
        }
        if (!WhatsAppApi::isConfigured()) {
            Flash::set('warning', 'Automatic sending is unavailable — ' . WhatsAppApi::unavailableReason()
                . ' Use the green Send buttons instead.');
            redirect('jobs/show/' . $id);
        }

        $groups = $this->jobs->requestsByRequester($id);
        $sent = 0; $failed = []; $skipped = 0;

        foreach ($groups as $g) {
            if (empty($g['phone'])) {
                $skipped++;
                continue;
            }
            $result = WhatsAppApi::send($g['phone'], $this->notifyText($job, $g));
            if ($result['ok']) {
                $sent++;
            } else {
                $failed[] = $g['name'] . ' (' . $result['error'] . ')';
            }
        }

        ActivityLog::record('job_notify',
            'WhatsApp: sent ' . $sent . ' of ' . count($groups) . ' for job #' . $id);

        if ($sent > 0 && !$failed) {
            Flash::set('success', 'Sent ' . $sent . ' WhatsApp message' . ($sent === 1 ? '' : 's') . '.'
                . ($skipped ? ' ' . $skipped . ' member(s) have no number on file.' : ''));
        } elseif ($sent > 0) {
            Flash::set('warning', 'Sent ' . $sent . ', failed ' . count($failed) . ': ' . implode('; ', $failed));
        } else {
            Flash::set('error', 'Nothing was sent. ' . ($failed ? implode('; ', $failed)
                : 'No member has a WhatsApp number on file.') . ' You can still use the green Send buttons.');
        }
        redirect('jobs/show/' . $id);
    }

    /** The same grouped Arabic text the click-to-chat links use. */
    private function notifyText(array $job, array $group): string
    {
        $statusLine = [
            'Planned'   => 'تم دمج طلباتكم التالية في لوحة طباعة واحدة، وسيتم تنفيذها معاً',
            'Approved'  => 'تمت الموافقة على طلباتكم التالية وتم دمجها في لوحة طباعة واحدة',
            'Printing'  => 'طلباتكم التالية قيد الطباعة الآن ضمن لوحة واحدة',
            'Completed' => 'تم إنجاز طلباتكم التالية، ويمكنكم استلامها',
            'Cancelled' => 'نعتذر، تم إلغاء لوحة الطباعة التي تضم طلباتكم التالية',
        ][$job['status']] ?? 'تحديث بخصوص طلباتكم التالية';

        $lines = ['مرحباً ' . $group['name'] . '،', $statusLine . ':', ''];
        foreach ($group['requests'] as $r) {
            $line = '• ' . $r['project_name'];
            if (($r['transaction_no'] ?? '') !== '') {
                $line .= ' (' . $r['transaction_no'] . ')';
            }
            $lines[] = $line;
        }
        $lines[] = '';
        $lines[] = 'اسم اللوحة: ' . $job['title'];
        if (!empty($job['filament_color'])) {
            $lines[] = 'الفيلامنت: ' . $job['filament_color'];
        }
        $lines[] = 'لمتابعة طلباتكم: ' . full_url('requests');
        $lines[] = '— ' . APP_FULL_NAME . ' · مخبر الروبوت والذكاء الصنعي';
        return implode("\n", $lines);
    }

    /** Remove one request from a plate (only while it is still being planned). */
    public function remove(string $jobId = '0', string $requestId = '0'): void
    {
        $this->requireAdmin();
        $this->requireCsrf();
        $jobId = (int) $jobId;
        $requestId = (int) $requestId;

        $job = $this->jobs->findFull($jobId);
        if (!$job) {
            redirect('jobs');
        }
        if ($job['status'] !== 'Planned') {
            Flash::set('error', 'Requests can only be removed while the job is still Planned. Cancel the job first.');
            redirect('jobs/show/' . $jobId);
        }

        $this->jobs->detach($requestId);
        $this->requests->setStatus($requestId, 'Submitted');
        ActivityLog::record('job_remove', 'Removed request #' . $requestId . ' from job #' . $jobId);
        Flash::set('success', 'Request removed from the plate.');
        redirect('jobs/show/' . $jobId);
    }

    /** Save the plate's title / notes. */
    public function update(string $id = '0'): void
    {
        $this->requireAdmin();
        $this->requireCsrf();
        $id = (int) $id;
        if (!$this->jobs->find($id)) {
            redirect('jobs');
        }
        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            Flash::set('error', 'The job needs a title.');
            redirect('jobs/show/' . $id);
        }
        $this->jobs->updateDetails($id, $title, trim($_POST['notes'] ?? '') ?: null);
        Flash::set('success', 'Job details saved.');
        redirect('jobs/show/' . $id);
    }

    /** Approve the whole plate: pick filament + total weight, deducted ONCE. */
    public function approve(string $id = '0'): void
    {
        $this->requireAdmin();
        $this->requireCsrf();
        $id = (int) $id;

        $job = $this->jobs->findFull($id);
        if (!$job) redirect('jobs');
        if ($job['status'] !== 'Planned') {
            Flash::set('error', 'Only a Planned job can be approved.');
            redirect('jobs/show/' . $id);
        }
        if ((int) $job['request_count'] === 0) {
            Flash::set('error', 'Add at least one request to the plate before approving it.');
            redirect('jobs/show/' . $id);
        }

        $filamentId = (int) ($_POST['filament_id'] ?? 0);
        $weight     = trim($_POST['total_weight'] ?? '');
        $spool = $filamentId > 0 ? (new Filament())->find($filamentId) : null;

        if (!$spool) {
            Flash::set('error', 'Choose the filament spool for this plate.');
            redirect('jobs/show/' . $id);
        }
        if ($weight === '' || !is_numeric($weight) || (float) $weight <= 0) {
            Flash::set('error', 'Enter the total filament weight for the plate (grams).');
            redirect('jobs/show/' . $id);
        }
        $weight = (float) $weight;

        $this->jobs->approve($id, $filamentId, $weight);
        (new Filament())->deduct($filamentId, $weight);
        $this->jobs->syncRequestStatus($id, 'Approved');

        ActivityLog::record('job_approve',
            'Approved print job #' . $id . ' — ' . $weight . ' g of ' . $spool['color'] . ' deducted');
        Flash::set('success', 'Plate approved. ' . $weight . ' g deducted from ' . $spool['color'] . '.');
        redirect('jobs/show/' . $id);
    }

    /** Approved -> Printing: assign a printer for the whole plate. */
    public function start(string $id = '0'): void
    {
        $this->requireAdmin();
        $this->requireCsrf();
        $id = (int) $id;

        $job = $this->jobs->findFull($id);
        if (!$job) redirect('jobs');
        if ($job['status'] !== 'Approved') {
            Flash::set('error', 'Only an Approved job can start printing.');
            redirect('jobs/show/' . $id);
        }

        $printerId = (int) ($_POST['printer_id'] ?? 0);
        if ($printerId <= 0) {
            Flash::set('error', 'Choose a printer for this plate.');
            redirect('jobs/show/' . $id);
        }

        $this->jobs->startPrinting($id, $printerId);
        $this->jobs->syncRequestStatus($id, 'Printing');

        $reqs  = $this->jobs->requests($id);
        $teams = array_unique(array_filter(array_column($reqs, 'team_name')));
        (new Printer())->setBusy(
            $printerId,
            $job['title'] . ' (' . count($reqs) . ' parts)',
            implode(', ', $teams),
            Auth::name()
        );

        ActivityLog::record('job_print', 'Started printing job #' . $id);
        Flash::set('success', 'Printing started for the whole plate.');
        redirect('jobs/show/' . $id);
    }

    /** Printing -> Completed. */
    public function complete(string $id = '0'): void
    {
        $this->requireAdmin();
        $this->requireCsrf();
        $id = (int) $id;

        $job = $this->jobs->findFull($id);
        if (!$job) redirect('jobs');
        if ($job['status'] !== 'Printing') {
            Flash::set('error', 'Only a job that is printing can be completed.');
            redirect('jobs/show/' . $id);
        }

        $this->jobs->setStatus($id, 'Completed');
        $this->jobs->syncRequestStatus($id, 'Completed');
        $this->freePrinter($job);

        ActivityLog::record('job_complete', 'Completed print job #' . $id);
        Flash::set('success', 'Plate completed — every request on it is now Completed.');
        redirect('jobs/show/' . $id);
    }

    /** Cancel the plate and give the filament back. */
    public function cancel(string $id = '0'): void
    {
        $this->requireAdmin();
        $this->requireCsrf();
        $id = (int) $id;

        $job = $this->jobs->findFull($id);
        if (!$job) redirect('jobs');
        if (in_array($job['status'], ['Completed', 'Cancelled'], true)) {
            Flash::set('error', 'This job is already finished.');
            redirect('jobs/show/' . $id);
        }

        $this->refundIfDeducted($job);
        $this->freePrinter($job);
        $this->jobs->setStatus($id, 'Cancelled');
        $this->jobs->syncRequestStatus($id, 'Cancelled');

        ActivityLog::record('job_cancel', 'Cancelled print job #' . $id);
        Flash::set('warning', 'Plate cancelled and filament returned.');
        redirect('jobs/show/' . $id);
    }

    /**
     * Delete the plate. Requests are released back to Submitted rather than
     * deleted — the plate is only a grouping.
     */
    public function delete(string $id = '0'): void
    {
        $this->requireAdmin();
        $this->requireCsrf();
        $id = (int) $id;

        $job = $this->jobs->findFull($id);
        if ($job) {
            $this->refundIfDeducted($job);
            $this->freePrinter($job);
            // Release the requests so they can be planned again.
            if (!in_array($job['status'], ['Completed', 'Cancelled'], true)) {
                $this->jobs->syncRequestStatus($id, 'Planned');
            }
            $this->jobs->detachAll($id);
            $this->jobs->delete($id);
            ActivityLog::record('job_delete', 'Deleted print job #' . $id);
            Flash::set('success', 'Print job deleted. Its requests were released.');
        }
        redirect('jobs');
    }

    // ---------- helpers ----------

    private function refundIfDeducted(array $job): void
    {
        if (!empty($job['filament_deducted']) && !empty($job['filament_id']) && $job['total_weight'] !== null) {
            (new Filament())->refund((int) $job['filament_id'], (float) $job['total_weight']);
            $this->jobs->clearDeducted((int) $job['id']);
        }
    }

    private function freePrinter(array $job): void
    {
        if (!empty($job['printer_id']) && $job['status'] === 'Printing') {
            (new Printer())->setIdle((int) $job['printer_id']);
        }
    }
}
