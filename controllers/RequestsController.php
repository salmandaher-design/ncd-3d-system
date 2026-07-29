<?php
class RequestsController extends Controller
{
    private PrintRequest $requests;

    public function __construct()
    {
        $this->requests = new PrintRequest();
    }

    /** List + search. Admin sees all; member sees only their own. */
    public function index(): void
    {
        $this->requireLogin();

        $filters = [
            'q'        => trim($_GET['q'] ?? ''),
            'status'   => $_GET['status'] ?? '',
            'priority' => $_GET['priority'] ?? '',
            'team_id'  => $_GET['team_id'] ?? '',
        ];

        if (!Auth::isAdmin()) {
            $filters['user_id'] = Auth::id();
        }

        $teams = Auth::isAdmin() ? (new Team())->all('name ASC') : [];

        $this->view('requests/index', [
            'pageTitle' => 'Print Requests',
            'requests'  => $this->requests->search($filters),
            'filters'   => $filters,
            'teams'     => $teams,
        ]);
    }

    /** Show the create form (members and admins). */
    public function create(): void
    {
        $this->requireLogin();

        // A member must belong to a team to submit.
        if (!Auth::isAdmin() && !Auth::teamId()) {
            Flash::set('warning', 'You are not assigned to a team yet. Please contact the administrator.');
            redirect('dashboard');
        }

        $teams = Auth::isAdmin() ? (new Team())->all('name ASC') : [];

        $this->view('requests/create', [
            'pageTitle' => 'New Print Request',
            'teams'     => $teams,
        ]);
    }

    /** Persist a new request. */
    public function store(): void
    {
        $this->requireLogin();
        $this->requireCsrf();

        $projectName   = trim($_POST['project_name'] ?? '');
        $description   = trim($_POST['description'] ?? '');
        $priority      = $_POST['priority'] ?? 'Medium';
        $color         = trim($_POST['color'] ?? '');
        $transactionNo = trim($_POST['transaction_no'] ?? '');

        // Team: admins may submit on behalf of a team; members use their own.
        if (Auth::isAdmin()) {
            $teamId = (int) ($_POST['team_id'] ?? 0);
        } else {
            $teamId = (int) Auth::teamId();
        }

        $errors = [];
        if ($projectName === '')                       $errors[] = 'Project name is required.';
        if (!in_array($priority, PrintRequest::PRIORITIES, true)) $priority = 'Medium';
        if ($teamId <= 0)                              $errors[] = 'A team must be selected.';

        if ($errors) {
            Flash::set('error', implode(' ', $errors));
            redirect('requests/create');
        }

        try {
            $imagePath = Upload::image($_FILES['image'] ?? []);
            $files     = Upload::files($_FILES['files'] ?? []);
        } catch (RuntimeException $e) {
            Flash::set('error', $e->getMessage());
            redirect('requests/create');
        }

        $id = $this->requests->create([
            'user_id'        => Auth::id(),
            'team_id'        => $teamId,
            'project_name'   => $projectName,
            'description'    => $description,
            'priority'       => $priority,
            'color'          => $color,
            'transaction_no' => $transactionNo,
            'image_path'     => $imagePath,
        ]);

        // Per-file "number of prints required" (quantities[] matches files[] by position).
        $quantities = $_POST['quantities'] ?? [];
        $rf = new RequestFile();
        foreach ($files as $f) {
            $idx = $f['index'] ?? null;
            $f['quantity'] = ($idx !== null && isset($quantities[$idx])) ? (int) $quantities[$idx] : 1;
            $rf->add($id, $f);
        }

        ActivityLog::record('request_create', 'Submitted request #' . $id . ' — ' . $projectName);
        Flash::set('success', 'Your print request has been submitted.');
        redirect('requests/show/' . $id);
    }

    /** Request detail page. */
    public function show(string $id = '0'): void
    {
        $this->requireLogin();
        $id = (int) $id;
        $request = $this->requests->findFull($id);

        if (!$request) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }
        // Members may only view their own requests.
        if (!Auth::isAdmin() && (int) $request['user_id'] !== Auth::id()) {
            http_response_code(403);
            $this->view('errors/403');
            return;
        }

        $files = (new RequestFile())->forRequest($id);
        $printers = Auth::isAdmin() ? (new Printer())->allSorted() : [];
        $filament = Auth::isAdmin() ? (new Filament())->options() : [];
        $comments = (new RequestComment())->forRequest($id);

