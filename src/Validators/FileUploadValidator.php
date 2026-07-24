<?php

namespace Src\Validators;

/**
 * File Upload Validator - Validates uploaded files
 */
class FileUploadValidator
{
    private array $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/app.php';
    }

    /**
     * Validate uploaded file
     * @throws \Exception if validation fails
     */
    public function validate(array $file, string $type = 'abstract'): string
    {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception($this->getUploadErrorMessage($file['error']));
        }

        // Check if file was uploaded via HTTP POST
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new \Exception('Arquivo inválido');
        }

        // Validate file size
        $maxSize = $this->config['upload']['max_file_size'];
        if ($file['size'] > $maxSize) {
            throw new \Exception('Arquivo muito grande. Tamanho máximo: ' . ($maxSize / 1024 / 1024) . 'MB');
        }

        // Validate file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = $this->config['upload']['allowed_extensions'];
        
        if (!in_array($extension, $allowedExtensions)) {
            throw new \Exception('Formato de arquivo não permitido. Apenas PDF é aceito.');
        }

        // Validate MIME type (security check)
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        $allowedMimeTypes = ['application/pdf', 'application/x-pdf'];
        if (!in_array($mimeType, $allowedMimeTypes)) {
            throw new \Exception('Tipo de arquivo inválido. Apenas PDF é aceito.');
        }

        // Generate safe filename
        $safeName = $this->generateSafeName($extension);

        return $safeName;
    }

    /**
     * Generate a safe filename
     */
    private function generateSafeName(string $extension): string
    {
        return uniqid('abstract_', true) . '.' . $extension;
    }

    /**
     * Get human-readable error message for upload error code
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'Arquivo excede o limite do php.ini',
            UPLOAD_ERR_FORM_SIZE => 'Arquivo excede o limite do formulário',
            UPLOAD_ERR_PARTIAL => 'Upload parcial',
            UPLOAD_ERR_NO_FILE => 'Nenhum arquivo enviado',
            UPLOAD_ERR_NO_TMP_DIR => 'Erro no servidor',
            UPLOAD_ERR_CANT_WRITE => 'Erro ao escrever arquivo',
            UPLOAD_ERR_EXTENSION => 'Upload bloqueado por extensão PHP'
        ];

        return $errors[$errorCode] ?? 'Erro desconhecido no upload';
    }

    /**
     * Sanitize filename
     */
    public function sanitizeFilename(string $filename): string
    {
        // Remove any directory traversal attempts
        $filename = basename($filename);
        
        // Remove special characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        
        return $filename;
    }
}
