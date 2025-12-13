<?php

/**
 * ORM Service - Cycle ORM Integration
 * 
 * Provides easy access to Cycle ORM components
 */

namespace NeoCore\System\Core;

use Cycle\Database\DatabaseManager;
use Cycle\Database\Config\DatabaseConfig;
use Cycle\ORM\ORM;
use Cycle\ORM\Factory;
use Cycle\ORM\Schema;
use Cycle\Annotated;
use Spiral\Tokenizer\ClassLocator;
use Symfony\Component\Finder\Finder;

class ORMService
{
    private static ?DatabaseManager $dbal = null;
    private static ?ORM $orm = null;
    private static array $config = [];

    /**
     * Initialize ORM with configuration
     */
    public static function init(array $config): void
    {
        self::$config = $config;
    }

    /**
     * Get Database Manager (DBAL)
     */
    public static function getDBAL(): DatabaseManager
    {
        if (self::$dbal === null) {
            $config = self::$config['default'] ?? [];
            
            $databaseConfig = new DatabaseConfig([
                'default' => 'default',
                'databases' => [
                    'default' => [
                        'connection' => 'default'
                    ]
                ],
                'connections' => [
                    'default' => [
                        'driver' => $config['driver'] ?? 'mysql',
                        'connection' => sprintf(
                            '%s:host=%s;port=%d;dbname=%s',
                            $config['driver'] ?? 'mysql',
                            $config['host'] ?? 'localhost',
                            $config['port'] ?? 3306,
                            $config['database'] ?? 'neocore'
                        ),
                        'username' => $config['username'] ?? 'root',
                        'password' => $config['password'] ?? '',
                        'options' => [
                            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                        ]
                    ]
                ]
            ]);

            self::$dbal = new DatabaseManager($databaseConfig);
        }

        return self::$dbal;
    }

    /**
     * Get ORM instance
     */
    public static function getORM(): ORM
    {
        if (self::$orm === null) {
            $dbal = self::getDBAL();
            
            // Get schema from cache or generate
            $schema = self::getSchema();
            
            self::$orm = new ORM(new Factory($dbal), $schema);
        }

        return self::$orm;
    }

    /**
     * Get or generate ORM schema
     */
    private static function getSchema(): Schema
    {
        $cacheFile = STORAGE_PATH . '/cache/orm_schema.php';

        // Load from cache in production
        if (file_exists($cacheFile) && !(self::$config['debug'] ?? false)) {
            $schemaArray = require $cacheFile;
            return new Schema($schemaArray);
        }

        // Generate schema
        $schema = self::generateSchema();
        
        // Cache schema
        if (!is_dir(dirname($cacheFile))) {
            mkdir(dirname($cacheFile), 0755, true);
        }
        file_put_contents($cacheFile, '<?php return ' . var_export($schema->toArray(), true) . ';');

        return $schema;
    }

    /**
     * Generate ORM schema from Entity classes
     */
    private static function generateSchema(): Schema
    {
        $dbal = self::getDBAL();
        
        $finder = (new Finder())->files()->in([
            BASE_PATH . '/app/Entities',
            BASE_PATH . '/modules'
        ])->name('*.php');

        $classLocator = new ClassLocator($finder);
        
        $schemaCompiler = new Annotated\Embeddings(
            new Annotated\Entities($classLocator)
        );

        return new Schema(
            (new \Cycle\Schema\Compiler())->compile(
                new \Cycle\Schema\Registry($dbal),
                [
                    $schemaCompiler,
                    new \Cycle\Schema\Generator\ResetTables(),
                    new \Cycle\Schema\Generator\GenerateRelations(),
                    new \Cycle\Schema\Generator\GenerateModifiers(),
                    new \Cycle\Schema\Generator\ValidateEntities(),
                    new \Cycle\Schema\Generator\RenderTables(),
                    new \Cycle\Schema\Generator\RenderRelations(),
                    new \Cycle\Schema\Generator\RenderModifiers(),
                    new \Cycle\Schema\Generator\SyncTables(),
                    new \Cycle\Schema\Generator\GenerateTypecast(),
                ]
            )
        );
    }

    /**
     * Get entity repository
     */
    public static function getRepository(string $entity): \Cycle\ORM\RepositoryInterface
    {
        return self::getORM()->getRepository($entity);
    }

    /**
     * Create entity manager (for transactions)
     */
    public static function getEntityManager(): \Cycle\ORM\EntityManagerInterface
    {
        return new \Cycle\ORM\EntityManager(self::getORM());
    }

    /**
     * Clear schema cache
     */
    public static function clearCache(): void
    {
        $cacheFile = STORAGE_PATH . '/cache/orm_schema.php';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }
}
