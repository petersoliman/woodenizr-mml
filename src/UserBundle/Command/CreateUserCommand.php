<?php

namespace App\UserBundle\Command;

use App\UserBundle\Entity\User;
use App\UserBundle\Model\UserInterface;
use App\UserBundle\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PN\ServiceBundle\Utils\Validate;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateUserCommand extends Command
{
    protected static $defaultName = 'app:user:create';

    private EntityManagerInterface $em;
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(
        EntityManagerInterface      $em,
        UserRepository              $userRepository,
        UserPasswordHasherInterface $userPasswordHasher
    )
    {
        parent::__construct();
        $this->em = $em;
        $this->userRepository = $userRepository;
        $this->userPasswordHasher = $userPasswordHasher;
    }

    protected function configure()
    {
        $this
            ->setDescription('Create a user.')
            ->setDefinition([
                new InputArgument('name', InputArgument::REQUIRED, 'The Name'),
                new InputArgument('email', InputArgument::REQUIRED, 'The email'),
                new InputArgument('password', InputArgument::REQUIRED, 'The password'),
                new InputArgument('isSuperAdmin', InputArgument::REQUIRED, 'Set the user as super admin'),
                new InputOption('inactive', null, InputOption::VALUE_NONE, 'Set the user as inactive'),
            ])
            ->setHelp(<<<'EOT'
The <info>user:create</info> command creates a user:

  <info>php %command.full_name% John example@example.com</info>

This interactive shell will ask you for an email and then a password.

You can alternatively specify the email and password as the second and third arguments:

  <info>php %command.full_name% John  example@example.com mypassword</info>

You can create an inactive user (will not be able to log in):

  <info>php %command.full_name% John example@example.com --inactive</info>

EOT
            );
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        $isSuperAdmin = $input->getArgument('isSuperAdmin');
        $inactive = $input->getOption('inactive');
        $isSuperAdmin = $isSuperAdmin == "yes";

        $this->createUser($name, $email, $password, $isSuperAdmin, $inactive);

        $output->writeln(sprintf('Created user <comment>%s</comment>', $email));

        return 0;
    }


    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $questions = [];

        if (!$input->getArgument('name')) {
            $question = new Question('Please choose an name: ');
            $question->setValidator(function ($name) {
                if (empty($name)) {
                    throw new \Exception('Email can not be empty');
                }

                return $name;
            });
            $questions['name'] = $question;
        }

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

        if (!$input->getArgument('password')) {
            $question = new Question('Please choose a password: ');
            $question->setValidator(function ($password) {
                if (empty($password)) {
                    throw new \Exception('Password can not be empty');
                }

                return $password;
            });
            $question->setHidden(true);
            $questions['password'] = $question;
        }

        if (!$input->getArgument('isSuperAdmin')) {
            $question = new ChoiceQuestion('Please choose a isSuperAdmin: ', ["no", "yes"], 0);
            $question->setValidator(function ($isSuperAdmin) {
                if (!in_array($isSuperAdmin, ["0", "1"])) {
                    throw new \Exception('isSuperAdmin can not be empty');
                }

                return $isSuperAdmin;
            });
            $questions['isSuperAdmin'] = $question;
        }

        foreach ($questions as $name => $question) {
            $answer = $this->getHelper('question')->ask($input, $output, $question);
            $input->setArgument($name, $answer);
        }
    }

    /**
     * @throws \Exception
     */
    private function createUser(
        string $name,
        string $email,
        string $password,
        bool   $isSuperAdmin,
        bool   $inactive = false
    ): void
    {
        $user = $this->userRepository->findOneBy(["email" => $email]);
        if ($user instanceof UserInterface) {
            throw new \Exception("This Email is already exist");
        }

        $user = new User();
        $user->setFullName($name);
        $user->setEmail($email);
        $user->setEnabled(!$inactive);
        if ($isSuperAdmin) {
            $user->addRole(UserInterface::ROLE_SUPER_ADMIN);
        }

        $encodedPassword = $this->userPasswordHasher->hashPassword($user, $password);

        $user->setPassword($encodedPassword);
        $this->em->persist($user);
        $this->em->flush();
    }
}
