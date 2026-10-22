<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RedirectResponse;

class Home extends BaseController
{
    public function index(): string|RedirectResponse
    {
        if ($this->request->getGet('mode') === 'login') return view('login_page');
        if (session()->get('user_id') && session()->get('must_change_password')) return redirect()->to('/account/password');
        if (session()->get('user_id') && $this->request->getGet('view') !== 'storefront') {
            return redirect()->to(in_array(session()->get('role'), ['admin', 'employee'], true) ? '/admin' : '/customer');
        }

        if ($this->request->getGet('mode') === 'register') return redirect()->to('/register');

        $packages = db_connect()->table('solar_packages')->where('active', 1)->orderBy('display_order')->get()->getResultArray();
        return view('home_page', ['packages' => $packages]);
    }

    public function registerPage(): string|RedirectResponse
    {
        if (session()->get('user_id')) return redirect()->to(in_array(session()->get('role'), ['admin', 'employee'], true) ? '/admin' : '/customer');
        return view('registration_page');
    }

    public function terms(): string
    {
        return view('legal_page', ['pageTitle' => 'Terms of Service', 'pageType' => 'terms']);
    }

    public function privacy(): string
    {
        return view('legal_page', ['pageTitle' => 'Privacy Notice', 'pageType' => 'privacy']);
    }

    public function passwordPage(): string|RedirectResponse
    {
        if (!session()->get('user_id')) return redirect()->to('/?mode=login');
        return view('password_change');
    }

    public function changePassword(): RedirectResponse
    {
        if (!session()->get('user_id')) return redirect()->to('/?mode=login');
        $rules = [
            'current_password' => 'required',
            'new_password' => 'required|min_length[12]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^a-zA-Z0-9]).+$/]',
            'confirm_password' => 'required|matches[new_password]',
        ];
        if (!$this->validate($rules)) return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());

        $db = db_connect();
        $user = $db->table('users')->where('id', session()->get('user_id'))->get()->getRowArray();
        if (!$user || !password_verify((string) $this->request->getPost('current_password'), $user['password_hash'])) {
            return redirect()->back()->withInput()->with('errors', ['current_password' => 'That current password does not match.']);
        }

