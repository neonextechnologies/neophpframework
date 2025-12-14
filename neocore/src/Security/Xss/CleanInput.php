<?php

declare(strict_types=1);

namespace NeoCore\Security\Xss;

use NeoCore\Http\Request;
use NeoCore\Http\Response;

/**
 * Clean Input Middleware
 * 
 * Automatically cleans request input to prevent XSS
 */
class CleanInput
{
    protected XssFilter $filter;
    protected array $except = [];
    protected bool $cleanGet = true;
    protected bool $cleanPost = true;

    public function __construct(XssFilter $filter)
    {
        $this->filter = $filter;
    }

    /**
     * Handle an incoming request
     */
    public function handle(Request $request, callable $next): Response
    {
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        $this->cleanRequest($request);

        return $next($request);
    }

    /**
     * Clean the request input
     */
    protected function cleanRequest(Request $request): void
    {
        if ($this->cleanGet) {
            $cleaned = $this->filter->clean($request->query->all());
            $request->query->replace($cleaned);
        }

        if ($this->cleanPost) {
            $cleaned = $this->filter->clean($request->request->all());
            $request->request->replace($cleaned);
        }
    }

    /**
     * Determine if the request should skip cleaning
     */
    protected function shouldSkip(Request $request): bool
    {
        foreach ($this->except as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set URIs that should be excluded from input cleaning
     */
    public function except(array $uris): self
    {
        $this->except = $uris;
        return $this;
    }
}
