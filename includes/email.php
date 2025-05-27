<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php'; // Require PHPMailer autoload

class EmailNotifier {
    private PHPMailer\PHPMailer\PHPMailer $mailer;
    private string $systemEmail;
    private string $supportEmail;
    
    public function __construct() {
        $this->mailer = new PHPMailer\PHPMailer\PHPMailer(true);
        $this->systemEmail = getSetting('system_email', 'noreply@university.edu');
        $this->supportEmail = getSetting('support_email', 'support@university.edu');
        $this->configureMailer();
    }
    
    private function configureMailer(): void {
        // Load SMTP configuration from settings
        $smtpConfig = [
            'host' => getSetting('smtp_host', 'smtp.example.com'),
            'username' => getSetting('smtp_username', 'noreply@university.edu'),
            'password' => getSetting('smtp_password', ''),
            'secure' => getSetting('smtp_secure', PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS),
            'port' => (int)getSetting('smtp_port', 587)
        ];

        // SMTP Configuration
        $this->mailer->isSMTP();
        $this->mailer->Host = $smtpConfig['host'];
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $smtpConfig['username'];
        $this->mailer->Password = $smtpConfig['password'];
        $this->mailer->SMTPSecure = $smtpConfig['secure'];
        $this->mailer->Port = $smtpConfig['port'];
        
        // Timeout settings
        $this->mailer->Timeout = 10;
        $this->mailer->SMTPKeepAlive = true;
        
        // From address configuration
        $this->mailer->setFrom($this->systemEmail, 'University Magazine System');
        $this->mailer->addReplyTo($this->supportEmail, 'Magazine Support Team');
        
        // Debugging level (0 = off, 1 = client messages, 2 = client and server messages)
        $this->mailer->SMTPDebug = 0;
    }
    
    public function sendPasswordResetEmail(string $to, string $name, string $resetLink): bool {
        try {
            $this->mailer->clearAddresses(); // Clear previous recipients
            $this->mailer->addAddress($to, $name);
            
            $this->mailer->Subject = 'University Magazine - Password Reset Request';
            
            // HTML email content with template
            $this->mailer->isHTML(true);
            $this->mailer->Body = $this->getEmailTemplate(
                'Password Reset Request',
                "<p>Hello {$name},</p>
                <p>We received a request to reset your password for the University Magazine System.</p>
                <div style='text-align: center; margin: 20px 0;'>
                    <a href='{$resetLink}' style='background-color: #3498db; color: white; 
                       padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
                        Reset Password
                    </a>
                </div>
                <p>This link will expire in 1 hour. If you didn't request this, please ignore this email.</p>
                <p>For security reasons, please don't share this link with anyone.</p>"
            );
            
            // Plain text version
            $this->mailer->AltBody = "Password Reset Link for University Magazine System:\n\n"
                . "Hello {$name},\n\n"
                . "We received a request to reset your password. Use this link to proceed:\n"
                . "{$resetLink}\n\n"
                . "This link will expire in 1 hour.\n\n"
                . "If you didn't request this, please ignore this email.";
            
            $sent = $this->mailer->send();
            
            if ($sent) {
                logActivity("Sent password reset email to {$to}", null, [
                    'type' => 'password_reset',
                    'recipient' => $to
                ]);
            }
            
            return $sent;
        } catch (Exception $e) {
            error_log("Password reset email error to {$to}: " . $this->mailer->ErrorInfo);
            logActivity("Failed to send password reset email", null, [
                'error' => $this->mailer->ErrorInfo,
                'recipient' => $to
            ]);
            return false;
        }
    }
    
    public function sendContributionNotification(string $to, string $name, int $contributionId, string $contributionTitle): bool {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to, $name);
            
            $this->mailer->Subject = "New Magazine Contribution: {$contributionTitle}";
            
            $reviewUrl = BASE_URL . "coordinator/review.php?id={$contributionId}";
            
