<?php
class PrintersController extends Controller
{
    private Printer $printers;

    public function __construct()
    {
        $this->printers = new Printer();
    }

    public function index(): void
    {
        $this->requireLogin();
        $this->view('printers/index', [
            'pageTitle' => 'Printers',
            'printers'  => $this->printers->allSorted(),
        ]);
    }

    /** Rename a printer or manually free it (admin). */
    public function save(): void
    {
        $this->requireAdmin();
        $this->requireCsrf();

        $id   = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if ($id > 0 && $name !== '') {
            $this->printers->rename($id, $name);
            ActivityLog::record('printer_update', 'Renamed printer #' . $id . ' to "' . $name . '"');
            Flash::set('success', 'Printer updated.');
        }
        redirect('printers');
    }

    /** Manually set a printer back to idle (admin). */
    public function free(string $id = '0'): void
    {
        $this->requireAdmin();
        $this->requireCsrf();
        $id = (int) $id;
        if ($this->printers->find($id)) {
            $this->printers->setIdle($id);
            ActivityLog::record('printer_free', 'Freed printer #' . $id);
            Flash::set('success', 'Printer set to idle.');
        }
        redirect('printers');
    }
}
