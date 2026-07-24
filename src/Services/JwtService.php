<?php

namespace Src\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Src\Models\User;
use DateTime;

/**
 * JWT Authentication Service
 */
class JwtService
{
    private User $userModel;
    private string $secretKey;
    private string $issuer;
    private string $audience;
    private int $tokenExpiry;
    private int $refreshTokenExpiry;

    public function __construct()
    {
        $this->userModel = new User();
        $config = require __DIR__ . '/../../config/app.php';
        
        $this->secretKey = $config['jwt']['secret_key'];
        $this->issuer = $config['jwt']['issuer'];
        $this->audience = $config['jwt']['audience'];
        $this->tokenExpiry = $config['jwt']['token_expiry'];
        $this->refreshTokenExpiry = $config['jwt']['refresh_token_expiry'];
    }

    /**
     * Generate JWT token for a user
     */
    public function generateToken(array $user): array
    {
        $issuedAt = time();
        $expirationTime = $issuedAt + $this->tokenExpiry;

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'data' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role'],
                'full_name' => $user['full_name'],
                'payment_status' => $user['payment_status']
            ]
        ];

        $token = JWT::encode($payload, $this->secretKey, 'HS256');

        // Generate refresh token
        $refreshToken = bin2hex(random_bytes(32));
        $this->storeRefreshToken($user['id'], $refreshToken);

        return [
            'access_token' => $token,
            'refresh_token' => $refreshToken,
            'expires_in' => $this->tokenExpiry,
            'token_type' => 'Bearer'
        ];
    }

    /**
     * Validate and decode JWT token
     */
    public function validateToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            
            // Check if token is blacklisted
            if ($this->isBlacklisted($token)) {
                return null;
            }

            return (array) $decoded;
        } catch (\Exception $e) {
            error_log("JWT validation failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Refresh access token using refresh token
     */
    public function refreshToken(string $refreshToken): ?array
    {
        // Verify refresh token exists and is valid
        $stored = $this->getRefreshToken($refreshToken);
        
        if (!$stored || new \DateTime() > new \DateTime($stored['expires_at'])) {
            return null;
        }

        // Get user data
        $user = $this->userModel->findById((int) $stored['user_id']);
        if (!$user) {
            return null;
        }

        // Invalidate old refresh token
        $this->invalidateRefreshToken($refreshToken);

        // Generate new tokens
        return $this->generateToken($user);
    }

    /**
     * Blacklist a token (logout)
     */
    public function blacklistToken(string $token, int $expiryTimestamp): void
    {
        $db = \Src\Helpers\Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "INSERT INTO jwt_blacklist (token, expires_at) VALUES (:token, :expires_at)"
        );
        $stmt->execute([
            'token' => $token,
            'expires_at' => date('Y-m-d H:i:s', $expiryTimestamp)
        ]);
    }

    /**
     * Check if token is blacklisted
     */
    private function isBlacklisted(string $token): bool
    {
        $db = \Src\Helpers\Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "SELECT id FROM jwt_blacklist WHERE token = :token AND expires_at > NOW()"
        );
        $stmt->execute(['token' => $token]);
        return $stmt->fetch() !== false;
    }

    /**
     * Store refresh token
     */
    private function storeRefreshToken(int $userId, string $token): void
    {
        $db = \Src\Helpers\Database::getInstance()->getConnection();
        $expiresAt = date('Y-m-d H:i:s', time() + $this->refreshTokenExpiry);
        
        $stmt = $db->prepare(
            "INSERT INTO user_refresh_tokens (user_id, token, expires_at) 
             VALUES (:user_id, :token, :expires_at)"
        );
        $stmt->execute([
            'user_id' => $userId,
            'token' => hash('sha256', $token),
            'expires_at' => $expiresAt
        ]);
    }

    /**
     * Get refresh token data
     */
    private function getRefreshToken(string $token): ?array
    {
        $db = \Src\Helpers\Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "SELECT * FROM user_refresh_tokens 
             WHERE token = :token AND expires_at > NOW()"
        );
        $stmt->execute(['token' => hash('sha256', $token)]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Invalidate refresh token
     */
    private function invalidateRefreshToken(string $token): void
    {
        $db = \Src\Helpers\Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "DELETE FROM user_refresh_tokens WHERE token = :token"
        );
        $stmt->execute(['token' => hash('sha256', $token)]);
    }

    /**
     * Extract user data from validated token
     */
    public function getUserFromToken(string $token): ?array
    {
        $decoded = $this->validateToken($token);
        if (!$decoded || !isset($decoded['data'])) {
            return null;
        }

        return $decoded['data'];
    }
}
