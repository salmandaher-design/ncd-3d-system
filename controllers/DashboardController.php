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
        foreach ($mine as $r) {
            if (in_array($r['status'], ['Submitted', 'Approved', 'Printing'], true)) {
                $counts['active']++;
            } elseif ($r['status'] === 'Completed') {
                $counts['completed']++;
            }
        }

        $this->view('dashboard/member', [
            'pageTitle' => 'My Dashboard',
            'banner'    => (new News())->latest(),
            'requests'  => $mine,
            'counts'    => $counts,
        ]);
    }
}
