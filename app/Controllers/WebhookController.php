<?php

declare(strict_types=1);

namespace App\Controllers;

use NeoCore\Http\Request;
use NeoCore\Http\Api\ApiResponse;
use NeoCore\Webhooks\WebhookManager;

class WebhookController
{
    protected WebhookManager $webhookManager;

    public function __construct(WebhookManager $webhookManager)
    {
        $this->webhookManager = $webhookManager;
    }

    /**
     * List all webhooks
     */
    public function index(): \NeoCore\Http\JsonResponse
    {
        $webhooks = $this->webhookManager->all();
        return ApiResponse::success($webhooks);
    }

    /**
     * Register a new webhook
     */
    public function store(Request $request): \NeoCore\Http\JsonResponse
    {
        $name = $request->input('name');
        $url = $request->input('url');
        $event = $request->input('event');
        $method = $request->input('method', 'POST');
        $headers = $request->input('headers');
        $secret = $request->input('secret');

        if (!$name || !$url || !$event) {
            return ApiResponse::validationError([
                'name' => ['Name is required'],
                'url' => ['URL is required'],
                'event' => ['Event is required'],
            ]);
        }

        try {
            $webhook = $this->webhookManager->register(
                $name,
                $url,
                $event,
                $method,
                $headers,
                $secret
            );

            return ApiResponse::created($webhook, 'Webhook registered successfully');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    /**
     * Enable a webhook
     */
    public function enable(Request $request): \NeoCore\Http\JsonResponse
    {
        $id = (int) $request->route('id');

        if ($this->webhookManager->enable($id)) {
            return ApiResponse::success(null, 'Webhook enabled');
        }

        return ApiResponse::notFound('Webhook not found');
    }

    /**
     * Disable a webhook
     */
    public function disable(Request $request): \NeoCore\Http\JsonResponse
    {
        $id = (int) $request->route('id');

        if ($this->webhookManager->disable($id)) {
            return ApiResponse::success(null, 'Webhook disabled');
        }

        return ApiResponse::notFound('Webhook not found');
    }

    /**
     * Delete a webhook
     */
    public function destroy(Request $request): \NeoCore\Http\JsonResponse
    {
        $id = (int) $request->route('id');

        if ($this->webhookManager->unregister($id)) {
            return ApiResponse::success(null, 'Webhook deleted');
        }

        return ApiResponse::notFound('Webhook not found');
    }

    /**
     * Test a webhook
     */
    public function test(Request $request): \NeoCore\Http\JsonResponse
    {
        $id = (int) $request->route('id');

        $result = $this->webhookManager->test($id);

        if ($result['success']) {
            return ApiResponse::success($result, 'Webhook test successful');
        }

        return ApiResponse::error('Webhook test failed', 400, $result);
    }

    /**
     * Trigger webhooks for an event (manual trigger)
     */
    public function trigger(Request $request): \NeoCore\Http\JsonResponse
    {
        $event = $request->input('event');
        $payload = $request->input('payload', []);

        if (!$event) {
            return ApiResponse::validationError(['event' => ['Event is required']]);
        }

        $results = $this->webhookManager->trigger($event, $payload);

        return ApiResponse::success([
            'triggered' => count($results),
            'results' => $results,
        ], 'Webhooks triggered');
    }
}
