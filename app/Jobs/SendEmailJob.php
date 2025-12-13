<?php

namespace App\Jobs;

/**
 * Example Job - Send Email
 */
class SendEmailJob
{
    /**
     * Handle the job
     */
    public function handle(array $data): void
    {
        $email = $data['email'] ?? null;
        $subject = $data['subject'] ?? 'No Subject';
        $message = $data['message'] ?? '';

        if (!$email) {
            throw new \Exception('Email address is required');
        }

        // Send email logic here
        // Example: mail($email, $subject, $message);
        
        // For demo, just log it
        logger("Email sent to: $email with subject: $subject", 'info');
    }
}
