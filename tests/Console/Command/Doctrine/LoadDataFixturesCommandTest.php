<?php

/*
 * This file is part of the Hautelook\AliceBundle package.
 *
 * (c) Baldur Rensch <brensch@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hautelook\AliceBundle\Console\Command\Doctrine;

use Doctrine\Common\Persistence\ManagerRegistry;
use Hautelook\AliceBundle\HttpKernel\DummyKernel;
use Hautelook\AliceBundle\Loader\FakeLoader;
use Hautelook\AliceBundle\LoaderInterface;
use Hautelook\AliceBundle\Persistence\FakeDoctrineManagerRegistry;
use Hautelook\AliceBundle\Persistence\ObjectMapper\FakeEntityManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Bundle\FrameworkBundle\Console\Application as FrameworkBundleConsoleApplication;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

/**
 * @covers \Hautelook\AliceBundle\Console\Command\Doctrine\DoctrineOdmLoadDataFixturesCommand
 */
class LoadDataFixturesCommandTest extends TestCase
{
    public function testIsACommand()
    {
        $this->assertTrue(is_a(DoctrineOdmLoadDataFixturesCommand::class, Command::class, true));
    }

    public function testCanSetTheCommandApplication()
    {
        $application = new FrameworkBundleConsoleApplication(new DummyKernel());

        $command = new DoctrineOdmLoadDataFixturesCommand('dummy', new FakeDoctrineManagerRegistry(), new FakeLoader());
        $command->setApplication($application);

        $this->assertSame($application, $command->getApplication());
    }

    public function testCanResetTheCommandApplication()
    {
        $command = new DoctrineOdmLoadDataFixturesCommand('dummy', new FakeDoctrineManagerRegistry(), new FakeLoader());
        $command->setApplication(null);

        $this->assertTrue(true);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected application to be an instance of "Symfony\Bundle\FrameworkBundle\Console\Application".
     */
    public function testThrowsAnExceptionIfInvalidApplicationIsGiven()
    {
        $command = new DoctrineOdmLoadDataFixturesCommand('dummy', new FakeDoctrineManagerRegistry(), new FakeLoader());
        $command->setApplication(new ConsoleApplication());
    }

    public function testCallCommandWithoutArguments()
    {
        $application = new FrameworkBundleConsoleApplication(new DummyKernel());
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'hautelook:fixtures:load',
        ]);
        $input->setInteractive(false);

        /** @var ManagerRegistry|ObjectProphecy $managerRegistryProphecy */
        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManager(null)->willReturn($manager = new FakeEntityManager());
        /** @var ManagerRegistry $managerRegistry */
        $managerRegistry = $managerRegistryProphecy->reveal();

        /** @var LoaderInterface|ObjectProphecy $loaderProphecy */
        $loaderProphecy = $this->prophesize(LoaderInterface::class);
        $loaderProphecy
            ->load($application, $manager, [], 'fake_env', false, false, null)
            ->shouldBeCalled()
        ;
        /** @var LoaderInterface $loader */
        $loader = $loaderProphecy->reveal();

        $command = new DoctrineOdmLoadDataFixturesCommand('dummy', $managerRegistry, $loader);
        $command->setApplication($application);
        $exit = $command->run($input, new NullOutput());

        $this->assertEquals(0, $exit);
        $loaderProphecy->load(Argument::cetera())->shouldHaveBeenCalledTimes(1);
        $managerRegistryProphecy->getManager(Argument::any())->shouldHaveBeenCalledTimes(1);
    }

    public function testCallCommandWithArguments()
    {
        $application = new FrameworkBundleConsoleApplication(new DummyKernel());
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'hautelook:fixtures:load',
            '--manager' => 'DummyManager',
            '--env' => 'dummy_env',
            '--bundle' => [
                'ABundle',
                'BBundle',
            ],
            '--shard' => 'shard_id',
            '--append' => null,
            '--purge-with-truncate' => null,
        ]);
        $input->setInteractive(false);

        /** @var ManagerRegistry|ObjectProphecy $managerRegistryProphecy */
        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManager('DummyManager')->willReturn($manager = new FakeEntityManager());
        /** @var ManagerRegistry $managerRegistry */
        $managerRegistry = $managerRegistryProphecy->reveal();

        /** @var LoaderInterface|ObjectProphecy $loaderProphecy */
        $loaderProphecy = $this->prophesize(LoaderInterface::class);
        $loaderProphecy
            ->load($application, $manager, ['ABundle', 'BBundle'], 'dummy_env', true, true, 'shard_id')
            ->shouldBeCalled();

        /** @var LoaderInterface $loader */
        $loader = $loaderProphecy->reveal();

        $command = new DoctrineOdmLoadDataFixturesCommand('dummy', $managerRegistry, $loader);
        $command->setApplication($application);
        $exit = $command->run($input, new NullOutput());

        $this->assertEquals(0, $exit);
        $loaderProphecy->load(Argument::cetera())->shouldHaveBeenCalledTimes(1);
        $managerRegistryProphecy->getManager(Argument::any())->shouldHaveBeenCalledTimes(1);
    }
}
