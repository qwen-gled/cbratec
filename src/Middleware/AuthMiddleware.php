<?php

namespace Src\Middleware;

use Src\Services\JwtService;

/**
 * Authentication Middleware - Validates JWT tokens
 */
class AuthMiddleware
{
    private JwtService $jwtService;

    public function __construct()
    {
        $this->jwtService = new JwtService();
    }

    /**
     * Handle authentication check
     * Returns user data if authenticated, null otherwise
     */
    public function handle(): ?array
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (empty($authHeader)) {
            return null;
        }

        // Extract token from "Bearer <token>" format
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return null;
        }

        $token = trim($matches[1]);
        return $this->jwtService->getUserFromToken($token);
    }

    /**
     * Require authentication - throws exception if not authenticated
     */
    public function requireAuth(): array
    {
        $user = $this->handle();
        
        if (!$user) {
            http_response_code(401);
            throw new \Exception('Não autorizado. Token inválido ou ausente.');
        }

        return $user;
    }

    /**
     * Require specific role
     */
    public function requireRole(array $allowedRoles): array
    {
        $user = $this->requireAuth();

        if (!in_array($user['role'], $allowedRoles)) {
            http_response_code(403);
            throw new \Exception('Acesso negado. Você não tem permissão para esta ação.');
        }

        return $user;
    }

    /**
     * Require admin role
     */
    public function requireAdmin(): array
    {
        return $this->requireRole(['admin']);
    }

    /**
     * Require moderator or admin role
     */
    public function requireModerator(): array
    {
        return $this->requireRole(['moderator', 'admin']);
    }

    /**
     * Check if user payment is approved
     */
    public function requirePaymentApproved(): array
    {
        $user = $this->requireAuth();

        if ($user['payment_status'] !== 'approved') {
            http_response_code(403);
            throw new \Exception('Pagamento pendente. Você precisa ter o pagamento aprovado para realizar esta ação.');
        }

        return $user;
    }
}
