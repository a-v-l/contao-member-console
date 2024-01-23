<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace AVL\MemberConsole\Command;

use Contao\BackendUser;
use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\MemberGroupModel;
use Contao\Validator;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

/**
 * Creates a new Contao front end user.
 *
 * @internal
 */
class MemberCreateCommand extends Command
{
    protected static $defaultName = 'contao:member:create';
    protected static $defaultDescription = 'Create a new Contao front end user.';

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
            ->addOption('username', 'u', InputOption::VALUE_REQUIRED, 'The username to create')
            ->addOption('firstname', null, InputOption::VALUE_REQUIRED, 'The firstname name')
            ->addOption('lastname', null, InputOption::VALUE_REQUIRED, 'The lastname name')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'The e-mail address')
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'The password')
            ->addOption('group', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The groups to assign the user to (optional)')
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (null === $input->getOption('username')) {
            $username = $this->ask('Please enter the username: ', $input, $output);

            $input->setOption('username', $username);
        }

        if (null === $input->getOption('firstname')) {
            $firstname = $this->ask('Please enter the firstname: ', $input, $output);

            $input->setOption('firstname', $firstname);
        }

        if (null === $input->getOption('lastname')) {
            $lastname = $this->ask('Please enter the lastname: ', $input, $output);

            $input->setOption('lastname', $lastname);
        }

        $emailCallback = static function ($value) {
            if (!Validator::isEmail($value)) {
                throw new \InvalidArgumentException('The e-mail address is invalid.');
            }

            return $value;
        };

        if (null === $input->getOption('email')) {
            $email = $this->ask('Please enter the e-mail address: ', $input, $output, $emailCallback);

            $input->setOption('email', $email);
        } else {
            $emailCallback($input->getOption('email'));
        }

        $config = $this->framework->getAdapter(Config::class);
        $minLength = $config->get('minPasswordLength');
        $username = $input->getOption('username');

        $passwordCallback = static function ($value) use ($username, $minLength): string {
            if ('' === trim($value)) {
                throw new \RuntimeException('The password cannot be empty');
            }

            if (mb_strlen($value) < $minLength) {
                throw new \RuntimeException(sprintf('Please use at least %d characters.', $minLength));
            }

            if ($value === $username) {
                throw new \RuntimeException('Username and password must not be the same.');
            }

            return $value;
        };

        if (null === $input->getOption('password')) {
            $password = $this->askForPassword('Please enter the new password: ', $input, $output, $passwordCallback);

            $confirmCallback = static function ($value) use ($password): string {
                if ($password !== $value) {
                    throw new \RuntimeException('The passwords do not match.');
                }

                return $value;
            };

            $this->askForPassword('Please confirm the password: ', $input, $output, $confirmCallback);

            $input->setOption('password', $password);
        } else {
            $passwordCallback($input->getOption('password'));
        }

        if (($options = $this->getGroups()) && 0 !== \count($options)) {
            $answer = $this->askMultipleChoice(
                'Assign which groups to the user (select multiple comma-separated)?',
                $options,
                $input,
                $output
            );

            $input->setOption('group', array_values(array_intersect_key(array_flip($options), array_flip($answer))));
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (
            null === $input->getOption('username')
            || (null === $email = $input->getOption('email'))
            || (null === $input->getOption('firstname'))
            || (null === $input->getOption('lastname'))
            || (null === $password = $input->getOption('password'))
        ) {
            $io->error('Please provide at least and each of: username, firstname, lastname, email, password');

            return 1;
        }

        $this->persistUser(
            $username = $input->getOption('username'),
            $input->getOption('firstname'),
            $input->getOption('lastname'),
            $email,
            $password,
            $input->getOption('group')
        );

        $io->success(sprintf('Member %s created.', $username));

        return 0;
    }

    private function ask(string $label, InputInterface $input, OutputInterface $output, callable $callback = null): string
    {
        $question = new Question($label);
        $question->setMaxAttempts(3);
        $question->setValidator($callback);

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        return $helper->ask($input, $output, $question);
    }

    private function askForPassword(string $label, InputInterface $input, OutputInterface $output, callable $callback): string
    {
        $question = new Question($label);
        $question->setHidden(true);
        $question->setMaxAttempts(3);
        $question->setValidator($callback);

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        return $helper->ask($input, $output, $question);
    }

    private function askMultipleChoice(string $label, array $options, InputInterface $input, OutputInterface $output): array
    {
        $question = new ChoiceQuestion($label, $options);
        $question->setAutocompleterValues($options);
        $question->setMultiselect(true);

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        return $helper->ask($input, $output, $question);
    }

    private function getGroups(): array
    {
        $this->framework->initialize();

        $memberGroupModel = $this->framework->getAdapter(MemberGroupModel::class);
        $groups = $memberGroupModel->findAll();

        if (null === $groups) {
            return [];
        }

        return $groups->fetchEach('name');
    }

    private function persistUser(string $username, string $firstname, string $lastname, string $email, string $password, array $groups = null): void
    {
        $time = time();
        $hash = $this->passwordHasherFactory->getPasswordHasher(BackendUser::class)->hash($password);

        $data = [
            'tstamp' => $time,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'username' => $username,
            'password' => $hash,
            'login' => '1',
            'dateAdded' => $time,
        ];

        if (!empty($groups)) {
            $data[$this->connection->quoteIdentifier('groups')] = serialize(array_map('strval', $groups));
        }

        $this->connection->insert('tl_member', $data);
    }
}
