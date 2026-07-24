<?php

/**
 * API Entry Point - REST API Router
 * 
 * All requests are routed through this file.
 * Requires proper Authorization header with JWT token for protected endpoints.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'Src\\';
    $baseDir = __DIR__ . '/../../src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Load composer dependencies (for JWT, PHPMailer)
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require __DIR__ . '/../../vendor/autoload.php';
}

// Simple router
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = preg_replace('#/api/#', '', $path);

try {
    // Initialize middleware
    $authMiddleware = new Src\Middleware\AuthMiddleware();

    // Route matching
    switch (true) {
        // ============ AUTH ROUTES ============
        case $path === 'auth/register' && $method === 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $controller = new Src\Controllers\AuthController();
            $result = $controller->register($data);
            respondJson(['success' => true, 'data' => $result]);
            break;

        case $path === 'auth/login' && $method === 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $controller = new Src\Controllers\AuthController();
            $result = $controller->login($data['email'], $data['password']);
            respondJson(['success' => true, 'data' => $result]);
            break;

        case $path === 'auth/google/callback' && $method === 'GET':
            // Google OAuth callback handling would go here
            // This requires additional setup with Google Client Library
            respondJson(['error' => 'OAuth callback requires frontend integration'], 400);
            break;

        case $path === 'auth/me' && $method === 'GET':
            $user = $authMiddleware->requireAuth();
            $controller = new Src\Controllers\AuthController();
            $result = $controller->me($user);
            respondJson(['success' => true, 'data' => $result]);
            break;

        case $path === 'auth/logout' && $method === 'POST':
            $user = $authMiddleware->requireAuth();
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $token = str_replace('Bearer ', '', $authHeader);
            $controller = new Src\Controllers\AuthController();
            $controller->logout($token);
            respondJson(['success' => true, 'message' => 'Logout realizado com sucesso']);
            break;

        // ============ ABSTRACT ROUTES ============
        case $path === 'abstracts' && $method === 'GET':
            $user = $authMiddleware->requireAuth();
            $controller = new Src\Controllers\AbstractController();
            
            if ($user['role'] === 'admin') {
                $result = $controller->getAllAbstracts(
                    $_GET['status'] ?? null,
                    isset($_GET['area_id']) ? (int)$_GET['area_id'] : null
                );
            } elseif ($user['role'] === 'moderator') {
                $result = $controller->getModeratorAbstracts($user['id'], $_GET['status'] ?? null);
            } else {
                $result = $controller->getUserAbstracts($user['id']);
            }
            
            respondJson(['success' => true, 'data' => $result]);
            break;

        case $path === 'abstracts' && $method === 'POST':
            $user = $authMiddleware->requirePaymentApproved();
            
            // Handle file upload
            if (!isset($_FILES['file'])) {
                throw new \Exception('Arquivo não enviado');
            }
            
            $controller = new Src\Controllers\AbstractController();
            $abstractId = $controller->submit(
                $user['id'],
                $_POST,
                $_FILES['file']
            );
            
            respondJson(['success' => true, 'data' => ['id' => $abstractId]]);
            break;

        case preg_match('#^abstracts/(\d+)$#', $path, $matches) && $method === 'GET':
            $user = $authMiddleware->requireAuth();
            $controller = new Src\Controllers\AbstractController();
            $result = $controller->getAbstractDetails((int)$matches[1], $user['id']);
            respondJson(['success' => true, 'data' => $result]);
            break;

        case preg_match('#^abstracts/(\d+)/replace$#', $path, $matches) && $method === 'PUT':
            $user = $authMiddleware->requirePaymentApproved();
            
            if (!isset($_FILES['file'])) {
                throw new \Exception('Arquivo não enviado');
            }
            
            $controller = new Src\Controllers\AbstractController();
            $controller->replaceFile((int)$matches[1], $user['id'], $_FILES['file']);
            respondJson(['success' => true, 'message' => 'Arquivo substituído com sucesso']);
            break;

        case preg_match('#^abstracts/(\d+)/status$#', $path, $matches) && $method === 'PUT':
            $user = $authMiddleware->requireModerator();
            $data = json_decode(file_get_contents('php://input'), true);
            
            $controller = new Src\Controllers\AbstractController();
            $controller->updateStatus(
                (int)$matches[1],
                $data['status'],
                $data['justification'] ?? null,
                $user['id']
            );
            
            respondJson(['success' => true, 'message' => 'Status atualizado com sucesso']);
            break;

        case preg_match('#^abstracts/(\d+)$#', $path, $matches) && $method === 'DELETE':
            $user = $authMiddleware->requireAuth();
            $controller = new Src\Controllers\AbstractController();
            $controller->delete((int)$matches[1], $user['id']);
            respondJson(['success' => true, 'message' => 'Resumo excluído com sucesso']);
            break;

        // ============ ADMIN ROUTES ============
        case $path === 'admin/users' && $method === 'GET':
            $user = $authMiddleware->requireAdmin();
            $controller = new Src\Controllers\AdminController();
            $result = $controller->getAllUsers($_GET['role'] ?? null);
            respondJson(['success' => true, 'data' => $result]);
            break;

        case $path === 'admin/payments/pending' && $method === 'GET':
            $user = $authMiddleware->requireAdmin();
            $controller = new Src\Controllers\AdminController();
            $result = $controller->getPendingPayments();
            respondJson(['success' => true, 'data' => $result]);
            break;

        case preg_match('#^admin/payments/(\d+)$#', $path, $matches) && $method === 'PUT':
            $user = $authMiddleware->requireAdmin();
            $data = json_decode(file_get_contents('php://input'), true);
            
            $controller = new Src\Controllers\AdminController();
            $controller->processPayment((int)$matches[1], $data['status']);
            respondJson(['success' => true, 'message' => 'Pagamento processado com sucesso']);
            break;

        case $path === 'admin/areas' && $method === 'GET':
            $user = $authMiddleware->requireAuth();
            $controller = new Src\Controllers\AdminController();
            $result = $controller->getAllAreas();
            respondJson(['success' => true, 'data' => $result]);
            break;

        case $path === 'admin/areas' && $method === 'POST':
            $user = $authMiddleware->requireAdmin();
            $data = json_decode(file_get_contents('php://input'), true);
            
            $controller = new Src\Controllers\AdminController();
            $areaId = $controller->createArea($data['name'], $data['description'] ?? null);
            respondJson(['success' => true, 'data' => ['id' => $areaId]]);
            break;

        case $path === 'admin/settings' && $method === 'GET':
            $user = $authMiddleware->requireAdmin();
            $controller = new Src\Controllers\AdminController();
            $result = $controller->getSettings();
            respondJson(['success' => true, 'data' => $result]);
            break;

        case $path === 'admin/settings/deadline' && $method === 'PUT':
            $user = $authMiddleware->requireAdmin();
            $data = json_decode(file_get_contents('php://input'), true);
            
            $controller = new Src\Controllers\AdminController();
            $controller->setSubmissionDeadline($data['deadline']);
            respondJson(['success' => true, 'message' => 'Prazo atualizado com sucesso']);
            break;

        case $path === 'admin/stats' && $method === 'GET':
            $user = $authMiddleware->requireAdmin();
            $controller = new Src\Controllers\AdminController();
            $result = $controller->getDashboardStats();
            respondJson(['success' => true, 'data' => $result]);
            break;

        case $path === 'admin/moderators/assignments' && $method === 'GET':
            $user = $authMiddleware->requireAdmin();
            $controller = new Src\Controllers\AdminController();
            $result = $controller->getModeratorAssignments();
            respondJson(['success' => true, 'data' => $result]);
            break;

        case $path === 'admin/moderators/assign' && $method === 'POST':
            $user = $authMiddleware->requireAdmin();
            $data = json_decode(file_get_contents('php://input'), true);
            
            $controller = new Src\Controllers\AdminController();
            $controller->assignModerator((int)$data['area_id'], (int)$data['user_id']);
            respondJson(['success' => true, 'message' => 'Moderador vinculado com sucesso']);
            break;

        default:
            http_response_code(404);
            respondJson(['error' => 'Endpoint não encontrado']);
    }

} catch (\Exception $e) {
    $statusCode = http_response_code();
    if ($statusCode === 200) {
        http_response_code(400);
    }
    respondJson(['error' => $e->getMessage()], http_response_code());
}

/**
 * Helper function to send JSON response
 */
function respondJson(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}
