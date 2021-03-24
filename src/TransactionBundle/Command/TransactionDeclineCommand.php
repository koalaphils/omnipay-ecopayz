<?php

declare(strict_types = 1);

namespace TransactionBundle\Command;

use AppBundle\Manager\SettingManager;
use DbBundle\Entity\User;
use DbBundle\Repository\UserRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use TransactionBundle\Event\TransactionPostDeclineEvent;
use TransactionBundle\Event\TransactionPreDeclineEvent;
use TransactionBundle\Service\DeclineTransactionService;

class TransactionDeclineCommand extends Command
{
    protected static $defaultName = 'transaction:decline';

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var DeclineTransactionService
     */
    private $declineTransactionService;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(
        UserRepository $userRepository,
        TokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        DeclineTransactionService $declineTransactionService,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->userRepository = $userRepository;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->declineTransactionService = $declineTransactionService;
        $this->eventDispatcher = $eventDispatcher;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription('Auto decline transaction')
            ->addArgument('user', InputArgument::REQUIRED)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->loginUser($input->getArgument('user'));
        $autoDeclineConfiguration = $this->declineTransactionService->getAutoDeclineConfiguration();

        if (!$autoDeclineConfiguration['autoDecline']) {
            $output->writeln('Auto decline of transaction is disabled');

            return;
        }

        $this->eventDispatcher->addListener('transaction.autoDeclined.pre', function (TransactionPreDeclineEvent $event) use ($output) {
            if ($event->getTransaction()->getPaymentOptionType()->isPaymentBitcoin() && $event->getTransaction()->getBitcoinConfirmation() !== null) {
                $event->stopPropagation();

                return;
            }
            $output->writeln('Decline transaction number: ' . $event->getTransaction()->getNumber());
        }, 100);

        $this->eventDispatcher->addListener('transaction.autoDeclined.post', function (TransactionPostDeclineEvent $event) use ($output) {
            $output->writeln('Completed Declining transaction number: ' . $event->getTransaction()->getNumber());
        }, 100);

        $this->declineTransactionService->declineTransactions();
    }

    private function loginUser(string $username): void
    {
        $user = $this->userRepository->findByUsername($username, User::USER_TYPE_ADMIN);
        if ($user === null) {
            throw new UsernameNotFoundException('User not found');
        }
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->tokenStorage->setToken($token);

        if (!$this->authorizationChecker->isGranted('ROLE_SCHEDULER')) {
            throw new AccessDeniedException('Access Denied.');
        }
    }
}