            $this->mailer->isHTML(true);
            $this->mailer->Body = $this->getEmailTemplate(
                'New Contribution Submitted',
                "<p>Hello {$name},</p>
                <p>A new contribution has been submitted for your review:</p>
                <div style='background-color: #f8f9fa; padding: 15px; border-left: 4px solid #3498db; 
                    margin: 15px 0;'>
                    <strong>Title:</strong> {$contributionTitle}<br>
                    <strong>Submission Date:</strong> " . date('F j, Y g:i a') . "
                </div>
                <div style='text-align: center; margin: 20px 0;'>
                    <a href='{$reviewUrl}' style='background-color: #3498db; color: white; 
                       padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
                        Review Contribution
                    </a>
                </div>
                <p>Please review this submission within 14 days.</p>"
            );
            
            $this->mailer->AltBody = "New Contribution Notification\n\n"
                . "Hello {$name},\n\n"
                . "A new contribution has been submitted for your review:\n\n"
                . "Title: {$contributionTitle}\n"
                . "Submission Date: " . date('F j, Y g:i a') . "\n\n"
                . "Review URL: {$reviewUrl}\n\n"
                . "Please review this submission within 14 days.";
            
            $sent = $this->mailer->send();
            
            if ($sent) {
                logActivity("Sent contribution notification to {$to}", null, [
                    'type' => 'contribution_notification',
                    'contribution_id' => $contributionId,
                    'recipient' => $to
                ]);
            }
            
            return $sent;
        } catch (Exception $e) {
            error_log("Contribution notification email error to {$to}: " . $this->mailer->ErrorInfo);
            logActivity("Failed to send contribution notification", null, [
                'error' => $this->mailer->ErrorInfo,
                'contribution_id' => $contributionId,
                'recipient' => $to
            ]);
            return false;
        }
    }
    
    public function sendSelectionNotification(string $to, string $name, int $contributionId, string $contributionTitle): bool {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to, $name);
            
            $this->mailer->Subject = "Contribution Selected: {$contributionTitle}";
            
            $viewUrl = BASE_URL . "contribution.php?id={$contributionId}";
            
            $this->mailer->isHTML(true);
            $this->mailer->Body = $this->getEmailTemplate(
                'Contribution Selected for Publication',
                "<p>Hello {$name},</p>
                <p>We're pleased to inform you that your contribution has been selected for publication in the University Magazine:</p>
                <div style='background-color: #f8f9fa; padding: 15px; border-left: 4px solid #2ecc71; 
                    margin: 15px 0;'>
                    <strong>Title:</strong> {$contributionTitle}
                </div>
                <p>You can view your selected contribution at:</p>
                <p><a href='{$viewUrl}'>{$viewUrl}</a></p>
                <p>Congratulations and thank you for your contribution!</p>"
            );
            
            $this->mailer->AltBody = "Contribution Selected for Publication\n\n"
                . "Hello {$name},\n\n"
                . "We're pleased to inform you that your contribution has been selected for publication:\n\n"
                . "Title: {$contributionTitle}\n\n"
                . "View your contribution at: {$viewUrl}\n\n"
                . "Congratulations and thank you for your contribution!";
            
            $sent = $this->mailer->send();
            
            if ($sent) {
                logActivity("Sent selection notification to {$to}", null, [
                    'type' => 'selection_notification',
                    'contribution_id' => $contributionId,
                    'recipient' => $to
                ]);
            }
            
            return $sent;
        } catch (Exception $e) {
            error_log("Selection notification email error to {$to}: " . $this->mailer->ErrorInfo);
            logActivity("Failed to send selection notification", null, [
                'error' => $this->mailer->ErrorInfo,
                'contribution_id' => $contributionId,
                'recipient' => $to
            ]);
            return false;
        }
    }
    
    private function getEmailTemplate(string $title, string $content): string {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>{$title}</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #2c3e50; padding: 20px; text-align: center; }
                .header h1 { color: white; margin: 0; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .footer { margin-top: 20px; padding: 10px; text-align: center; 
                         font-size: 12px; color: #777; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>University Magazine System</h1>
                </div>
                <div class='content'>
                    <h2>{$title}</h2>
                    {$content}
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " University Magazine System. All rights reserved.</p>
                    <p>This is an automated message - please do not reply directly to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}