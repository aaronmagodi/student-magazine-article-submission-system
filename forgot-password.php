<?php
// includes/email.php

class EmailNotifier {
    public function sendPasswordResetEmail($email, $name, $resetLink) {
        $subject = "Password Reset Request - University Magazine";
        $message = "Hello $name,\n\n";
        $message .= "We received a request to reset your password. Click the link below to proceed:\n\n";
        $message .= "$resetLink\n\n";
        $message .= "This link will expire in 1 hour.\n";
        $message .= "If you didn't request this, please ignore this email.\n\n";
        $message .= "Best regards,\nUniversity Magazine Team";
        
        $headers = "From: " . EMAIL_FROM . "\r\n";
        $headers .= "Reply-To: " . EMAIL_FROM . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        return mail($email, $subject, $message, $headers);
    }
}