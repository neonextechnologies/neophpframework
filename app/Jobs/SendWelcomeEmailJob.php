<?php

/**
 * Example Job - Send Welcome Email
 */

namespace App\Jobs;

use NeoCore\System\Core\Queue;

class SendWelcomeEmailJob
{
    public function handle(array $data): void
    {
        $email = $data['email'] ?? '';
        $name = $data['name'] ?? 'User';

        // Simulate sending email
        // In production, use PHPMailer or similar
        $to = $email;
        $subject = "Welcome to NeoCore!";
        $message = "Hello {$name},\n\nWelcome to our platform!";
        $headers = "From: noreply@neocore.dev\r\n";

        // Log instead of actually sending (for demo)
        logger("Sending welcome email to {$email}");
        
        // Uncomment to actually send
        // mail($to, $subject, $message, $headers);
    }
}
