<?php
/**
 * Authentication Controller (Login / Logout)
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use PDO;

final class AuthController
{
    public function __construct(private PDO $pdo) {}

    public function loginForm(): void
    {
        if (Auth::user($this->pdo) !== null) {
            redirect('');
        }
        render_view('auth/login', [
            'pageTitle' => t('login_title'),
            'error'     => '',
            'email'     => '',
        ], false);
    }

    public function loginSubmit(): void
    {
        Csrf::requireValid(false);

        $email    = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            render_view('auth/login', [
                'pageTitle' => t('login_title'),
                'error'     => t('error_required_fields'),
                'email'     => $email,
            ], false);
            return;
        }

        $result = Auth::attempt($this->pdo, $email, $password);
        if (!$result['ok']) {
            render_view('auth/login', [
                'pageTitle' => t('login_title'),
                'error'     => $result['error'],
                'email'     => $email,
            ], false);
            return;
        }

        redirect('');
    }

    public function logout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Csrf::requireValid(false);
        }
        Auth::logout();
        redirect('?route=login');
    }
}