        $db->table('users')->where('id', $user['id'])->update(['password_hash' => password_hash((string) $this->request->getPost('new_password'), PASSWORD_DEFAULT), 'must_change_password' => 0]);
        session()->regenerate(true);
        session()->set('must_change_password', 0);
        return redirect()->to($user['role'] === 'admin' || $user['role'] === 'employee' ? '/admin' : '/customer')->with('success', 'Your password has been updated.');
    }

    public function register(): RedirectResponse
    {
        $rules = [
            'firstName' => 'required|max_length[100]',
            'lastName' => 'required|max_length[100]',
            'birthdate' => 'required|valid_date[Y-m-d]',
            'gender' => 'required|in_list[Female,Male,Prefer not to say]',
            'email' => 'required|valid_email|max_length[190]|is_unique[users.email]',
            'phone' => 'permit_empty|max_length[40]',
            'address' => 'required|max_length[255]',
            'username' => 'required|min_length[3]|max_length[60]|is_unique[users.username]',
            'password' => 'required|min_length[8]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).+$/]',
        ];

        if ($this->request->getPost('terms') !== 'on') {
            $rules['terms'] = 'required';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $db = db_connect();
        $db->table('users')->insert([
            'first_name' => trim((string) $this->request->getPost('firstName')),
            'middle_name' => trim((string) $this->request->getPost('middleName')) ?: null,
            'last_name' => trim((string) $this->request->getPost('lastName')),
            'birthdate' => $this->request->getPost('birthdate'),
            'gender' => $this->request->getPost('gender'),
            'email' => strtolower(trim((string) $this->request->getPost('email'))),
            'phone' => trim((string) $this->request->getPost('phone')) ?: null,
            'address' => trim((string) $this->request->getPost('address')),
            'username' => trim((string) $this->request->getPost('username')),
            'password_hash' => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
            'role' => 'customer',
            'status' => 'active',
        ]);

        session()->setFlashdata('success', 'Account created. Sign in to continue.');
        return redirect()->to('/?mode=login');
    }

    public function login(): RedirectResponse
    {
        $identity = trim((string) $this->request->getPost('identity'));
        $user = db_connect()->table('users')
            ->groupStart()->where('email', strtolower($identity))->orWhere('username', $identity)->groupEnd()
            ->get()->getRowArray();

        if (!$user || !password_verify((string) $this->request->getPost('password'), $user['password_hash']) || $user['status'] !== 'active') {
            return redirect()->to('/?mode=login')->withInput()->with('errors', ['login' => 'The credentials are incorrect or the account is inactive.']);
        }

        session()->regenerate(true);
        session()->set(['user_id' => $user['id'], 'name' => $user['first_name'], 'role' => $user['role'], 'must_change_password' => (int) ($user['must_change_password'] ?? 0)]);
        if ((int) ($user['must_change_password'] ?? 0) === 1) return redirect()->to('/account/password');
        return redirect()->to(in_array($user['role'], ['admin', 'employee'], true) ? '/admin' : '/customer');
    }

    public function customer(): string|RedirectResponse
    {
        if (!session()->get('user_id')) return redirect()->to('/?mode=login');
        if (session()->get('role') !== 'customer') return redirect()->to(in_array(session()->get('role'), ['admin', 'employee'], true) ? '/admin' : '/?mode=login');
        if (session()->get('must_change_password')) return redirect()->to('/account/password');
        $db = db_connect();
        $user = $db->table('users')->where('id', session()->get('user_id'))->get()->getRowArray();
        if (!$user || $user['role'] !== 'customer' || $user['status'] !== 'active') {
            $this->clearActiveTabAuth();
            return redirect()->to('/?mode=login');
        }
        $requests = $db->table('service_requests')->where('user_id', session()->get('user_id'))->orderBy('created_at', 'DESC')->get()->getResultArray();
        $packages = $db->table('solar_packages')->where('active', 1)->orderBy('display_order')->get()->getResultArray();
        $quotes = $db->table('quote_requests')->select('quote_requests.*, solar_packages.name AS package_name')->join('solar_packages', 'solar_packages.id = quote_requests.package_id', 'left')->where('quote_requests.user_id', session()->get('user_id'))->orderBy('quote_requests.created_at', 'DESC')->get()->getResultArray();
        $installations = $db->table('installations')->select('installations.*, solar_packages.name AS package_name')->join('solar_packages', 'solar_packages.id = installations.package_id', 'left')->where('installations.user_id', session()->get('user_id'))->orderBy('installations.created_at', 'DESC')->get()->getResultArray();
        $activity = $db->table('activity_log')->where('customer_user_id', session()->get('user_id'))->orderBy('created_at', 'DESC')->limit(12)->get()->getResultArray();
        return view('customer_dashboard', ['user' => $user, 'displayName' => trim($user['first_name'] . ' ' . $user['last_name']), 'requests' => $requests, 'packages' => $packages, 'quotes' => $quotes, 'installations' => $installations, 'activity' => $activity]);
    }

    public function requestQuote(): RedirectResponse
    {
        if (!session()->get('user_id') || session()->get('role') !== 'customer') return redirect()->to('/?mode=login');
        $rules = ['package_id' => 'required|is_not_unique[solar_packages.id]', 'monthly_bill' => 'required|decimal|greater_than[0]|less_than_equal_to[1000000]', 'roof_type' => 'required|in_list[Metal,Concrete,Tile,Other]', 'message' => 'permit_empty|max_length[2000]'];
        if (!$this->validate($rules)) return redirect()->to('/customer#solar-quote')->withInput()->with('errors', $this->validator->getErrors());
        db_connect()->table('quote_requests')->insert(['user_id' => session()->get('user_id'), 'package_id' => $this->request->getPost('package_id'), 'monthly_bill' => $this->request->getPost('monthly_bill'), 'roof_type' => $this->request->getPost('roof_type'), 'message' => trim((string) $this->request->getPost('message')) ?: null]);
        $quoteId = (int) db_connect()->insertID();
        $this->logActivity('quote', $quoteId, 'Quote request submitted', (int) session()->get('user_id'));
        return redirect()->to('/customer#my-quotes')->with('success', 'Quote request received. Our solar team will contact you to arrange a site assessment.');
    }

    public function createRequest(): RedirectResponse
    {
        if (!session()->get('user_id') || session()->get('role') !== 'customer') return redirect()->to('/?mode=login');
        $rules = [
            'subject' => 'required|max_length[160]',
            'category' => 'required|in_list[Installation,Maintenance,Billing,Other]',
            'details' => 'required|min_length[10]|max_length[5000]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->to('/customer#new-request')->withInput()->with('errors', $this->validator->getErrors());
        }
        db_connect()->table('service_requests')->insert([
            'user_id' => session()->get('user_id'),
            'subject' => trim((string) $this->request->getPost('subject')),
            'category' => $this->request->getPost('category'),
            'details' => trim((string) $this->request->getPost('details')),
        ]);
        $requestId = (int) db_connect()->insertID();
        $this->logActivity('service', $requestId, 'Support request submitted', (int) session()->get('user_id'));
        return redirect()->to('/customer')->with('success', 'Your service request has been submitted.');
    }

    public function admin(): string|RedirectResponse
    {
        if (!session()->get('user_id')) return redirect()->to('/?mode=login');
        if (!in_array(session()->get('role'), ['admin', 'employee'], true)) return redirect()->to(session()->get('role') === 'customer' ? '/customer' : '/?mode=login');
        if (session()->get('must_change_password')) return redirect()->to('/account/password');
        $db = db_connect();
        $users = $db->table('users')->orderBy('created_at', 'DESC')->get()->getResultArray();
        $requests = $db->table('service_requests')->select('service_requests.*, users.first_name, users.last_name, users.email')->join('users', 'users.id = service_requests.user_id')->orderBy('service_requests.created_at', 'DESC')->get()->getResultArray();
        $quotes = $db->table('quote_requests')->select('quote_requests.*, users.first_name, users.last_name, users.email, users.phone, solar_packages.name AS package_name, solar_packages.price_php')->join('users', 'users.id = quote_requests.user_id')->join('solar_packages', 'solar_packages.id = quote_requests.package_id', 'left')->orderBy('quote_requests.created_at', 'DESC')->get()->getResultArray();
        $installations = $db->table('installations')->select('installations.*, users.first_name, users.last_name, solar_packages.name AS package_name')->join('users', 'users.id = installations.user_id')->join('solar_packages', 'solar_packages.id = installations.package_id', 'left')->orderBy('installations.created_at', 'DESC')->get()->getResultArray();
        $packages = $db->table('solar_packages')->orderBy('display_order')->get()->getResultArray();
        $staff = $db->table('users')->whereIn('role', ['admin', 'employee'])->orderBy('role')->orderBy('created_at', 'DESC')->get()->getResultArray();
        $activity = $db->table('activity_log')->select('activity_log.*, users.first_name, users.last_name')->join('users', 'users.id = activity_log.actor_user_id', 'left')->orderBy('activity_log.created_at', 'DESC')->limit(100)->get()->getResultArray();
        $stats = [
            'customers' => $db->table('users')->where('role', 'customer')->countAllResults(),
            'active_customers' => $db->table('users')->where('role', 'customer')->where('status', 'active')->countAllResults(),
            'new_quotes' => $db->table('quote_requests')->where('status', 'New')->countAllResults(),
            'open_service' => $db->table('service_requests')->whereIn('status', ['Open', 'In Progress'])->countAllResults(),
            'active_installs' => $db->table('installations')->whereNotIn('status', ['Completed', 'Cancelled'])->countAllResults(),
        ];
        return view('admin_dashboard', ['users' => $users, 'requests' => $requests, 'quotes' => $quotes, 'installations' => $installations, 'packages' => $packages, 'staff' => $staff, 'activity' => $activity, 'stats' => $stats]);
    }

    public function createCustomer(): RedirectResponse
    {
        if (session()->get('role') !== 'admin') return redirect()->to('/?mode=login');
        $rules = ['first_name' => 'required|max_length[100]', 'last_name' => 'required|max_length[100]', 'email' => 'required|valid_email|max_length[190]|is_unique[users.email]', 'username' => 'required|min_length[3]|max_length[60]|is_unique[users.username]', 'password' => 'required|min_length[8]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).+$/]', 'address' => 'required|max_length[255]', 'phone' => 'permit_empty|max_length[40]', 'birthdate' => 'required|valid_date[Y-m-d]', 'gender' => 'required|in_list[Female,Male,Prefer not to say]'];
        if (!$this->validate($rules)) return redirect()->to('/admin#customers')->withInput()->with('errors', $this->validator->getErrors());
        $db = db_connect();
        $db->table('users')->insert(['first_name' => trim((string)$this->request->getPost('first_name')), 'last_name' => trim((string)$this->request->getPost('last_name')), 'birthdate' => $this->request->getPost('birthdate'), 'gender' => $this->request->getPost('gender'), 'email' => strtolower(trim((string)$this->request->getPost('email'))), 'phone' => trim((string)$this->request->getPost('phone')) ?: null, 'address' => trim((string)$this->request->getPost('address')), 'username' => trim((string)$this->request->getPost('username')), 'password_hash' => password_hash((string)$this->request->getPost('password'), PASSWORD_DEFAULT), 'role' => 'customer', 'status' => 'active']);
        $id = (int)$db->insertID();
        $this->logActivity('account', $id, 'Customer account created');
        return redirect()->to('/admin#customers')->with('success', 'Customer account created.');
    }

    public function updateEmployee(int $id): RedirectResponse
    {
        if (session()->get('role') !== 'admin') return redirect()->to('/?mode=login');
        $db = db_connect();
        $employee = $db->table('users')->where('id', $id)->where('role', 'employee')->get()->getRowArray();
        if (!$employee) return redirect()->to('/admin#staff')->with('errors', ['Employee not found.']);
        $rules = ['first_name' => 'required|max_length[100]', 'last_name' => 'required|max_length[100]', 'email' => "required|valid_email|max_length[190]|is_unique[users.email,id,{$id}]", 'username' => "required|min_length[3]|max_length[60]|is_unique[users.username,id,{$id}]"];
        if (!$this->validate($rules)) return redirect()->to('/admin#staff')->withInput()->with('errors', $this->validator->getErrors());
        $db->table('users')->where('id', $id)->where('role', 'employee')->update(['first_name' => trim((string)$this->request->getPost('first_name')), 'last_name' => trim((string)$this->request->getPost('last_name')), 'email' => strtolower(trim((string)$this->request->getPost('email'))), 'username' => trim((string)$this->request->getPost('username'))]);
        $this->logActivity('staff', $id, 'Employee account details updated');
        return redirect()->to('/admin#staff')->with('success', 'Employee details updated.');
    }

    public function deleteEmployee(int $id): RedirectResponse
    {
        if (session()->get('role') !== 'admin') return redirect()->to('/?mode=login');
        if ($id === (int)session()->get('user_id')) return redirect()->to('/admin#staff')->with('errors', ['You cannot delete your own administrator account.']);
        $db = db_connect();
        $employee = $db->table('users')->where('id', $id)->where('role', 'employee')->get()->getRowArray();
        if ($employee) {
            $db->table('users')->where('id', $id)->where('role', 'employee')->update(['status' => 'inactive']);
            $this->logActivity('staff', $id, 'Employee account deactivated: '.$employee['first_name'].' '.$employee['last_name']);
            return redirect()->to('/admin#staff')->with('success', 'Employee access revoked. The account was retained for audit history.');
        }
        return redirect()->to('/admin#staff')->with('errors', ['Employee account not found.']);
    }

    public function updateCustomer(int $id): RedirectResponse
    {
        if (session()->get('role') !== 'admin') return redirect()->to('/?mode=login');
        $db = db_connect();
        $customer = $db->table('users')->where('id', $id)->where('role', 'customer')->get()->getRowArray();
        if (!$customer) return redirect()->to('/admin#customers')->with('errors', ['Customer account not found.']);
        $rules = ['first_name' => 'required|max_length[100]', 'last_name' => 'required|max_length[100]', 'email' => "required|valid_email|max_length[190]|is_unique[users.email,id,{$id}]", 'username' => "required|min_length[3]|max_length[60]|is_unique[users.username,id,{$id}]", 'address' => 'required|max_length[255]', 'phone' => 'permit_empty|max_length[40]', 'birthdate' => 'required|valid_date[Y-m-d]', 'gender' => 'required|in_list[Female,Male,Prefer not to say]'];
        if (!$this->validate($rules)) return redirect()->to('/admin#customers')->withInput()->with('errors', $this->validator->getErrors());
        $db->table('users')->where('id', $id)->where('role', 'customer')->update(['first_name' => trim((string)$this->request->getPost('first_name')), 'last_name' => trim((string)$this->request->getPost('last_name')), 'email' => strtolower(trim((string)$this->request->getPost('email'))), 'username' => trim((string)$this->request->getPost('username')), 'address' => trim((string)$this->request->getPost('address')), 'phone' => trim((string)$this->request->getPost('phone')) ?: null, 'birthdate' => $this->request->getPost('birthdate'), 'gender' => $this->request->getPost('gender')]);
        $this->logActivity('account', $id, 'Customer account details updated');
        return redirect()->to('/admin#customers')->with('success', 'Customer details updated.');
    }

    public function createPackage(): RedirectResponse
    {
        return $this->savePackage();
    }

    public function updatePackage(int $id): RedirectResponse
    {
        return $this->savePackage($id);
    }

    private function savePackage(?int $id = null): RedirectResponse
    {
        if (session()->get('role') !== 'admin') return redirect()->to('/?mode=login');
        $db = db_connect();
        $slug = strtolower(trim((string)$this->request->getPost('slug')));
        $slugRule = 'required|alpha_dash|max_length[80]|is_unique[solar_packages.slug]';
        if ($id) $slugRule = "required|alpha_dash|max_length[80]|is_unique[solar_packages.slug,id,{$id}]";
        $rules = ['slug' => $slugRule, 'name' => 'required|max_length[120]', 'capacity_kw' => 'required|decimal|greater_than[0]|less_than_equal_to[999]', 'price_php' => 'required|decimal|greater_than_equal_to[0]', 'monthly_savings_php' => 'required|decimal|greater_than_equal_to[0]', 'description' => 'required|max_length[255]', 'warranty_years' => 'required|integer|greater_than[0]|less_than_equal_to[255]', 'features' => 'required|max_length[6000]', 'badge' => 'permit_empty|max_length[60]', 'display_order' => 'required|integer|greater_than_equal_to[0]|less_than_equal_to[65535]'];
        if (!$this->validate($rules)) return redirect()->to('/admin#packages')->withInput()->with('errors', $this->validator->getErrors());
        $data = ['slug' => $slug, 'name' => trim((string)$this->request->getPost('name')), 'capacity_kw' => $this->request->getPost('capacity_kw'), 'price_php' => $this->request->getPost('price_php'), 'monthly_savings_php' => $this->request->getPost('monthly_savings_php'), 'description' => trim((string)$this->request->getPost('description')), 'warranty_years' => $this->request->getPost('warranty_years'), 'features' => trim((string)$this->request->getPost('features')), 'badge' => trim((string)$this->request->getPost('badge')) ?: null, 'display_order' => $this->request->getPost('display_order'), 'active' => $this->request->getPost('active') === '1' ? 1 : 0];
        if ($id) $db->table('solar_packages')->where('id', $id)->update($data);
        else $db->table('solar_packages')->insert($data);
        $packageId = $id ?? (int)$db->insertID();
        $this->logActivity('package', $packageId, $id ? 'Solar package updated: '.$data['name'] : 'Solar package created: '.$data['name']);
        return redirect()->to('/admin#packages')->with('success', $id ? 'Solar package updated.' : 'Solar package created.');
    }

    public function deletePackage(int $id): RedirectResponse
    {
        if (session()->get('role') !== 'admin') return redirect()->to('/?mode=login');
        $db = db_connect();
        $package = $db->table('solar_packages')->where('id', $id)->get()->getRowArray();
        if (!$package) return redirect()->to('/admin#packages')->with('errors', ['Package not found.']);
        if ($db->table('quote_requests')->where('package_id', $id)->countAllResults() || $db->table('installations')->where('package_id', $id)->countAllResults()) return redirect()->to('/admin#packages')->with('errors', ['This package has customer history, so it cannot be deleted. Deactivate it instead.']);
        $db->table('solar_packages')->where('id', $id)->delete();
        $this->logActivity('package', $id, 'Solar package deleted: '.$package['name']);
        return redirect()->to('/admin#packages')->with('success', 'Solar package deleted.');
    }

    public function createQuote(): RedirectResponse
    {
        if (session()->get('role') !== 'admin') return redirect()->to('/?mode=login');
        $rules = ['user_id' => 'required|is_not_unique[users.id]', 'package_id' => 'permit_empty|is_not_unique[solar_packages.id]', 'monthly_bill' => 'required|decimal|greater_than[0]|less_than_equal_to[1000000]', 'roof_type' => 'required|in_list[Metal,Concrete,Tile,Other]', 'message' => 'permit_empty|max_length[2000]'];
        if (!$this->validate($rules)) return redirect()->to('/admin#quotes')->withInput()->with('errors', $this->validator->getErrors());
        $db = db_connect(); $customer = $db->table('users')->where('id', $this->request->getPost('user_id'))->where('role', 'customer')->get()->getRowArray();
        if (!$customer) return redirect()->to('/admin#quotes')->withInput()->with('errors', ['Choose a customer account.']);
        $db->table('quote_requests')->insert(['user_id' => $customer['id'], 'package_id' => $this->request->getPost('package_id') ?: null, 'monthly_bill' => $this->request->getPost('monthly_bill'), 'roof_type' => $this->request->getPost('roof_type'), 'message' => trim((string)$this->request->getPost('message')) ?: null]);
        $id = (int)$db->insertID(); $this->logActivity('quote', $id, 'Quote request created by staff', (int)$customer['id']);
        return redirect()->to('/admin#quotes')->with('success', 'Quote request created.');
    }

    public function deleteQuote(int $id): RedirectResponse
    {
        if (session()->get('role') !== 'admin') return redirect()->to('/?mode=login');
        $db = db_connect(); $quote = $db->table('quote_requests')->where('id', $id)->get()->getRowArray();
        if ($quote) { $db->table('quote_requests')->where('id', $id)->delete(); $this->logActivity('quote', $id, 'Quote request deleted', (int)$quote['user_id']); }
        return redirect()->to('/admin#quotes')->with('success', 'Quote request deleted.');
    }

    public function editQuote(int $id): RedirectResponse
    {
        if (session()->get('role') !== 'admin') return redirect()->to('/?mode=login');
        $rules = ['user_id' => 'required|is_not_unique[users.id]', 'package_id' => 'permit_empty|is_not_unique[solar_packages.id]', 'monthly_bill' => 'required|decimal|greater_than[0]|less_than_equal_to[1000000]', 'roof_type' => 'required|in_list[Metal,Concrete,Tile,Other]', 'status' => 'required|in_list[New,Contacted,Site Survey,Proposal Sent,Won,Lost]', 'message' => 'permit_empty|max_length[2000]', 'admin_note' => 'permit_empty|max_length[2000]'];
        if (!$this->validate($rules)) return redirect()->to('/admin#quotes')->withInput()->with('errors', $this->validator->getErrors());
        $db = db_connect(); $customer = $db->table('users')->where('id', $this->request->getPost('user_id'))->where('role', 'customer')->get()->getRowArray();
        if (!$customer) return redirect()->to('/admin#quotes')->withInput()->with('errors', ['Choose a customer account.']);
        $quote = $db->table('quote_requests')->where('id', $id)->get()->getRowArray();
        if (!$quote) return redirect()->to('/admin#quotes')->with('errors', ['Quote not found.']);
        $db->table('quote_requests')->where('id', $id)->update(['user_id' => $customer['id'], 'package_id' => $this->request->getPost('package_id') ?: null, 'monthly_bill' => $this->request->getPost('monthly_bill'), 'roof_type' => $this->request->getPost('roof_type'), 'status' => $this->request->getPost('status'), 'message' => trim((string)$this->request->getPost('message')) ?: null, 'admin_note' => trim((string)$this->request->getPost('admin_note')) ?: null]);
        $this->logActivity('quote', $id, 'Quote record edited by admin', (int)$customer['id']);
        return redirect()->to('/admin#quotes')->with('success', 'Quote updated.');
    }

    public function deleteInstallation(int $id): RedirectResponse
    {
        if (session()->get('role') !== 'admin') return redirect()->to('/?mode=login');
        $db = db_connect(); $job = $db->table('installations')->where('id', $id)->get()->getRowArray();
        if ($job) { $db->table('installations')->where('id', $id)->delete(); $this->logActivity('installation', $id, 'Installation project deleted', (int)$job['user_id']); }
        return redirect()->to('/admin#installations')->with('success', 'Installation project deleted.');
    }

    public function editInstallation(int $id): RedirectResponse
    {
        if (session()->get('role') !== 'admin') return redirect()->to('/?mode=login');
        $rules = ['user_id' => 'required|is_not_unique[users.id]', 'package_id' => 'required|is_not_unique[solar_packages.id]', 'site_address' => 'required|max_length[255]', 'scheduled_date' => 'permit_empty|valid_date[Y-m-d]', 'status' => 'required|in_list[Survey Scheduled,Design,Permitting,Installation,Inspection,Completed,Cancelled]', 'progress' => 'required|integer|greater_than_equal_to[0]|less_than_equal_to[100]', 'admin_note' => 'permit_empty|max_length[2000]'];
        if (!$this->validate($rules)) return redirect()->to('/admin#installations')->withInput()->with('errors', $this->validator->getErrors());
        $db = db_connect(); $customer = $db->table('users')->where('id', $this->request->getPost('user_id'))->where('role', 'customer')->get()->getRowArray();
        if (!$customer) return redirect()->to('/admin#installations')->withInput()->with('errors', ['Choose a customer account.']);
        $job = $db->table('installations')->where('id', $id)->get()->getRowArray();
        if (!$job) return redirect()->to('/admin#installations')->with('errors', ['Installation not found.']);
        $db->table('installations')->where('id', $id)->update(['user_id' => $customer['id'], 'package_id' => $this->request->getPost('package_id'), 'site_address' => trim((string)$this->request->getPost('site_address')), 'scheduled_date' => $this->request->getPost('scheduled_date') ?: null, 'status' => $this->request->getPost('status'), 'progress' => $this->request->getPost('progress'), 'admin_note' => trim((string)$this->request->getPost('admin_note')) ?: null]);
        $this->logActivity('installation', $id, 'Installation record edited by admin', (int)$customer['id']);
        return redirect()->to('/admin#installations')->with('success', 'Installation updated.');
    }

    public function deleteRequest(int $id): RedirectResponse
    {
        if (session()->get('role') !== 'admin') return redirect()->to('/?mode=login');
        $db = db_connect(); $ticket = $db->table('service_requests')->where('id', $id)->get()->getRowArray();
        if ($ticket) { $db->table('service_requests')->where('id', $id)->delete(); $this->logActivity('service', $id, 'Support request deleted', (int)$ticket['user_id']); }
        return redirect()->to('/admin#service-requests')->with('success', 'Support request deleted.');
    }

    public function editRequest(int $id): RedirectResponse
    {
        if (session()->get('role') !== 'admin') return redirect()->to('/?mode=login');
        $rules = ['user_id' => 'required|is_not_unique[users.id]', 'subject' => 'required|max_length[160]', 'category' => 'required|in_list[Installation,Maintenance,Billing,Other]', 'details' => 'required|min_length[10]|max_length[5000]', 'status' => 'required|in_list[Open,In Progress,Resolved,Closed]', 'admin_note' => 'permit_empty|max_length[5000]'];
        if (!$this->validate($rules)) return redirect()->to('/admin#service-requests')->withInput()->with('errors', $this->validator->getErrors());
        $db = db_connect(); $customer = $db->table('users')->where('id', $this->request->getPost('user_id'))->where('role', 'customer')->get()->getRowArray();
        if (!$customer) return redirect()->to('/admin#service-requests')->withInput()->with('errors', ['Choose a customer account.']);
        if (!$db->table('service_requests')->where('id', $id)->countAllResults()) return redirect()->to('/admin#service-requests')->with('errors', ['Support request not found.']);
        $db->table('service_requests')->where('id', $id)->update(['user_id' => $customer['id'], 'subject' => trim((string)$this->request->getPost('subject')), 'category' => $this->request->getPost('category'), 'details' => trim((string)$this->request->getPost('details')), 'status' => $this->request->getPost('status'), 'admin_note' => trim((string)$this->request->getPost('admin_note')) ?: null]);
        $this->logActivity('service', $id, 'Support request edited by admin', (int)$customer['id']);
        return redirect()->to('/admin#service-requests')->with('success', 'Support request updated.');
    }

    public function createEmployee(): RedirectResponse
    {
        if (session()->get('role') !== 'admin') return redirect()->to('/?mode=login');
        $rules = ['first_name' => 'required|max_length[100]', 'last_name' => 'required|max_length[100]', 'email' => 'required|valid_email|max_length[190]|is_unique[users.email]', 'username' => 'required|min_length[3]|max_length[60]|is_unique[users.username]', 'password' => 'required|min_length[12]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^a-zA-Z0-9]).+$/]'];
        if (!$this->validate($rules)) return redirect()->to('/admin#staff')->withInput()->with('errors', $this->validator->getErrors());
        db_connect()->table('users')->insert([
            'first_name' => trim((string) $this->request->getPost('first_name')),
            'last_name' => trim((string) $this->request->getPost('last_name')),
            'birthdate' => '1970-01-01',
            'gender' => 'Prefer not to say',
            'email' => strtolower(trim((string) $this->request->getPost('email'))),
            'address' => 'Staff account',
            'username' => trim((string) $this->request->getPost('username')),
            'password_hash' => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
            'role' => 'employee',
            'status' => 'active',
            'must_change_password' => 1,
        ]);
        $employeeId = (int) db_connect()->insertID();
        $this->logActivity('staff', $employeeId, 'Employee account created: ' . trim((string) $this->request->getPost('first_name')) . ' ' . trim((string) $this->request->getPost('last_name')));
        return redirect()->to('/admin#staff')->with('success', 'Employee account created. Share the initial password securely; the employee must replace it at first sign-in.');
    }

    public function updateEmployeeStatus(int $id): RedirectResponse
    {
        if (session()->get('role') !== 'admin') return redirect()->to('/?mode=login');
        $status = (string) $this->request->getPost('status');
        if (in_array($status, ['active', 'inactive'], true) && $id !== (int) session()->get('user_id')) {
            $db = db_connect();
            $staff = $db->table('users')->where('id', $id)->where('role', 'employee')->get()->getRowArray();
            if ($staff && $staff['status'] !== $status) {
                $db->table('users')->where('id', $id)->where('role', 'employee')->update(['status' => $status]);
                $this->logActivity('staff', $id, 'Employee access ' . $status . ': ' . $staff['first_name'] . ' ' . $staff['last_name']);
            }
        }
        return redirect()->to('/admin#staff');
    }

    public function updateQuote(int $id): RedirectResponse
    {
        if (!$this->hasStaffAccess()) return redirect()->to('/?mode=login');
        if (!$this->validate(['status' => 'required|in_list[New,Contacted,Site Survey,Proposal Sent,Won,Lost]', 'admin_note' => 'permit_empty|max_length[2000]'])) return redirect()->to('/admin#quotes')->withInput()->with('errors', $this->validator->getErrors());
        $db = db_connect();
        $oldLead = $db->table('quote_requests')->where('id', $id)->get()->getRowArray();
        $db->table('quote_requests')->where('id', $id)->update(['status' => $this->request->getPost('status'), 'admin_note' => trim((string) $this->request->getPost('admin_note')) ?: null]);
        $lead = $db->table('quote_requests')->where('id', $id)->get()->getRowArray();
        if ($lead && $oldLead && ($lead['status'] !== $oldLead['status'] || $lead['admin_note'] !== $oldLead['admin_note'])) {
            $this->logActivity('quote', $id, 'Quote updated to ' . $lead['status'] . ($lead['admin_note'] ? ': ' . $lead['admin_note'] : ''), (int) $lead['user_id']);
        }
        $message = 'Quote lead updated.';
        if ($lead && $lead['status'] === 'Won' && $lead['package_id']) {
            $existingJob = $db->table('installations')->where('user_id', $lead['user_id'])->where('package_id', $lead['package_id'])->countAllResults();
            if ($existingJob === 0) {
                $customer = $db->table('users')->where('id', $lead['user_id'])->get()->getRowArray();
                $db->table('installations')->insert(['user_id' => $lead['user_id'], 'package_id' => $lead['package_id'], 'site_address' => $customer['address'] ?? 'Address to be confirmed', 'status' => 'Survey Scheduled', 'progress' => 5]);
                $message .= ' An installation tracker was opened for the customer.';
            }
        }
        return redirect()->to('/admin#quotes')->with('success', $message);
    }

    public function createInstallation(): RedirectResponse
    {
        if (!$this->hasStaffAccess()) return redirect()->to('/?mode=login');
        $rules = ['user_id' => 'required|is_not_unique[users.id]', 'package_id' => 'required|is_not_unique[solar_packages.id]', 'site_address' => 'required|max_length[255]', 'scheduled_date' => 'permit_empty|valid_date[Y-m-d]'];
        if (!$this->validate($rules)) return redirect()->to('/admin#installations')->withInput()->with('errors', $this->validator->getErrors());
        $customer = db_connect()->table('users')->where('id', $this->request->getPost('user_id'))->where('role', 'customer')->get()->getRowArray();
        if (!$customer) return redirect()->to('/admin#installations')->withInput()->with('errors', ['user_id' => 'Choose a customer account for this installation.']);
        $db = db_connect();
        $db->table('installations')->insert(['user_id' => $this->request->getPost('user_id'), 'package_id' => $this->request->getPost('package_id'), 'site_address' => trim((string) $this->request->getPost('site_address')), 'scheduled_date' => $this->request->getPost('scheduled_date') ?: null]);
        $this->logActivity('installation', (int) $db->insertID(), 'Installation project created', (int) $customer['id']);
        return redirect()->to('/admin#installations')->with('success', 'Installation job created.');
    }

    public function updateInstallation(int $id): RedirectResponse
    {
        if (!$this->hasStaffAccess()) return redirect()->to('/?mode=login');
        $rules = ['status' => 'required|in_list[Survey Scheduled,Design,Permitting,Installation,Inspection,Completed,Cancelled]', 'progress' => 'required|integer|greater_than_equal_to[0]|less_than_equal_to[100]', 'admin_note' => 'permit_empty|max_length[2000]'];
        if (!$this->validate($rules)) return redirect()->to('/admin#installations')->withInput()->with('errors', $this->validator->getErrors());
        $db = db_connect();
        $project = $db->table('installations')->where('id', $id)->get()->getRowArray();
        $db->table('installations')->where('id', $id)->update(['status' => $this->request->getPost('status'), 'progress' => $this->request->getPost('progress'), 'admin_note' => trim((string) $this->request->getPost('admin_note')) ?: null]);
        if ($project && ($project['status'] !== $this->request->getPost('status') || (int)$project['progress'] !== (int)$this->request->getPost('progress') || $project['admin_note'] !== (trim((string)$this->request->getPost('admin_note')) ?: null))) $this->logActivity('installation', $id, 'Installation updated: ' . $this->request->getPost('status') . ' (' . $this->request->getPost('progress') . '%)' . (trim((string) $this->request->getPost('admin_note')) ? ': ' . trim((string) $this->request->getPost('admin_note')) : ''), (int) $project['user_id']);
        return redirect()->to('/admin#installations')->with('success', 'Installation progress updated.');
    }

    public function updateRequest(int $id): RedirectResponse
    {
        if (!$this->hasStaffAccess()) return redirect()->to('/?mode=login');
        $rules = ['status' => 'required|in_list[Open,In Progress,Resolved,Closed]', 'admin_note' => 'permit_empty|max_length[5000]'];
        if (!$this->validate($rules)) return redirect()->to('/admin#service-requests')->withInput()->with('errors', $this->validator->getErrors());
        $db = db_connect();
        $ticket = $db->table('service_requests')->where('id', $id)->get()->getRowArray();
        $db->table('service_requests')->where('id', $id)->update([
            'status' => $this->request->getPost('status'),
            'admin_note' => trim((string) $this->request->getPost('admin_note')) ?: null,
        ]);
        if ($ticket && ($ticket['status'] !== $this->request->getPost('status') || $ticket['admin_note'] !== (trim((string)$this->request->getPost('admin_note')) ?: null))) $this->logActivity('service', $id, 'Support request updated: ' . $this->request->getPost('status') . (trim((string) $this->request->getPost('admin_note')) ? ': ' . trim((string) $this->request->getPost('admin_note')) : ''), (int) $ticket['user_id']);
        return redirect()->to('/admin#service-requests')->with('success', 'Service request updated.');
    }

    public function updateStatus(int $id): RedirectResponse
    {
        if (!session()->get('user_id') || session()->get('role') !== 'admin') return redirect()->to('/?mode=login');
        $status = (string) $this->request->getPost('status');
        if (in_array($status, ['active', 'inactive'], true) && $id !== (int) session()->get('user_id')) {
            $db = db_connect();
            $customer = $db->table('users')->where('id', $id)->where('role', 'customer')->get()->getRowArray();
            if ($customer && $customer['status'] !== $status) {
                $db->table('users')->where('id', $id)->where('role', 'customer')->update(['status' => $status]);
                $this->logActivity('account', $id, 'Customer account access ' . $status . ': ' . $customer['first_name'] . ' ' . $customer['last_name']);
            }
        }
        return redirect()->to('/admin');
    }

    public function logout(): RedirectResponse
    {
        $session = session();
        $token = $session->get('_active_tab_context');
        $contexts = $session->get('auth_tab_contexts') ?? [];
        if (is_string($token) && isset($contexts[$token])) {
            $this->clearActiveTabAuth();
        } else {
            $session->destroy();
        }

        return redirect()->to('/?mode=login');
    }

    private function clearActiveTabAuth(): void
    {
        $session = session();
        $token = $session->get('_active_tab_context');
        $contexts = $session->get('auth_tab_contexts') ?? [];
        if (is_string($token)) {
            unset($contexts[$token]);
            $session->set('auth_tab_contexts', $contexts);
        }
        $session->remove(['user_id', 'name', 'role', 'must_change_password']);
    }

    private function hasStaffAccess(): bool
    {
        return (bool) session()->get('user_id') && in_array(session()->get('role'), ['admin', 'employee'], true);
    }

    private function logActivity(string $entityType, ?int $entityId, string $description, ?int $customerId = null): void
    {
        db_connect()->table('activity_log')->insert([
            'actor_user_id' => session()->get('user_id') ?: null,
            'customer_user_id' => $customerId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'event_type' => $entityType . '.updated',
            'description' => mb_substr($description, 0, 500),
        ]);
    }
}
