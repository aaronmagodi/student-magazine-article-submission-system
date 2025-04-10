<?php
class EmailNotifier {
    private $mailer;
    
    public function __construct() {
        $this->mailer = new PHPMailer(true);
        $this->configureMailer();
    }
    
    private function configureMailer(): void {
        // SMTP Configuration
        $this->mailer->isSMTP();
        $this->mailer->Host = 'smtp.example.com';
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = 'noreply@university.edu';
        $this->mailer->Password = 'your-email-password';
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = 587;
        
        // From address
        $this->mailer->setFrom('noreply@university.edu', 'University Magazine');
        $this->mailer->addReplyTo('support@university.edu', 'Support Team');
    }
    
    public function sendPasswordResetEmail(string $to, string $name, string $resetLink): bool {
        try {
            $this->mailer->addAddress($to, $name);
            $this->mailer->Subject = 'Password Reset Request';
            
            // HTML email content
            $this->mailer->isHTML(true);
            $this->mailer->Body = "
                <h2>Password Reset</h2>
                <p>Hello {$name},</p>
                <p>We received a request to reset your password. Click the link below to proceed:</p>
                <p><a href=\"{$resetLink}\">Reset Password</a></p>
                <p>This link will expire in 1 hour.</p>
                <p>If you didn't request this, please ignore this email.</p>
                <p>Best regards,<br>University Magazine Team</p>
            ";
            
            // Plain text version
            $this->mailer->AltBody = "Password Reset Link: {$resetLink}";
            
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Email error: " . $this->mailer->ErrorInfo);
            return false;
        }
    }
}