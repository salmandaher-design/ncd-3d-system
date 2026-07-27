<?php
class ActivityController extends Controller
{
    public function index(): void
    {
        $this->requireAdmin();
        $this->view('activity/index', [
            'pageTitle' => 'Activity Log',
            'logs'      => (new ActivityLog())->recent(60),
        ]);
    }
}
