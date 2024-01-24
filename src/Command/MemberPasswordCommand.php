<?php

declare(strict_types=1);

namespace AVL\MemberConsole\Command;

use Contao\FrontendUser;
use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

/**
 * Change password of existing Contao front end user.
 *
 * @internal
 */
class MemberPasswordCommand extends Command
{
    protected static $defaultName = 'contao:member:password';
    protected static $defaultDescription = 'Change password of existing Contao front end user.';

    private ContaoFramework $framework;
    private Connection $connection;
    private PasswordHasherFactoryInterface $passwordHasherFactory;

    public function __construct(ContaoFramework $framework, Connection $connection, PasswordHasherFactoryInterface $passwordHasherFactory)
    {
        $this->framework = $framework;
        $this->connection = $connection;
        $this->passwordHasherFactory = $passwordHasherFactory;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'The username of the front end user')
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'The new password (using this option is not recommended for security reasons)')
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (null === $input->getArgument('username')) {
            throw new InvalidArgumentException('Please provide the username as argument.');
        }

        if (null !== $input->getOption('password')) {
            return;
        }

        $password = $this->askForPassword('Please enter the new password:', $input, $output);
        $confirm = $this->askForPassword('Please confirm the password:', $input, $output);

        if ($password !== $confirm) {
            throw new RuntimeException('The passwords do not match.');
        }

        $input->setOption('password', $password);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (null === $input->getArgument('username') || null === $input->getOption('password')) {
            return 1;
        }

        $this->framework->initialize();

        $config = $this->framework->getAdapter(Config::class);
        $minLength = $config->get('minPasswordLength') ?: 8;

        if (mb_strlen($input->getOption('password')) < $minLength) {
            throw new InvalidArgumentException(sprintf('The password must be at least %s characters long.', $minLength));
        }

        $passwordHasher = $this->passwordHasherFactory->getPasswordHasher(FrontendUser::class);
        $hash = $passwordHasher->hash($input->getOption('password'));

        $affected = $this->connection->update(
            'tl_member',
            [
                'password' => $hash,
                'locked' => 0,
                'loginAttempts' => 0,
            ],
            ['username' => $input->getArgument('username')]
        );

        if (0 === $affected) {
            throw new InvalidArgumentException(sprintf('Invalid username: %s', $input->getArgument('username')));
        }

        $io = new SymfonyStyle($input, $output);
        $io->success('The password has been changed successfully.');

        return 0;
    }

    /**
     * Asks a question with the given label and hides the input.
     */
    private function askForPassword(string $label, InputInterface $input, OutputInterface $output): string
    {
        $question = new Question($label);
        $question->setHidden(true);
        $question->setMaxAttempts(3);

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        return $helper->ask($input, $output, $question);
    }
}
