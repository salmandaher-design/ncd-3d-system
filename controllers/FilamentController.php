<?php
class FilamentController extends Controller
{
    private Filament $filament;

    public function __construct()
    {
        $this->filament = new Filament();
    }

    public function index(): void
    {
        $this->requireAdmin();
        $this->view('filament/index', [
            'pageTitle' => 'Filament Inventory',
            'spools'    => $this->filament->allSorted(),
        ]);
    }

    public function save(): void
    {
        $this->requireAdmin();
        $this->requireCsrf();

        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            'color'            => trim($_POST['color'] ?? ''),
            'material'         => trim($_POST['material'] ?? ''),
            'remaining_weight' => is_numeric($_POST['remaining_weight'] ?? null) ? (float) $_POST['remaining_weight'] : 0,
            'spools'           => max(0, (int) ($_POST['spools'] ?? 1)),
            'location'         => trim($_POST['location'] ?? ''),
            'notes'            => trim($_POST['notes'] ?? ''),
        ];

        if ($data['color'] === '') {
            Flash::set('error', 'Filament color/name is required.');
            redirect('filament');
        }

        if ($id > 0) {
            $this->filament->update($id, $data);
            ActivityLog::record('filament_update', 'Updated filament "' . $data['color'] . '"');
            Flash::set('success', 'Filament updated.');
        } else {
            $this->filament->create($data);
            ActivityLog::record('filament_create', 'Added filament "' . $data['color'] . '"');
            Flash::set('success', 'Filament added.');
        }
        redirect('filament');
    }

    public function delete(string $id = '0'): void
    {
        $this->requireAdmin();
        $this->requireCsrf();
        $id = (int) $id;
        if ($this->filament->find($id)) {
            $this->filament->delete($id);
            ActivityLog::record('filament_delete', 'Deleted filament #' . $id);
            Flash::set('success', 'Filament removed.');
        }
        redirect('filament');
    }
}
