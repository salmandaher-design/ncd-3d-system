<?php
class UsersController extends Controller
{
    private User $users;

    public function __construct()
    {
        $this->users = new User();
    }

    public function index(): void
    {
        $this->requireAdmin();
        $this->view('users/index', [
            'pageTitle' => 'Members & Accounts',
            'users'     => $this->users->allWithTeam(),
            'teams'     => (new Team())->all('name ASC'),
        ]);
    }

    public function save(): void
    {
        $this->requireAdmin();
        $this->requireCsrf();

        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            'name'      => trim($_POST['name'] ?? ''),
            'email'     => trim($_POST['email'] ?? ''),
            'phone'     => trim($_POST['phone'] ?? ''),
            'role'      => ($_POST['role'] ?? 'member') === 'admin' ? 'admin' : 'member',
            'team_id'   => $_POST['team_id'] ?? '',
            'password'  => $_POST['password'] ?? '',
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];

        // Validation
        $errors = [];
        if ($data['name'] === '')  $errors[] = 'Name is required.';
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
        if ($this->users->emailExists($data['email'], $id ?: null)) $errors[] = 'That email is already in use.';
        if ($id === 0 && strlen($data['password']) < 6) $errors[] = 'Password must be at least 6 characters.';
        if (!empty($data['password']) && strlen($data['password']) < 6) $errors[] = 'Password must be at least 6 characters.';
        // Members should have a team; admins do not need one.
        if ($data['role'] === 'member' && $data['team_id'] === '') $errors[] = 'Please assign the member to a team.';

        if ($errors) {
            Flash::set('error', implode(' ', $errors));
            redirect('users');
        }

        if ($id > 0) {
            // Prevent removing the last admin / self-lockout.
            $existing = $this->users->find($id);
            if ($existing && $existing['role'] === 'admin' && $data['role'] !== 'admin'
                && $this->users->countAdmins() <= 1) {
                Flash::set('error', 'You cannot remove the last administrator.');
                redirect('users');
            }
            $this->users->update($id, $data);
            ActivityLog::record('user_update', 'Updated account ' . $data['email']);
            Flash::set('success', 'Account updated.');
        } else {
            $this->users->create($data);
            ActivityLog::record('user_create', 'Created account ' . $data['email']);
            Flash::set('success', 'Account created.');
        }
        redirect('users');
    }

    public function delete(string $id = '0'): void
    {
        $this->requireAdmin();
        $this->requireCsrf();
        $id = (int) $id;

        if ($id === Auth::id()) {
            Flash::set('error', 'You cannot delete your own account.');
            redirect('users');
        }
        $user = $this->users->find($id);
        if ($user) {
            if ($user['role'] === 'admin' && $this->users->countAdmins() <= 1) {
                Flash::set('error', 'You cannot delete the last administrator.');
                redirect('users');
            }
            $this->users->delete($id);
            ActivityLog::record('user_delete', 'Deleted account #' . $id);
            Flash::set('success', 'Account deleted.');
        }
        redirect('users');
    }
}
