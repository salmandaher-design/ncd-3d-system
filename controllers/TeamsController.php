<?php
class TeamsController extends Controller
{
    private Team $teams;

    public function __construct()
    {
        $this->teams = new Team();
    }

    public function index(): void
    {
        $this->requireAdmin();
        $this->view('teams/index', [
            'pageTitle' => 'Teams',
            'teams'     => $this->teams->allWithCounts(),
        ]);
    }

    /** Create or update (id present = update). */
    public function save(): void
    {
        $this->requireAdmin();
        $this->requireCsrf();

        $id   = (int) ($_POST['id'] ?? 0);
        $data = [
            'name'        => trim($_POST['name'] ?? ''),
            'competition' => trim($_POST['competition'] ?? ''),
            'supervisor'  => trim($_POST['supervisor'] ?? ''),
        ];

        if ($data['name'] === '') {
            Flash::set('error', 'Team name is required.');
            redirect('teams');
        }

        if ($id > 0) {
            $this->teams->update($id, $data);
            ActivityLog::record('team_update', 'Updated team "' . $data['name'] . '"');
            Flash::set('success', 'Team updated.');
        } else {
            $this->teams->create($data);
            ActivityLog::record('team_create', 'Created team "' . $data['name'] . '"');
            Flash::set('success', 'Team created.');
        }
        redirect('teams');
    }

    public function delete(string $id = '0'): void
    {
        $this->requireAdmin();
        $this->requireCsrf();
        $id = (int) $id;

        // Guard: do not delete a team that still has members or requests.
        $team = $this->teams->find($id);
        if ($team) {
            $counts = $this->teams->allWithCounts();
            foreach ($counts as $c) {
                if ((int) $c['id'] === $id && ((int) $c['member_count'] > 0 || (int) $c['request_count'] > 0)) {
                    Flash::set('error', 'Cannot delete a team that still has members or requests.');
                    redirect('teams');
                }
            }
            $this->teams->delete($id);
            ActivityLog::record('team_delete', 'Deleted team #' . $id);
            Flash::set('success', 'Team deleted.');
        }
        redirect('teams');
    }
}
