<?php
class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();
        if (Auth::isAdmin()) {
            $this->admin();
        } else {
            $this->member();
        }
    }

    private function admin(): void
    {
        $req = new PrintRequest();
        $fil = new Filament();
        $prn = new Printer();

        $stats = [
            'total'     => $req->countAll(),
            'pending'   => $req->countPending(),
            'printing'  => $req->countByStatus('Printing'),
            'completed' => $req->countByStatus('Completed'),
            'cancelled' => $req->countByStatus('Cancelled'),
        ];

        $this->view('dashboard/admin', [
            'pageTitle'        => 'Dashboard',
            'banner'           => (new News())->latest(),
            'stats'            => $stats,
            'recent'           => $req->recent(6),
            'waiting'          => $req->byStatus('Submitted'),
            'printing'         => $req->byStatus('Printing'),
            'lowFilament'      => $fil->lowStock(),
            'busyPrinters'     => $prn->busy(),
            'requestsByMonth'  => $req->requestsByMonth(6),
            'filamentByMonth'  => $req->filamentByMonth(6),
        ]);
    }

    private function member(): void
    {
        $req = new PrintRequest();
        $mine = $req->forUser(Auth::id());

        $counts = ['total' => count($mine), 'active' => 0, 'completed' => 0];
        $highCount = 0; $nightOwl = false; $colors = [];
        foreach ($mine as $r) {
            if (in_array($r['status'], ['Submitted', 'Approved', 'Printing'], true)) {
                $counts['active']++;
            } elseif ($r['status'] === 'Completed') {
                $counts['completed']++;
            }
            if ($r['priority'] === 'High') $highCount++;
            $h = (int) date('G', strtotime($r['created_at']));
            if ($h >= 0 && $h < 5) $nightOwl = true;
            if (!empty($r['color'])) $colors[strtolower($r['color'])] = true;
        }
        $failCount = (new PrintFail())->countForUser(Auth::id());

        // Silly achievement badges (earned flag computed from the member's own data).
        $achievements = [
            ['em' => '🎉', 'name' => 'First Print',      'desc' => 'Submitted a request',        'earned' => $counts['total'] >= 1],
            ['em' => '✅', 'name' => 'Finisher',         'desc' => 'Completed a print',          'earned' => $counts['completed'] >= 1],
            ['em' => '🌙', 'name' => 'Night Owl',        'desc' => 'Submitted after midnight',    'earned' => $nightOwl],
            ['em' => '😎', 'name' => 'The Optimist',     'desc' => '5+ "High" priority requests', 'earned' => $highCount >= 5],
            ['em' => '🎨', 'name' => 'Rainbow',          'desc' => 'Used 3+ different colors',     'earned' => count($colors) >= 3],
            ['em' => '🏭', 'name' => 'Serial Requester', 'desc' => '10+ requests submitted',      'earned' => $counts['total'] >= 10],
            ['em' => '🍝', 'name' => 'Spaghetti Chef',   'desc' => '3+ fails on the wall',        'earned' => $failCount >= 3],
        ];

        $this->view('dashboard/member', [
            'pageTitle'    => 'My Dashboard',
            'banner'       => (new News())->latest(),
            'requests'     => $mine,
            'counts'       => $counts,
            'achievements' => $achievements,
        ]);
    }
}
