<?php

namespace Src\Controllers;

use Src\Models\User;
use Src\Services\JwtService;
use Src\Services\EmailService;

/**
 * Auth Controller - Handles authentication (login, register, OAuth)
 */
class AuthController
{
    private User $userModel;
    private JwtService $jwtService;
    private EmailService $emailService;

    public function __construct()
    {
        $this->userModel = new User();
        $this->jwtService = new JwtService();
        $this->emailService = new EmailService();
    }

    /**
     * Register a new user
     */
    public function register(array $data): array
    {
        // Validate required fields
        $requiredFields = ['email', 'password', 'full_name', 'date_of_birth', 'document_number', 'country', 'institution', 'category'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Campo {$field} é obrigatório");
            }
        }

        // Check if email already exists
        if ($this->userModel->findByEmail($data['email'])) {
            throw new \InvalidArgumentException('E-mail já cadastrado');
        }

        // Validate password strength
        $config = require __DIR__ . '/../../config/app.php';
        if (strlen($data['password']) < $config['security']['password_min_length']) {
            throw new \InvalidArgumentException('Senha deve ter pelo menos ' . $config['security']['password_min_length'] . ' caracteres');
        }

        // Hash password
        $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        unset($data['password']);

        // Create user
        $userId = $this->userModel->create($data);

        // Send welcome email
        $user = $this->userModel->findById($userId);
        $this->emailService->sendWelcomeEmail($user['email'], $user['full_name']);

        return [
            'message' => 'Cadastro realizado com sucesso. Verifique seu e-mail para instruções de pagamento.',
            'user_id' => $userId
        ];
    }

    /**
     * Login with email/password
     */
    public function login(string $email, string $password): array
    {
        $user = $this->userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new \Exception('E-mail ou senha inválidos');
        }

        return $this->jwtService->generateToken($user);
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback(string $googleId, string $email, string $name, ?string $givenName = null): array
    {
        // Try to find existing user by Google ID
        $user = $this->userModel->findByGoogleId($googleId);

        if (!$user) {
            // Try to find by email
            $user = $this->userModel->findByEmail($email);

            if ($user) {
                // Link Google account to existing user
                $this->userModel->linkGoogleAccount($user['id'], $googleId);
            } else {
                // Create new user
                $userData = [
                    'email' => $email,
                    'full_name' => $name,
                    'date_of_birth' => '2000-01-01', // User must update later
                    'document_number' => 'pending', // User must update later
                    'country' => 'Brazil',
                    'institution' => 'Pending',
                    'category' => 'professional',
                    'google_id' => $googleId
                ];

                $userId = $this->userModel->create($userData);
                $user = $this->userModel->findById($userId);
            }
        }

        return $this->jwtService->generateToken($user);
    }

    /**
     * Refresh token
     */
    public function refreshToken(string $refreshToken): array
    {
        $tokens = $this->jwtService->refreshToken($refreshToken);

        if (!$tokens) {
            throw new \Exception('Refresh token inválido ou expirado');
        }

        return $tokens;
    }

    /**
     * Logout (blacklist token)
     */
    public function logout(string $token): void
    {
        $decoded = $this->jwtService->validateToken($token);
        if ($decoded && isset($decoded['exp'])) {
            $this->jwtService->blacklistToken($token, $decoded['exp']);
        }
    }

    /**
     * Get current authenticated user
     */
    public function me(array $userClaims): array
    {
        $user = $this->userModel->findById($userClaims['id']);
        
        if (!$user) {
            throw new \Exception('Usuário não encontrado');
        }

        // Remove sensitive data
        unset($user['password_hash']);

        return $user;
    }

    /**
     * Update user profile
     */
    public function updateProfile(int $userId, array $data): bool
    {
        $allowedFields = ['full_name', 'date_of_birth', 'document_number', 'country', 'institution', 'category'];
        $updateData = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            throw new \InvalidArgumentException('Nenhum campo válido para atualização');
        }

        return $this->userModel->update($userId, $updateData);
    }
}
