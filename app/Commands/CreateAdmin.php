<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CreateAdmin extends BaseCommand
{
    protected $group = 'Sun Son Solar';
    protected $name = 'admin:create';
    protected $description = 'Create the first administrator account.';

    public function run(array $params)
    {
        $db = db_connect();
        if ($db->table('users')->where('role', 'admin')->countAllResults() > 0) {
            CLI::error('An administrator already exists. Ask an existing admin to manage access.');
            return EXIT_ERROR;
        }

        $first = trim((string) CLI::prompt('First name'));
        $last = trim((string) CLI::prompt('Last name'));
        $email = strtolower(trim((string) CLI::prompt('Email')));
        $username = trim((string) CLI::prompt('Username'));
        $password = (string) CLI::prompt('Password', null, 'required');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8 || !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).+$/', $password)) {
            CLI::error('Use a valid email and a password with at least 8 characters, including uppercase, lowercase, and a number.');
            return EXIT_ERROR;
        }
        if ($db->table('users')->groupStart()->where('email', $email)->orWhere('username', $username)->groupEnd()->countAllResults() > 0) {
            CLI::error('That email or username is already registered.');
            return EXIT_ERROR;
        }

        $db->table('users')->insert([
            'first_name' => $first,
            'last_name' => $last,
            'birthdate' => '1970-01-01',
            'gender' => 'Prefer not to say',
            'email' => $email,
            'address' => 'Administrator',
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'admin',
            'status' => 'active',
        ]);
        CLI::write('Administrator created. Sign in at /?mode=login', 'green');
        return EXIT_SUCCESS;
    }
}
