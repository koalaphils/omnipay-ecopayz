<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use DbBundle\Entity\User;
use DbBundle\Entity\Currency;

class AppSetupCommand extends ContainerAwareCommand
{
    protected $output;
    protected $input;

    protected function configure()
    {
        $this
            ->setName('app:setup')
            ->setDescription('Setup backoffice system')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->input = $input;

        if ($this->hasExistingUser()) {
            $toLogin = $this->askQuestion('Do you want to login (y/n)?');

            if ($toLogin == 'y') {
                $username = $this->askQuestion('Login Username: ');
                $this->loginUser($username);
            }
        }

        $createUser = 'y';
        if ($this->hasExistingUser()) {
            $createUser = $this->askQuestion('Do you want to create new user? (y/n) ');
        }

        if ($createUser == 'y') {
            $this->createUser();
        }

        $createCurrency = 'y';
        if ($this->hasExistingCurrency()) {
            $createCurrency = $this->askQuestion('Do you want to create new currency? (y/n) ');
        }

        if ($createCurrency == 'y') {
            $this->createCurrency();
        }

        $this->initSettings();
    }

    private function loginUser($user): void
    {
        if (is_string($user)) {
            $user = $this->getUserRepository()->loadUserByUsername($user);
        }

        if (!($user instanceof User)) {
            throw new \Exception('Invalid User');
        }

        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->getContainer()->get('security.token_storage')->setToken($token);
        $event = new \Symfony\Component\Security\Http\Event\InteractiveLoginEvent(new \Symfony\Component\HttpFoundation\Request(), $token);
        $this->getContainer()->get("event_dispatcher")->dispatch("security.interactive_login", $event);
    }

    private function hasExistingUser(): bool
    {
        $qb = $this->getUserRepository()->createQueryBuilder('u');
        $qb->select('COUNT(u.id) as total');

        return $qb->getQuery()->getSingleScalarResult() > 0 ? true : false;
    }

    private function createUser(): \DbBundle\Entity\User
    {
        $this->output->writeln('Create User');

        $username = $this->askQuestion('Username: ');
        $password = $this->askQuestion('Password: ');
        $email = $this->askQuestion('Email: ');
        $isSuperAdmin = $this->askQuestion('Superadmin (y/n): ');

        $roles = ['ROLE_ADMIN' => 2];
        if ($isSuperAdmin == 'y') {
            $roles['ROLE_SUPER_ADMIN'] = 2;
        }

        $user = new User();
        $user->setUsername($username);
        $user->setIsActive(true);
        $user->setEmail($email);
        $user->setType(User::USER_TYPE_ADMIN);
        $user->setRoles($roles);
        $user->forSetup = true;
        $user->setPassword($this->getPasswordEncoder()->encodePassword($user, $password));

        $this->getUserRepository()->save($user);
        $this->output->writeln('User "' . $username . '" was successfully saved');

        if (!($this->getContainer()->get('user.manager')->getUser() instanceof User)) {
            $this->loginUser($user);
            $this->output->writeln('User "' . $username . '" was successfully ');
        }

        return $user;
    }

    private function hasExistingCurrency(): bool
    {
        $qb = $this->getCurrencyRepository()->createQueryBuilder('c');
        $qb->select('COUNT(c.id) as total');

        return $qb->getQuery()->getSingleScalarResult() > 0 ? true : false;
    }

    private function createCurrency()
    {
        $this->output->writeln('Create currency');

        $baseCurrency = $this->getBaseCurrency();
        $code = $this->askQuestion('Code: ');
        $name = $this->askQuestion('Name: ');
        $rate = 1;

        if ($baseCurrency !== null) {
            $rate = $this->askQuestion(sprintf('Convertion Rate (Base Currency %s): ', $baseCurrency->getCode()));
        }

        $currency = new Currency();
        $currency->setCode($code);
        $currency->setName($name);
        $currency->setRate($rate);
        $this->getCurrencyRepository()->save($currency);

        if ($baseCurrency === null) {
            $this->getSettingManager()->saveSetting('currency.base', $currency->getId());
        }

        return $currency;
    }

    private function getBaseCurrency()
    {
        $baseCurrency = $this->getSettingManager()->getSetting('currency.base', null);

        if ($baseCurrency !== null) {
            $baseCurrency = $this->getCurrencyRepository()->find($baseCurrency);
        }

        return $baseCurrency;
    }

    private function initSettings()
    {
        $settings = $this->getSettingManager()->getSettingCodes();
        foreach ($settings as $code) {
            $setting = $this->getSettingManager()->getSetting($code);
            $this->getSettingManager()->updateSetting($code, $setting);
        }
    }

    private function askQuestion($question, $defaultAnswer = null)
    {
        $q = new Question($question, $defaultAnswer);

        return $this->getQuestionHelper()->ask($this->input, $this->output, $q);
    }

    private function getUserRepository(): \DbBundle\Repository\UserRepository
    {
        return $this->getRepository('DbBundle:User');
    }

    private function getCurrencyRepository(): \DbBundle\Repository\CurrencyRepository
    {
        return $this->getRepository('DbBundle:Currency');
    }

    private function getRepository($name): \Doctrine\ORM\EntityRepository
    {
        return $this->getContainer()->get('doctrine')->getRepository($name);
    }

    private function getQuestionHelper(): \Symfony\Component\Console\Helper\QuestionHelper
    {
        return $this->getHelper('question');
    }

    private function getSettingManager(): \AppBundle\Manager\SettingManager
    {
        return $this->getContainer()->get('app.setting_manager');
    }

    private function getPasswordEncoder(): \Symfony\Component\Security\Core\Encoder\UserPasswordEncoder
    {
        return $this->getContainer()->get('security.password_encoder');
    }
}
