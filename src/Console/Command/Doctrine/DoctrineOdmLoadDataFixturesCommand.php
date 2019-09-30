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
use Hautelook\AliceBundle\Loader\DoctrineOdmLoader as AliceBundleLoaderInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Console\Application as FrameworkBundleConsoleApplication;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Command used to load the fixtures.
 */
class DoctrineOdmLoadDataFixturesCommand extends Command
{
    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @var AliceBundleLoaderInterface
     */
    private $loader;

    public function __construct(string $name, ManagerRegistry $managerRegistry, AliceBundleLoaderInterface $loader)
    {
        parent::__construct($name);

        $this->doctrine = $managerRegistry;
        $this->loader = $loader;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setAliases(['hautelook:fixtures:load'])
            ->setDescription('Load data fixtures to your database.')
            ->addOption(
                'bundle',
                'b',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Bundles where fixtures should be loaded.'
            )
            ->addOption(
                'manager',
                'm',
                InputOption::VALUE_REQUIRED,
                'The entity manager to use for this command. If not specified, use the default Doctrine fixtures entity'
                .'manager.'
            )
            ->addOption(
                'append',
                null,
                InputOption::VALUE_NONE,
                'Append the data fixtures instead of deleting all data from the database first.'
            )
            ->addOption(
                'shard',
                null,
                InputOption::VALUE_REQUIRED,
                'The shard database id to use for this command.'
            )
            ->addOption('purge-with-truncate',
                null,
                InputOption::VALUE_NONE,
                'Purge data by using a database-level TRUNCATE statement when using Doctrine fixtures.'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function setApplication(ConsoleApplication $application = null)
    {
        if (null !== $application && false === $application instanceof FrameworkBundleConsoleApplication) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Expected application to be an instance of "%s".',
                    FrameworkBundleConsoleApplication::class
                )
            );
        }

        parent::setApplication($application);
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException Unsupported Application type
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Warn the user that the database will be purged
        // Ask him to confirm his choice
        if ($input->isInteractive() && !$input->getOption('append')) {
            if (false === $this->askConfirmation(
                    $input,
                    $output,
                    '<question>Careful, database will be purged. Do you want to continue y/N ?</question>',
                    false
                )
            ) {
                return 0;
            }
        }

        $manager = $this->doctrine->getManager($input->getOption('manager'));
        $environment = $input->getOption('env');
        $bundles = $input->getOption('bundle');
        $shard = $input->getOption('shard');
        $append = $input->getOption('append');
        $truncate = $input->getOption('purge-with-truncate');
        /** @var FrameworkBundleConsoleApplication $application */
        $application = $this->getApplication();

        $this->loader->load($application, $manager, $bundles, $environment, $append, $truncate, $shard);

        return 0;
    }

    /**
     * Prompts to the user a message to ask him a confirmation.
     *
     * @param string $question
     * @param bool   $default
     *
     * @return bool User choice
     */
    private function askConfirmation(InputInterface $input, OutputInterface $output, $question, $default)
    {
        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelperSet()->get('question');
        $question = new ConfirmationQuestion($question, $default);

        return (bool) $questionHelper->ask($input, $output, $question);
    }
}
