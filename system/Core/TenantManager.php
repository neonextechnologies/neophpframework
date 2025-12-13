<?php

namespace NeoCore\System\Core;

use PDO;

/**
 * TenantManager - Multi-tenant database manager
 * 
 * Detects tenant via subdomain, header, or domain mapping.
 * No magic binding. Explicit tenant detection.
 */
class TenantManager
{
    private ?string $currentTenant = null;
    private array $tenantDatabases = [];
    private array $config;
    private string $detectionMethod;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->detectionMethod = $config['detection_method'] ?? 'subdomain';
        $this->tenantDatabases = $config['tenants'] ?? [];
    }

    /**
     * Detect and set current tenant
     */
    public function detectTenant(Request $request): ?string
    {
        $tenant = null;

        switch ($this->detectionMethod) {
            case 'subdomain':
                $tenant = $this->detectFromSubdomain($request);
                break;
            
            case 'header':
                $tenant = $this->detectFromHeader($request);
                break;
            
            case 'domain':
                $tenant = $this->detectFromDomain($request);
                break;
        }

        $this->currentTenant = $tenant;
        return $tenant;
    }

    /**
     * Detect tenant from subdomain
     */
    private function detectFromSubdomain(Request $request): ?string
    {
        $host = $request->server('HTTP_HOST');
        
        if (!$host) {
            return null;
        }

        $parts = explode('.', $host);
        
        if (count($parts) < 3) {
            return null; // No subdomain
        }

        return $parts[0];
    }

    /**
     * Detect tenant from header
     */
    private function detectFromHeader(Request $request): ?string
    {
        $headerName = $this->config['tenant_header'] ?? 'X-Tenant-ID';
        return $request->getHeader($headerName);
    }

    /**
     * Detect tenant from domain mapping
     */
    private function detectFromDomain(Request $request): ?string
    {
        $host = $request->server('HTTP_HOST');
        $domainMap = $this->config['domain_map'] ?? [];

        return $domainMap[$host] ?? null;
    }

    /**
     * Get current tenant
     */
    public function getCurrentTenant(): ?string
    {
        return $this->currentTenant;
    }

    /**
     * Set current tenant manually
     */
    public function setCurrentTenant(string $tenant): void
    {
        $this->currentTenant = $tenant;
    }

    /**
     * Get tenant database connection
     */
    public function getTenantDatabase(string $tenant): PDO
    {
        if (!isset($this->tenantDatabases[$tenant])) {
            throw new \Exception("Tenant database not configured: $tenant");
        }

        $dbConfig = $this->tenantDatabases[$tenant];
        
        return $this->createConnection($dbConfig);
    }

    /**
     * Get current tenant database connection
     */
    public function getCurrentTenantDatabase(): ?PDO
    {
        if ($this->currentTenant === null) {
            return null;
        }

        return $this->getTenantDatabase($this->currentTenant);
    }

    /**
     * Create database connection
     */
    private function createConnection(array $config): PDO
    {
        $driver = $config['driver'] ?? 'mysql';
        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? 3306;
        $database = $config['database'];
        $username = $config['username'];
        $password = $config['password'];
        $charset = $config['charset'] ?? 'utf8mb4';

        $dsn = "$driver:host=$host;port=$port;dbname=$database;charset=$charset";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        return new PDO($dsn, $username, $password, $options);
    }

    /**
     * Check if tenant exists
     */
    public function tenantExists(string $tenant): bool
    {
        return isset($this->tenantDatabases[$tenant]);
    }

    /**
     * Register tenant database
     */
    public function registerTenant(string $tenant, array $dbConfig): void
    {
        $this->tenantDatabases[$tenant] = $dbConfig;
    }

    /**
     * Get all registered tenants
     */
    public function getAllTenants(): array
    {
        return array_keys($this->tenantDatabases);
    }

    /**
     * Validate tenant access
     */
    public function validateTenantAccess(?string $tenant = null): bool
    {
        $tenant = $tenant ?? $this->currentTenant;

        if ($tenant === null) {
            return false;
        }

        return $this->tenantExists($tenant);
    }

    /**
     * Get tenant configuration
     */
    public function getTenantConfig(string $tenant): ?array
    {
        return $this->tenantDatabases[$tenant] ?? null;
    }
}
