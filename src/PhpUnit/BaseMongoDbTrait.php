<?php

declare(strict_types=1);

namespace Hautelook\AliceBundle\PhpUnit;

use Doctrine\ODM\MongoDB\DocumentManager;
use LogicException;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Container;

trait BaseMongoDbTrait
{
    /**
     * @var string|null The name of the Doctrine manager to use
     */
    protected static $manager;

    /**
     * @var string[] The list of bundles where to look for fixtures
     */
    protected static $bundles = [];

    /**
     * @var bool Append fixtures instead of purging
     */
    protected static $append = false;

    /**
     * @var bool Use TRUNCATE to purge
     */
    protected static $purgeWithTruncate = true;

    /**
     * @var string|null The name of the Doctrine shard to use
     */
    protected static $shard;

    /**
     * @var string|null The name of the Doctrine connection to use
     */
    protected static $connection;

    /**
     * @var array|null Contain loaded fixture from alice
     */
    protected static $fixtures;

    protected static function ensureKernelTestCase(): void
    {
        if (!is_a(static::class, KernelTestCase::class, true)) {
            throw new LogicException(
                sprintf(
                    'The test class must extend "%s" to use "%s".',
                    KernelTestCase::class,
                    static::class
                )
            );
        }
    }

    protected static function purgeDatabase(): void
    {
        /** @var Container $container */
        $container = static::$container ?? static::$kernel->getContainer();
        /** @var DocumentManager $manager */
        $manager = $container->get('doctrine_mongodb.odm.document_manager');
        $db = $manager->getConfiguration()->getDefaultDB();
        if (!$db) {
            throw new RuntimeException('Database name couldn\'t be retrieved.');
        }
        $collectionIterator = $manager->getClient()
            ->selectDatabase($db)
            ->listCollections();
        foreach ($collectionIterator as $collection) {
            $manager->getClient()->selectDatabase($db)->dropCollection(
                $collection->getName()
            );
        }
    }

    protected static function populateDatabase(): void
    {
        $container = static::$container ?? static::$kernel->getContainer();
        $manager = $container->get('doctrine_mongodb.odm.document_manager');
        $loader = $container->get('hautelook_alice.loader');
        static::$fixtures = $loader->load(
            new Application(static::$kernel),
            // OK this is ugly... But there is no other way without redesigning LoaderInterface from the ground.
            $manager,
            static::$bundles,
            static::$kernel->getEnvironment(),
            static::$append,
            static::$purgeWithTruncate,
            static::$shard
        );
    }
}
