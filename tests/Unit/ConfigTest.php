<?php

/**
 * Example Unit Test - Config Test
 */

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use NeoCore\System\Core\Config;

class ConfigTest extends TestCase
{
    private string $configPath;

    protected function setUp(): void
    {
        $this->configPath = BASE_PATH . '/config';
    }

    public function testCanLoadConfig(): void
    {
        $config = Config::load('app', $this->configPath);
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('name', $config);
    }

    public function testCanGetConfigValue(): void
    {
        $value = Config::get('app.name', '', $this->configPath);
        
        $this->assertIsString($value);
        $this->assertNotEmpty($value);
    }

    public function testCanGetNestedConfigValue(): void
    {
        $value = Config::get('database.default.driver', '', $this->configPath);
        
        $this->assertIsString($value);
    }

    public function testReturnsDefaultWhenKeyNotFound(): void
    {
        $value = Config::get('nonexistent.key', 'default', $this->configPath);
        
        $this->assertEquals('default', $value);
    }

    public function testCanSetConfigValue(): void
    {
        Config::set('app.test', 'test_value');
        $value = Config::get('app.test', '', $this->configPath);
        
        $this->assertEquals('test_value', $value);
    }
}
