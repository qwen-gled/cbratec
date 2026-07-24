<?php
/**
 * Application Configuration
 */

return [
    'app_name' => 'Congress Management System',
    'base_url' => getenv('APP_URL') ?: 'http://localhost',
    
    // JWT Configuration
    'jwt' => [
        'secret_key' => getenv('JWT_SECRET') ?: 'change-this-secret-key-in-production',
        'issuer' => 'congress-system',
        'audience' => 'congress-api',
        'token_expiry' => 3600, // 1 hour
        'refresh_token_expiry' => 604800, // 7 days
    ],
    
    // Google OAuth Configuration
    'google_oauth' => [
        'client_id' => getenv('GOOGLE_CLIENT_ID') ?: '',
        'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: '',
        'redirect_uri' => getenv('GOOGLE_REDIRECT_URI') ?: 'http://localhost/auth/google/callback',
        'scopes' => ['email', 'profile'],
    ],
    
    // Email Configuration (PHPMailer)
    'mail' => [
        'host' => getenv('MAIL_HOST') ?: 'smtp.gmail.com',
        'port' => getenv('MAIL_PORT') ?: 587,
        'username' => getenv('MAIL_USERNAME') ?: '',
        'password' => getenv('MAIL_PASSWORD') ?: '',
        'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
        'from_address' => getenv('MAIL_FROM_ADDRESS') ?: 'noreply@congress.com',
        'from_name' => getenv('MAIL_FROM_NAME') ?: 'Congress System',
    ],
    
    // File Upload Configuration
    'upload' => [
        'max_file_size' => 10 * 1024 * 1024, // 10MB
        'allowed_extensions' => ['pdf'],
        'abstracts_path' => __DIR__ . '/../public/uploads/abstracts/',
        'payment_proofs_path' => __DIR__ . '/../public/uploads/payment_proofs/',
    ],
    
    // Security
    'security' => [
        'csrf_token_name' => 'csrf_token',
        'password_min_length' => 8,
    ],
];