        $this->view('requests/show', [
            'pageTitle' => 'Request #' . $id,
            'request'   => $request,
            'files'     => $files,
            'printers'  => $printers,
            'filament'  => $filament,
            'comments'  => $comments,
        ]);
    }

    /** Post a comment on a request (admin on any, member on their own). */
    public function comment(string $id = '0'): void
    {
        $this->requireLogin();
        $this->requireCsrf();
        $id = (int) $id;

        $request = $this->requests->find($id);
        if (!$request) {
            redirect('requests');
        }
        // Members may only comment on their own requests.
        if (!Auth::isAdmin() && (int) $request['user_id'] !== Auth::id()) {
            http_response_code(403);
            $this->view('errors/403');
            return;
        }

        $body = trim($_POST['body'] ?? '');
        if ($body === '') {
            Flash::set('error', 'Please write a message before sending.');
            redirect('requests/show/' . $id);
        }
        if (mb_strlen($body) > 2000) {
            $body = mb_substr($body, 0, 2000);
        }

        (new RequestComment())->add($id, Auth::id(), $body);
        ActivityLog::record('request_comment', 'Commented on request #' . $id);
        Flash::set('success', 'Comment added.');
        // Jump straight to the thread.
        redirect('requests/show/' . $id . '#comments');
    }

    /** Save admin-only technical fields (no status change). */
    public function update(string $id = '0'): void
    {
        $this->requireAdmin();
        $this->requireCsrf();
        $id = (int) $id;

        if (!$this->requests->find($id)) {
            redirect('requests');
        }

        $this->requests->updateNotes(
            $id,
            trim($_POST['admin_notes'] ?? ''),
            trim($_POST['estimated_weight'] ?? ''),
            trim($_POST['transaction_no'] ?? '')
        );

        ActivityLog::record('request_update', 'Updated notes/estimate for request #' . $id);
        Flash::set('success', 'Request details saved.');
        redirect('requests/show/' . $id);
    }

    // ---------- Status transitions (admin) ----------

    /**
     * Approve (accept) a request. The admin chooses the filament spool and
     * enters the weight to use — this is deducted from the spool immediately.
     */
    public function approve(string $id = '0'): void
    {
        $this->requireAdmin();
        $this->requireCsrf();
        $id = (int) $id;
        $r = $this->requests->find($id);
        if (!$r) {
            redirect('requests');
        }

        $filamentId = (int) ($_POST['filament_id'] ?? 0);
        $weight     = trim($_POST['weight'] ?? '');

        $spool = $filamentId > 0 ? (new Filament())->find($filamentId) : null;
        if (!$spool) {
            Flash::set('error', 'Please choose a filament spool to approve the request.');
            redirect('requests/show/' . $id);
        }
        if ($weight === '' || !is_numeric($weight) || (float) $weight <= 0) {
            Flash::set('error', 'Please enter the filament weight (grams) to use.');
            redirect('requests/show/' . $id);
        }
        $weight = (float) $weight;

        // Commit: record on the request and subtract from the spool total.
        $this->requests->approveWithFilament($id, $filamentId, $weight);
        (new Filament())->deduct($filamentId, $weight);

        ActivityLog::record('request_approve',
            'Approved request #' . $id . ' — ' . $weight . ' g of ' . $spool['color'] . ' deducted');
        Flash::set('success', 'Request approved. ' . $weight . ' g deducted from ' . $spool['color'] . '.');
        redirect('requests/show/' . $id);
    }

    /** Reject a request, recording the reason(s). Refunds filament if already deducted. */
    public function reject(string $id = '0'): void
    {
        $this->requireAdmin();
        $this->requireCsrf();
        $id = (int) $id;
        $r = $this->requests->findFull($id);
        if ($r) {
            $reason = trim($_POST['reason'] ?? '');
            $this->freePrinterIfAny($r);
            $this->refundIfDeducted($r);
            $this->requests->setStatus($id, 'Rejected');
            // Store the rejection reason in the admin notes so it shows on the printout.
            $this->requests->updateNotes($id, $reason !== '' ? $reason : ($r['admin_notes'] ?? ''),
                $r['estimated_weight'] ?? '', $r['transaction_no'] ?? '');
            ActivityLog::record('request_reject', 'Rejected request #' . $id);
            Flash::set('warning', 'Request rejected.');
        }
        redirect('requests/show/' . $id);
    }

    /** Approved -> Printing. Requires a printer choice (filament already chosen at approval). */
    public function start(string $id = '0'): void
    {
        $this->requireAdmin();
        $this->requireCsrf();
        $id = (int) $id;
        $r = $this->requests->findFull($id);
        if (!$r) {
            redirect('requests');
        }

        $printerId = (int) ($_POST['printer_id'] ?? 0);
        if ($printerId <= 0) {
            Flash::set('error', 'Please choose a printer before starting.');
            redirect('requests/show/' . $id);
        }

        $this->requests->startPrinting($id, $printerId);

        (new Printer())->setBusy(
            $printerId,
            $r['project_name'],
            $r['team_name'] ?? '',
            Auth::name()
        );

        ActivityLog::record('request_print', 'Started printing request #' . $id);
        Flash::set('success', 'Printing started.');
        redirect('requests/show/' . $id);
    }

    /** Printing -> Completed. (Filament was already deducted at approval.) */
    public function complete(string $id = '0'): void
    {
        $this->requireAdmin();
        $this->requireCsrf();
        $id = (int) $id;
        $r = $this->requests->findFull($id);
        if (!$r) {
            redirect('requests');
        }

        $this->requests->setStatus($id, 'Completed');
        $this->freePrinterIfAny($r);

        ActivityLog::record('request_complete', 'Completed request #' . $id);
        Flash::set('success', 'Request marked as completed.');
        redirect('requests/show/' . $id);
    }

    /** Cancel: admin any request, or member their own while Submitted. Refunds filament. */
    public function cancel(string $id = '0'): void
    {
        $this->requireLogin();
        $this->requireCsrf();
        $id = (int) $id;
        $r = $this->requests->findFull($id);
        if (!$r) {
            redirect('requests');
        }

        $isOwner = (int) $r['user_id'] === Auth::id();
        $memberMayCancel = $isOwner && $r['status'] === 'Submitted';

        if (!Auth::isAdmin() && !$memberMayCancel) {
            http_response_code(403);
            $this->view('errors/403');
            return;
        }

        $this->freePrinterIfAny($r);
        $this->refundIfDeducted($r);
        $this->requests->setStatus($id, 'Cancelled');
        ActivityLog::record('request_cancel', 'Cancelled request #' . $id);
        Flash::set('warning', 'Request cancelled.');
        redirect('requests/show/' . $id);
    }

    public function delete(string $id = '0'): void
    {
        $this->requireAdmin();
        $this->requireCsrf();
        $id = (int) $id;
        $r = $this->requests->findFull($id);
        if ($r) {
            $this->freePrinterIfAny($r);
            $this->refundIfDeducted($r);
            (new RequestFile())->deleteForRequest($id);
            Upload::remove($r['image_path'] ?? null);
            $this->requests->delete($id);
            ActivityLog::record('request_delete', 'Deleted request #' . $id);
            Flash::set('success', 'Request deleted.');
        }
        redirect('requests');
    }

    /**
     * Printable, official request form (Arabic letterhead).
     * Only available once the admin has responded (status is no longer "Submitted").
     */
    public function printForm(string $id = '0'): void
    {
        $this->requireLogin();
        $id = (int) $id;
        $request = $this->requests->findFull($id);

        if (!$request) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }
        // Members may only print their own requests.
        if (!Auth::isAdmin() && (int) $request['user_id'] !== Auth::id()) {
            http_response_code(403);
            $this->view('errors/403');
            return;
        }
        // Cannot print until the admin has responded (even a rejection is fine).
        if ($request['status'] === 'Submitted') {
            Flash::set('warning', 'This request cannot be printed until the administrator has responded to it.');
            redirect('requests/show/' . $id);
        }

        $files = (new RequestFile())->forRequest($id);

        // Standalone document — no app layout.
        $this->view('requests/print', [
            'request' => $request,
            'files'   => $files,
        ], null);
    }

    /**
     * Print several requests as one document — one A4 page per request.
     * Members get their own requests; admins get all (honouring the list filters).
     * Requests still awaiting a response ("Submitted") are skipped.
     */
    public function printAll(): void
    {
        $this->requireLogin();

        $filters = [
            'q'        => trim($_GET['q'] ?? ''),
            'status'   => $_GET['status'] ?? '',
            'priority' => $_GET['priority'] ?? '',
            'team_id'  => $_GET['team_id'] ?? '',
            'job_id'   => $_GET['job_id'] ?? '',
        ];
        // Members print their whole team's requests. If somehow a member has no
        // team, fall back to their own requests (never expose everyone's).
        if (!Auth::isAdmin()) {
            if (Auth::teamId()) {
                $filters['team_id'] = Auth::teamId();
            } else {
                $filters['user_id'] = Auth::id();
            }
        }

        $rf = new RequestFile();
        $sheets = [];
        $skipped = 0;

        foreach ($this->requests->search($filters) as $r) {
            if ($r['status'] === 'Submitted') {   // not answered by the admin yet
                $skipped++;
                continue;
            }
            $sheets[] = ['request' => $r, 'files' => $rf->forRequest((int) $r['id'])];
        }

        $this->view('requests/print-all', [
            'sheets'  => $sheets,
            'skipped' => $skipped,
        ], null);
    }

    /** Stream a request file as a download (with auth checks). */
    public function download(string $fileId = '0'): void
    {
        $this->requireLogin();
        $fileId = (int) $fileId;

        $rf = new RequestFile();
        $file = $rf->find($fileId);
        if (!$file) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $request = $this->requests->find((int) $file['request_id']);
        if (!$request) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }
        if (!Auth::isAdmin() && (int) $request['user_id'] !== Auth::id()) {
            http_response_code(403);
            $this->view('errors/403');
            return;
        }

        $path = UPLOAD_DIR . '/' . str_replace('uploads/', '', $file['file_path']);
        if (!is_file($path)) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file['file_name']) . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-store');
        readfile($path);
        exit;
    }

    // ---------- Helpers ----------

    /** Return committed filament to its spool if it was deducted at approval. */
    private function refundIfDeducted(array $request): void
    {
        if (!empty($request['filament_deducted']) && !empty($request['filament_id']) && $request['actual_weight'] !== null) {
            (new Filament())->refund((int) $request['filament_id'], (float) $request['actual_weight']);
            $this->requests->clearDeducted((int) $request['id']);
        }
    }

    private function freePrinterIfAny(array $request): void
    {
        if (!empty($request['printer_id']) && ($request['status'] ?? '') === 'Printing') {
            (new Printer())->setIdle((int) $request['printer_id']);
        }
    }
}
