<?php

/*
 * This file is part of the Hautelook\AliceBundle package.
 *
 * (c) Baldur Rensch <brensch@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hautelook\AliceBundle\DependencyInjection;

use Fidry\AliceDataFixtures\Bridge\Symfony\FidryAliceDataFixturesBundle;
use Hautelook\AliceBundle\Functional\AppKernel;
use Hautelook\AliceBundle\Functional\ConfigurableKernel;
use Hautelook\AliceBundle\Functional\WithoutDoctrineKernel;
use Nelmio\Alice\Bridge\Symfony\NelmioAliceBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @covers \Hautelook\AliceBundle\HautelookAliceBundle
 * @covers \Hautelook\AliceBundle\DependencyInjection\Configuration
 * @covers \Hautelook\AliceBundle\DependencyInjection\HautelookAliceExtension
 */
class HautelookAliceBundleTest extends TestCase
{
    /**
     * @var KernelInterface|null
     */
    protected $kernel;

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        if (null !== $this->kernel) {
            $this->kernel->shutdown();
        }
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage To register "Hautelook\AliceBundle\HautelookAliceBundle", you also need: "Doctrine\Bundle\DoctrineBundle\DoctrineBundle", "Fidry\AliceDataFixtures\Bridge\Symfony\FidryAliceDataFixturesBundle".
     */
    public function testCannotBootIfFidryAliceDataFixturesBundleIsNotRegistered()
    {
        $this->kernel = new ConfigurableKernel('ConfigurableKernel0', true);
        $this->kernel->boot();
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage To register "Hautelook\AliceBundle\HautelookAliceBundle", you also need: "Doctrine\Bundle\DoctrineBundle\DoctrineBundle".
     */
    public function testWillReplaceFixtureLoadCommandWithErrorInformationCommandIfDoctrineBundleIsNotRegistered()
    {
        $this->kernel = new WithoutDoctrineKernel('ConfigurableKernel1', true);
        $this->kernel->addBundle(new FidryAliceDataFixturesBundle());
        $this->kernel->addBundle(new NelmioAliceBundle());
        $this->kernel->boot();
    }

    public function testServiceRegistration()
    {
        $this->kernel = new AppKernel('public', true);
        $this->kernel->boot();

        // Resolvers
        $this->assertInstanceOf(
            \Hautelook\AliceBundle\Resolver\Bundle\SimpleBundleResolver::class,
            $this->kernel->getContainer()->get('hautelook_alice.resolver.bundle.simple_resolver')
        );

        $this->assertInstanceOf(
            \Hautelook\AliceBundle\Resolver\Bundle\NoBundleResolver::class,
            $this->kernel->getContainer()->get('hautelook_alice.resolver.bundle.no_bundle_resolver')
        );

        $this->assertInstanceOf(
            \Hautelook\AliceBundle\BundleResolverInterface::class,
            $this->kernel->getContainer()->get('hautelook_alice.resolver.bundle')
        );

        // Locators
        $this->assertInstanceOf(
            \Hautelook\AliceBundle\Locator\EnvironmentlessFilesLocator::class,
            $this->kernel->getContainer()->get('hautelook_alice.locator.environmentless')
        );

        $this->assertInstanceOf(
            \Hautelook\AliceBundle\Locator\EnvDirectoryLocator::class,
            $this->kernel->getContainer()->get('hautelook_alice.locator.env_directory')
        );

        $this->assertInstanceOf(
            \Hautelook\AliceBundle\FixtureLocatorInterface::class,
            $this->kernel->getContainer()->get('hautelook_alice.locator')
        );

        // Loader
        $this->assertInstanceOf(
            \Fidry\AliceDataFixtures\Loader\FileResolverLoader::class,
            $this->kernel->getContainer()->get('hautelook_alice.data_fixtures.loader.file_resolver_loader')
        );

        $this->assertInstanceOf(
            \Fidry\AliceDataFixtures\LoaderInterface::class,
            $this->kernel->getContainer()->get('hautelook_alice.data_fixtures.purge_loader')
        );

        $this->assertInstanceOf(
            \Fidry\AliceDataFixtures\LoaderInterface::class,
            $this->kernel->getContainer()->get('hautelook_alice.data_fixtures.append_loader')
        );

        $this->assertInstanceOf(
            \Hautelook\AliceBundle\Loader\DoctrineOdmLoader::class,
            $this->kernel->getContainer()->get('hautelook_alice.loader.doctrine_odm_loader')
        );

        $this->assertInstanceOf(
            \Hautelook\AliceBundle\LoaderInterface::class,
            $this->kernel->getContainer()->get('hautelook_alice.loader')
        );

        // Commands
        $this->assertInstanceOf(
            \Hautelook\AliceBundle\Console\Command\Doctrine\DoctrineOrmLoadDataFixturesCommand::class,
            $this->kernel->getContainer()->get('hautelook_alice.console.command.doctrine.doctrine_orm_load_data_fixtures_command')
        );
    }
}
