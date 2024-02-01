<?php

declare(strict_types=1);

namespace AVL\MemberConsole\Command;

use Contao\MemberModel;
use Contao\MemberGroupModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Delete Contao front end user.
 *
 * @internal
 */
class MemberDeleteCommand extends Command
{
    protected static $defaultName = 'contao:member:delete';
    protected static $defaultDescription = 'Delete Contao front end user.';

    private ContaoFramework $framework;
    private Connection $connection;
    private Bool $delete = true;

    public function __construct(ContaoFramework $framework, Connection $connection)
    {
        $this->framework = $framework;
        $this->connection = $connection;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'The username of the front end user')
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (null === $input->getArgument('username')) {
            throw new InvalidArgumentException('Please provide the username as argument.');
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $member = $this->getMember($input->getArgument('username'));

        $io = new SymfonyStyle($input, $output);
        $io->text(sprintf("Member '%s' found:", $input->getArgument('username')));
        $io->table(
            ["id", "firstname", "lastname", "email", "group" . (count($member['groups_names']) > 1 ? 's' : '')],
            [[$member['id'], $member['firstname'], $member['lastname'], $member['email'], implode(', ', $member['groups_names'])]]
        );

        if (false === $input->getOption('no-interaction')) {
            $answer = $this->askChoice('Delete member?', ['no', 'yes'], $input, $output);

            if('no' === $answer) {
                return 0;
            }
        }

        $this->connection->delete(
            'tl_member',
            ['username' => $input->getArgument('username')]
        );

        $io->success(sprintf('Member %s deleted!', $input->getArgument('username')));

        return 0;
    }

    private function askChoice(string $label, array $options, InputInterface $input, OutputInterface $output): string
    {
        $question = new ChoiceQuestion($label, $options);
        $question->setAutocompleterValues($options);

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        return $helper->ask($input, $output, $question);
    }

    private function getMember(string $username): array
    {
        $this->framework->initialize();

        $memberModel = $this->framework->getAdapter(MemberModel::class);
        $member = $memberModel->findByUsername($username);

        if (null === $member) {
            throw new InvalidArgumentException(sprintf('Invalid username: %s', $username));
        }

        $groups = $member->getRelated('groups');
        $groups_names = [];
        foreach ($groups as $group) {
            $groups_names[] = $group->name;
        }

        return [
            'id'           => $member->id,
            'firstname'    => $member->firstname,
            'lastname'     => $member->lastname,
            'email'        => $member->email,
            'groups_names' => $groups_names
        ];
    }
}
