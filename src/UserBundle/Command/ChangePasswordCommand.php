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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ChangePasswordCommand extends Command
{
    protected static $defaultName = 'app:user:change-password';

    private EntityManagerInterface $em;
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(
        EntityManagerInterface $em,
        UserRepository $userRepository,
        UserPasswordHasherInterface $userPasswordHasher

    ) {
        parent::__construct();
        $this->em = $em;
        $this->userRepository = $userRepository;
        $this->userPasswordHasher = $userPasswordHasher;
    }

    protected function configure()
    {
        $this
            ->setDescription('Change the password of a user.')
            ->setDefinition([
                new InputArgument('email', InputArgument::REQUIRED, 'The email'),
                new InputArgument('password', InputArgument::REQUIRED, 'The role'),
            ])
            ->setHelp(<<<'EOT'
The <info>app:user:change-password</info> command changes the password of a user:

  <info>php %command.full_name% example@example.com</info>

This interactive shell will first ask you for a password.

You can alternatively specify the password as a second argument:

  <info>php %command.full_name% example@example.com mypassword</info>

EOT
            );
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');

        $this->changePassword($email, $password);

        $output->writeln(sprintf('Changed password for user <comment>%s</comment>', $email));

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

        foreach ($questions as $name => $question) {
            $answer = $this->getHelper('question')->ask($input, $output, $question);
            $input->setArgument($name, $answer);
        }
    }

    /**
     * @throws \Exception
     */
    private function changePassword(
        string $email,
        string $password,
    ): void
    {
        $user = $this->userRepository->findOneBy(["email" => $email]);
        if (!$user instanceof UserInterface) {
            throw new \Exception("This email is not exist");
        }

        $encodedPassword = $this->userPasswordHasher->hashPassword($user, $password);
        $user->setPassword($encodedPassword);
        $this->em->persist($user);
        $this->em->flush();

    }
}
