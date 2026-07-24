<?php

namespace Src\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Email Service - Handles sending notifications
 */
class EmailService
{
    private array $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/app.php';
    }

    /**
     * Send email notification for abstract status change
     */
    public function sendAbstractStatusNotification(
        string $toEmail,
        string $toName,
        string $abstractTitle,
        string $newStatus,
        ?string $justification = null
    ): bool {
        $subject = "Atualização de Status do seu Resumo: {$abstractTitle}";
        
        $statusLabels = [
            'pending' => 'Pendente de avaliação',
            'accepted' => 'ACEITO',
            'rejected' => 'RECUSADO',
            'accepted_with_corrections' => 'Aceito com correções',
            'pending_revision' => 'Pendente de revisão'
        ];

        $statusLabel = $statusLabels[$newStatus] ?? $newStatus;
        
        $body = $this->buildAbstractStatusEmailBody(
            $toName,
            $abstractTitle,
            $statusLabel,
            $justification
        );

        return $this->send($toEmail, $toName, $subject, $body);
    }

    /**
     * Send payment approval/rejection notification
     */
    public function sendPaymentNotification(
        string $toEmail,
        string $toName,
        string $status
    ): bool {
        $subject = "Status do seu Pagamento - Congress Management System";
        
        if ($status === 'approved') {
            $body = "
                <p>Olá {$toName},</p>
                <p><strong>Seu pagamento foi APROVADO!</strong></p>
                <p>Agora você pode acessar o sistema de submissão de resumos.</p>
                <p>Atenciosamente,<br>Equipe do Congresso</p>
            ";
        } else {
            $body = "
                <p>Olá {$toName},</p>
                <p>Seu comprovante de pagamento foi analisado e <strong>não foi aprovado</strong>.</p>
                <p>Por favor, verifique os dados e envie um novo comprovante.</p>
                <p>Atenciosamente,<br>Equipe do Congresso</p>
            ";
        }

        return $this->send($toEmail, $toName, $subject, $body);
    }

    /**
     * Send welcome email after registration
     */
    public function sendWelcomeEmail(string $toEmail, string $toName): bool
    {
        $subject = "Bem-vindo ao Congress Management System";
        $body = "
            <p>Olá {$toName},</p>
            <p>Bem-vindo ao sistema de gerenciamento de congressos!</p>
            <p>Para completar seu cadastro e poder submeter resumos, é necessário realizar o pagamento da inscrição.</p>
            <p><strong>Dados para pagamento:</strong></p>
            <ul>
                <li>PIX: congresso@exemplo.com</li>
                <li>Valor Profissional: R$ 200,00</li>
                <li>Valor Estudante: R$ 100,00</li>
            </ul>
            <p>Após o pagamento, faça upload do comprovante no sistema.</p>
            <p>Atenciosamente,<br>Equipe do Congresso</p>
        ";

        return $this->send($toEmail, $toName, $subject, $body);
    }

    /**
     * Generic send method
     */
    private function send(string $toEmail, string $toName, string $subject, string $body): bool
    {
        // In development, log emails instead of sending
        if (getenv('APP_ENV') !== 'production') {
            error_log("Email would be sent to {$toEmail}: {$subject}");
            return true;
        }

        try {
            $mail = new PHPMailer(true);

            // Server settings
            $mail->isSMTP();
            $mail->Host = $this->config['mail']['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['mail']['username'];
            $mail->Password = $this->config['mail']['password'];
            $mail->SMTPSecure = $this->config['mail']['encryption'];
            $mail->Port = $this->config['mail']['port'];

            // Recipients
            $mail->setFrom($this->config['mail']['from_address'], $this->config['mail']['from_name']);
            $mail->addAddress($toEmail, $toName);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;

            return $mail->send();
        } catch (Exception $e) {
            error_log("Email sending failed: {$mail->ErrorInfo}");
            return false;
        }
    }

    /**
     * Build email body for abstract status change
     */
    private function buildAbstractStatusEmailBody(
        string $toName,
        string $abstractTitle,
        string $statusLabel,
        ?string $justification
    ): string {
        $body = "
            <p>Olá {$toName},</p>
            <p>O status do seu resumo <strong>\"{$abstractTitle}\"</strong> foi atualizado.</p>
            <p><strong>Novo status: {$statusLabel}</strong></p>
        ";

        if ($justification) {
            $body .= "
                <p><strong>Justificativa/Observações:</strong></p>
                <blockquote>{$justification}</blockquote>
            ";
        }

        if ($statusLabel === 'Aceito com correções') {
            $body .= "
                <p>Você deve corrigir o arquivo conforme as observações acima e reenviar pelo sistema.</p>
            ";
        } elseif ($statusLabel === 'Pendente de revisão') {
            $body .= "
                <p>Seu arquivo reenviado está aguardando nova avaliação do moderador.</p>
            ";
        } elseif ($statusLabel === 'ACEITO') {
            $body .= "
                <p>Parabéns! Seu resumo foi aceito para apresentação no congresso.</p>
            ";
        } elseif ($statusLabel === 'RECUSADO') {
            $body .= "
                <p>Você poderá enviar um NOVO arquivo para esta submissão, que não contará em seu limite de envios.</p>
            ";
        }

        $body .= "
            <p>Atenciosamente,<br>Equipe do Congresso</p>
        ";

        return $body;
    }
}
