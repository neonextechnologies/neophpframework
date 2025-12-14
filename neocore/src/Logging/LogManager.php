<?php

declare(strict_types=1);

namespace NeoCore\Logging;

use NeoCore\Container\Container;

/**
 * Logger Manager
 */
class LogManager implements LoggerInterface
{
    protected Container $container;
    protected array $config;
    protected array $channels = [];

    public function __construct(Container $container, array $config = [])
    {
        $this->container = $container;
        $this->config = $config;
    }

    /**
     * Get a log channel
     */
    public function channel(?string $name = null): LoggerInterface
    {
        $name = $name ?? $this->getDefaultChannel();

        if (!isset($this->channels[$name])) {
            $this->channels[$name] = $this->createChannel($name);
        }

        return $this->channels[$name];
    }

    /**
     * Create a log channel
     */
    protected function createChannel(string $name): LoggerInterface
    {
        $config = $this->getChannelConfig($name);
        $driver = $config['driver'] ?? 'single';

        $method = 'create' . ucfirst($driver) . 'Driver';

        if (method_exists($this, $method)) {
            return $this->$method($config);
        }

        throw new \InvalidArgumentException("Log driver [{$driver}] is not supported.");
    }

    /**
     * Create single file driver
     */
    protected function createSingleDriver(array $config): LoggerInterface
    {
        return new FileLogger(
            $config['path'],
            LogLevel::fromString($config['level'] ?? 'debug')
        );
    }

    /**
     * Create daily file driver
     */
    protected function createDailyDriver(array $config): LoggerInterface
    {
        return new DailyFileLogger(
            $config['path'],
            LogLevel::fromString($config['level'] ?? 'debug'),
            $config['days'] ?? 14
        );
    }

    /**
     * Create database driver
     */
    protected function createDatabaseDriver(array $config): LoggerInterface
    {
        return new DatabaseLogger(
            $this->container->make('entityManager'),
            LogLevel::fromString($config['level'] ?? 'debug')
        );
    }

    /**
     * Create stack driver (multiple channels)
     */
    protected function createStackDriver(array $config): LoggerInterface
    {
        $channels = [];
        
        foreach ($config['channels'] as $channelName) {
            $channels[] = $this->channel($channelName);
        }

        return new StackLogger($channels);
    }

    /**
     * Create Slack driver
     */
    protected function createSlackDriver(array $config): LoggerInterface
    {
        return new SlackLogger(
            $config['url'],
            LogLevel::fromString($config['level'] ?? 'critical'),
            $config['username'] ?? 'Logger',
            $config['emoji'] ?? ':boom:'
        );
    }

    /**
     * Create email driver
     */
    protected function createEmailDriver(array $config): LoggerInterface
    {
        return new EmailLogger(
            $config['to'],
            LogLevel::fromString($config['level'] ?? 'error'),
            $config['subject'] ?? 'Application Log'
        );
    }

    /**
     * Get channel configuration
     */
    protected function getChannelConfig(string $name): array
    {
        if (!isset($this->config['channels'][$name])) {
            throw new \InvalidArgumentException("Log channel [{$name}] is not configured.");
        }

        return $this->config['channels'][$name];
    }

    /**
     * Get default channel
     */
    protected function getDefaultChannel(): string
    {
        return $this->config['default'] ?? 'single';
    }

    /**
     * Forward calls to default channel
     */
    public function emergency(string $message, array $context = []): void
    {
        $this->channel()->emergency($message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->channel()->alert($message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->channel()->critical($message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->channel()->error($message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->channel()->warning($message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->channel()->notice($message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->channel()->info($message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->channel()->debug($message, $context);
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $this->channel()->log($level, $message, $context);
    }
}
