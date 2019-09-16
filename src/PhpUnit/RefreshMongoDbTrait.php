<?php

declare(strict_types=1);

namespace Hautelook\AliceBundle\PhpUnit;

/**
 * Purges and loads the fixtures before the first test and wraps all test in a transaction that will be roll backed when
 * it has finished.
 *
 */
trait RefreshMongoDbTrait
{
    use BaseMongoDbTrait;

    protected static function bootKernel(array $options = [])
    {
        static::ensureKernelTestCase();
        $kernel = parent::bootKernel($options);

        static::populateDatabase();
        return $kernel;
    }

    protected static function ensureKernelShutdown(): void
    {
        $container = static::$container ?? null;
        if (null === $container && null !== static::$kernel) {
            $container = static::$kernel->getContainer();
        }

        if (null !== $container) {
            static::purgeDatabase();
        }

        parent::ensureKernelShutdown();
    }
}
