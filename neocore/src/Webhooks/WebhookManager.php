<?php

declare(strict_types=1);

namespace NeoCore\Webhooks;

use App\Entities\Webhook;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\EntityManagerInterface;

/**
 * Webhook Manager
 * 
 * Manage and trigger webhooks
 */
class WebhookManager
{
    protected ORMInterface $orm;
    protected EntityManagerInterface $entityManager;

    public function __construct(ORMInterface $orm, EntityManagerInterface $entityManager)
    {
        $this->orm = $orm;
        $this->entityManager = $entityManager;
    }

    /**
     * Trigger webhooks for an event
     */
    public function trigger(string $event, array $payload): array
    {
        $webhooks = $this->getWebhooksForEvent($event);
        $results = [];

        foreach ($webhooks as $webhook) {
            $results[] = $this->triggerWebhook($webhook, $payload);
        }

        return $results;
    }

    /**
     * Trigger a single webhook
     */
    public function triggerWebhook(Webhook $webhook, array $payload): array
    {
        $webhook->markTriggered();
        $this->entityManager->persist($webhook)->run();

        try {
            $response = $this->sendRequest($webhook, $payload);

            if ($response['success']) {
                $webhook->markSuccess();
                $webhook->resetFailures();
            } else {
                $webhook->markFailed();
            }

            $this->entityManager->persist($webhook)->run();

            return $response;
        } catch (\Exception $e) {
            $webhook->markFailed();
            $this->entityManager->persist($webhook)->run();

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'webhook_id' => $webhook->id,
            ];
        }
    }

    /**
     * Send HTTP request to webhook URL
     */
    protected function sendRequest(Webhook $webhook, array $payload): array
    {
        $ch = curl_init();

        // Prepare headers
        $headers = $webhook->headers ?? [];
        $headers['Content-Type'] = 'application/json';

        // Add signature if secret is set
        if ($webhook->secret) {
            $signature = $this->generateSignature($payload, $webhook->secret);
            $headers['X-Webhook-Signature'] = $signature;
        }

        // Convert headers to curl format
        $curlHeaders = [];
        foreach ($headers as $key => $value) {
            $curlHeaders[] = "{$key}: {$value}";
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $webhook->url,
            CURLOPT_CUSTOMREQUEST => $webhook->method,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $curlHeaders,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'error' => $error,
                'webhook_id' => $webhook->id,
            ];
        }

        $success = $statusCode >= 200 && $statusCode < 300;

        return [
            'success' => $success,
            'status_code' => $statusCode,
            'response' => $response,
            'webhook_id' => $webhook->id,
        ];
    }

    /**
     * Generate HMAC signature
     */
    protected function generateSignature(array $payload, string $secret): string
    {
        return hash_hmac('sha256', json_encode($payload), $secret);
    }

    /**
     * Verify webhook signature
     */
    public static function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Get webhooks for event
     */
    protected function getWebhooksForEvent(string $event): array
    {
        $repository = $this->orm->getRepository(Webhook::class);

        return $repository->select()
            ->where('event', $event)
            ->andWhere('active', true)
            ->fetchAll();
    }

    /**
     * Register a webhook
     */
    public function register(
        string $name,
        string $url,
        string $event,
        string $method = 'POST',
        ?array $headers = null,
        ?string $secret = null
    ): Webhook {
        $webhook = new Webhook();
        $webhook->name = $name;
        $webhook->url = $url;
        $webhook->event = $event;
        $webhook->method = $method;
        $webhook->headers = $headers;
        $webhook->secret = $secret ?? Webhook::generateSecret();

        $this->entityManager->persist($webhook)->run();

        return $webhook;
    }

    /**
     * Unregister a webhook
     */
    public function unregister(int $webhookId): bool
    {
        $repository = $this->orm->getRepository(Webhook::class);
        $webhook = $repository->findByPK($webhookId);

        if (!$webhook) {
            return false;
        }

        $this->entityManager->delete($webhook)->run();

        return true;
    }

    /**
     * Enable a webhook
     */
    public function enable(int $webhookId): bool
    {
        return $this->setActive($webhookId, true);
    }

    /**
     * Disable a webhook
     */
    public function disable(int $webhookId): bool
    {
        return $this->setActive($webhookId, false);
    }

    /**
     * Set webhook active status
     */
    protected function setActive(int $webhookId, bool $active): bool
    {
        $repository = $this->orm->getRepository(Webhook::class);
        $webhook = $repository->findByPK($webhookId);

        if (!$webhook) {
            return false;
        }

        $webhook->active = $active;
        $this->entityManager->persist($webhook)->run();

        return true;
    }

    /**
     * Get all webhooks
     */
    public function all(): array
    {
        $repository = $this->orm->getRepository(Webhook::class);
        return $repository->select()->fetchAll();
    }

    /**
     * Test a webhook
     */
    public function test(int $webhookId): array
    {
        $repository = $this->orm->getRepository(Webhook::class);
        $webhook = $repository->findByPK($webhookId);

        if (!$webhook) {
            return [
                'success' => false,
                'error' => 'Webhook not found',
            ];
        }

        $testPayload = [
            'event' => $webhook->event,
            'test' => true,
            'timestamp' => time(),
        ];

        return $this->triggerWebhook($webhook, $testPayload);
    }
}
