<?php

namespace NeoCore\System\Core;

/**
 * EventBus - Lightweight event dispatcher
 * 
 * Synchronous by default. No magic discovery.
 * Listeners must be registered explicitly.
 */
class EventBus
{
    private array $listeners = [];
    private array $eventLog = [];
    private bool $logging = false;

    /**
     * Register event listener
     */
    public function listen(string $eventName, $listener): void
    {
        if (!isset($this->listeners[$eventName])) {
            $this->listeners[$eventName] = [];
        }

        $this->listeners[$eventName][] = $listener;
    }

    /**
     * Register multiple listeners
     */
    public function listenMany(array $events): void
    {
        foreach ($events as $eventName => $listeners) {
            if (!is_array($listeners)) {
                $listeners = [$listeners];
            }

            foreach ($listeners as $listener) {
                $this->listen($eventName, $listener);
            }
        }
    }

    /**
     * Dispatch event (synchronous)
     */
    public function dispatch(string $eventName, $payload = null): void
    {
        if ($this->logging) {
            $this->eventLog[] = [
                'event' => $eventName,
                'payload' => $payload,
                'time' => microtime(true)
            ];
        }

        if (!isset($this->listeners[$eventName])) {
            return;
        }

        foreach ($this->listeners[$eventName] as $listener) {
            $this->executeListener($listener, $payload);
        }
    }

    /**
     * Execute listener
     */
    private function executeListener($listener, $payload): void
    {
        try {
            if (is_callable($listener)) {
                // Direct callable
                $listener($payload);
            } elseif (is_string($listener) && class_exists($listener)) {
                // Class name
                $instance = new $listener();
                if (method_exists($instance, 'handle')) {
                    $instance->handle($payload);
                }
            } elseif (is_array($listener) && count($listener) === 2) {
                // [Class, method] array
                [$class, $method] = $listener;
                $instance = is_object($class) ? $class : new $class();
                $instance->$method($payload);
            }
        } catch (\Exception $e) {
            error_log("EventBus listener error: " . $e->getMessage());
        }
    }

    /**
     * Check if event has listeners
     */
    public function hasListeners(string $eventName): bool
    {
        return isset($this->listeners[$eventName]) && count($this->listeners[$eventName]) > 0;
    }

    /**
     * Get listeners for event
     */
    public function getListeners(string $eventName): array
    {
        return $this->listeners[$eventName] ?? [];
    }

    /**
     * Remove listeners for event
     */
    public function removeListeners(string $eventName): void
    {
        unset($this->listeners[$eventName]);
    }

    /**
     * Remove all listeners
     */
    public function clearListeners(): void
    {
        $this->listeners = [];
    }

    /**
     * Enable event logging
     */
    public function enableLogging(): void
    {
        $this->logging = true;
    }

    /**
     * Disable event logging
     */
    public function disableLogging(): void
    {
        $this->logging = false;
    }

    /**
     * Get event log
     */
    public function getEventLog(): array
    {
        return $this->eventLog;
    }

    /**
     * Clear event log
     */
    public function clearEventLog(): void
    {
        $this->eventLog = [];
    }
}
