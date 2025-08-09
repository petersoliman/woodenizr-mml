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

class ActivateUserCommand extends Command
{
    protected static $defaultName = 'app:user:activate';

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
            ->setDescription('Activate a user')
            ->setDefinition([
                new InputArgument('email', InputArgument::REQUIRED, 'The email'),
            ])
            ->setHelp(<<<'EOT'
The <info>app:user:activate</info> command activates a user (so they will be able to log in):

  <info>php %command.full_name% example@example.com</info>
EOT
            );
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');

        $this->activateUser($email);

        $output->writeln(sprintf('User "%s" has been activated.', $email));
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


        foreach ($questions as $name => $question) {
            $answer = $this->getHelper('question')->ask($input, $output, $question);
            $input->setArgument($name, $answer);
        }
    }

    /**
     * @throws \Exception
     */
    private function activateUser(string $email): void
    {
        $user = $this->userRepository->findOneBy(["email" => $email]);
        if (!$user instanceof UserInterface) {
            throw new \Exception("This email is not exist");
        }

        $user->setEnabled(true);
        $this->em->persist($user);
        $this->em->flush();

    }
}
