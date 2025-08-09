<?php

namespace App\UserBundle\Command;

use App\UserBundle\Model\UserInterface;
use App\UserBundle\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PN\ServiceBundle\Utils\Validate;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class PromoteUserCommand extends Command
{
    protected static $defaultName = 'app:user:promote';

    private EntityManagerInterface $em;
    private UserRepository $userRepository;

    public function __construct(
        EntityManagerInterface $em,
        UserRepository $userRepository
    ) {
        parent::__construct();
        $this->em = $em;
        $this->userRepository = $userRepository;
    }

    protected function configure()
    {
        $this
            ->setDescription('Promotes a user by adding a role')
            ->setDefinition([
                new InputArgument('email', InputArgument::REQUIRED, 'The email'),
                new InputArgument('role', InputArgument::REQUIRED, 'The role'),
            ])
            ->setHelp(<<<'EOT'
The <info>app:user:promote</info> command promotes a user by adding a role

  <info>php %command.full_name% example@example.com ROLE_CUSTOM</info>
  <info>php %command.full_name% --super example@example.com</info>
EOT
            );
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');
        $role = $input->getArgument('role');

        $isRoleAdded = $this->promoteUser($email, $role);
        if ($isRoleAdded) {
            $output->writeln(sprintf('Role "%s" has been added to user "%s". This change will not apply until the user logs out and back in again.',
                $role, $email));
        } else {
            $output->writeln(sprintf('User "%s" did already have "%s" role.', $email, $role));
        }


        return 0;
    }


    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $questions = [];


        if (!$input->getArgument('email')) {
            $question = new Question('Please choose an email: ');
            $question->setValidator(function ($email) {
                if (empty($email)) {
                    throw new \Exception('Email can not be empty');
                }
                if (!Validate::email($email)) {
                    throw new \Exception('Invalid Email');
                }

                return $email;
            });
            $questions['email'] = $question;
        }

        if (!$input->getArgument('role')) {
            $question = new Question('Please choose a role: ');
            $question->setValidator(function ($role) {
                if (empty($role)) {
                    throw new \Exception('Email can not be empty');
                }

                return $role;
            });
            $questions['role'] = $question;
        }

        foreach ($questions as $name => $question) {
            $answer = $this->getHelper('question')->ask($input, $output, $question);
            $input->setArgument($name, $answer);
        }
    }

    /**
     * @throws \Exception
     */
    private function promoteUser(
        string $email,
        string $role,
    ): bool {
        $user = $this->userRepository->findOneBy(["email" => $email]);
        if (!$user instanceof UserInterface) {
            throw new \Exception("This email is not exist");
        }
        if ($user->hasRole($role)) {
            return false;
        }

        $user->addRole($role);
        $this->em->persist($user);
        $this->em->flush();

        return true;
    }
}
