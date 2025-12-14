<?php

declare(strict_types=1);

namespace NeoCore\Auth\Access;

use NeoCore\Container\Container;

/**
 * Gate
 * 
 * Provides authorization through abilities and policies
 */
class Gate
{
    protected Container $container;
    protected $user;
    protected array $abilities = [];
    protected array $policies = [];
    protected array $beforeCallbacks = [];
    protected array $afterCallbacks = [];

    public function __construct(Container $container, $user = null)
    {
        $this->container = $container;
        $this->user = $user;
    }

    /**
     * Define a new ability
     */
    public function define(string $ability, callable $callback): self
    {
        $this->abilities[$ability] = $callback;
        return $this;
    }

    /**
     * Register a policy
     */
    public function policy(string $class, string $policy): self
    {
        $this->policies[$class] = $policy;
        return $this;
    }

    /**
     * Register a callback to run before all checks
     */
    public function before(callable $callback): self
    {
        $this->beforeCallbacks[] = $callback;
        return $this;
    }

    /**
     * Register a callback to run after all checks
     */
    public function after(callable $callback): self
    {
        $this->afterCallbacks[] = $callback;
        return $this;
    }

    /**
     * Determine if the given ability should be granted
     */
    public function allows(string $ability, $arguments = []): bool
    {
        return $this->check($ability, $arguments);
    }

    /**
     * Determine if the given ability should be denied
     */
    public function denies(string $ability, $arguments = []): bool
    {
        return !$this->allows($ability, $arguments);
    }

    /**
     * Check if the given ability passes
     */
    public function check(string $ability, $arguments = []): bool
    {
        $arguments = is_array($arguments) ? $arguments : [$arguments];

        // Run before callbacks
        $result = $this->callBeforeCallbacks($ability, $arguments);
        if (!is_null($result)) {
            return $result;
        }

        // Check if ability is defined
        if (isset($this->abilities[$ability])) {
            $result = $this->callAbility($ability, $arguments);
        } else {
            // Try to resolve from policy
            $result = $this->callPolicyMethod($ability, $arguments);
        }

        // Run after callbacks
        return $this->callAfterCallbacks($ability, $arguments, $result);
    }

    /**
     * Check if any of the given abilities pass
     */
    public function any(array $abilities, $arguments = []): bool
    {
        foreach ($abilities as $ability) {
            if ($this->check($ability, $arguments)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Authorize a given ability or throw an exception
     */
    public function authorize(string $ability, $arguments = []): void
    {
        if ($this->denies($ability, $arguments)) {
            throw new AuthorizationException("This action is unauthorized.");
        }
    }

    /**
     * Get a gate instance for the given user
     */
    public function forUser($user): self
    {
        $gate = new static($this->container, $user);
        $gate->abilities = $this->abilities;
        $gate->policies = $this->policies;
        $gate->beforeCallbacks = $this->beforeCallbacks;
        $gate->afterCallbacks = $this->afterCallbacks;
        return $gate;
    }

    /**
     * Call before callbacks
     */
    protected function callBeforeCallbacks(string $ability, array $arguments): ?bool
    {
        foreach ($this->beforeCallbacks as $callback) {
            $result = $callback($this->user, $ability, $arguments);
            if (!is_null($result)) {
                return $result;
            }
        }
        return null;
    }

    /**
     * Call after callbacks
     */
    protected function callAfterCallbacks(string $ability, array $arguments, ?bool $result): bool
    {
        foreach ($this->afterCallbacks as $callback) {
            $afterResult = $callback($this->user, $ability, $arguments, $result);
            if (!is_null($afterResult)) {
                return $afterResult;
            }
        }
        return $result ?? false;
    }

    /**
     * Call an ability callback
     */
    protected function callAbility(string $ability, array $arguments): bool
    {
        $callback = $this->abilities[$ability];
        $result = $callback($this->user, ...$arguments);
        return $result === true;
    }

    /**
     * Call a policy method
     */
    protected function callPolicyMethod(string $ability, array $arguments): ?bool
    {
        if (empty($arguments)) {
            return null;
        }

        $model = $arguments[0];
        $class = is_object($model) ? get_class($model) : $model;

        if (!isset($this->policies[$class])) {
            return null;
        }

        $policy = $this->resolvePolicy($this->policies[$class]);

        // Call before method if exists
        if (method_exists($policy, 'before')) {
            $result = $policy->before($this->user, $ability);
            if (!is_null($result)) {
                return $result;
            }
        }

        // Call ability method
        if (!method_exists($policy, $ability)) {
            return null;
        }

        $result = $policy->$ability($this->user, ...$arguments);
        return $result === true;
    }

    /**
     * Resolve a policy instance
     */
    protected function resolvePolicy(string $policy)
    {
        return $this->container->make($policy);
    }

    /**
     * Get the user for the gate
     */
    public function getUser()
    {
        return $this->user;
    }
}
