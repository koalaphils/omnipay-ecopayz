<?php

namespace CustomerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class CustomerCreateZendeskCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('customer:create-zendesk')
            ->setDescription('Create end-user for zendesk')
            ->addArgument('customer', InputArgument::REQUIRED)
            ->addArgument('user', InputArgument::REQUIRED)
            ->setHelp('This command allows you to create zendesk end-user using customer')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln([
            'Create Zendesk End-User',
            '=======================',
        ]);
        if ($this->setUser($input->getArgument('user'))) {
            $helper = $this->getHelper('question');
            $id = $input->getArgument('customer');

            $customer = $this->_getCustomerRepository()->find($id);
            if (is_null($customer)) {
                $output->writeln([
                    'Customer ID ' . $id . ' not found',
                ]);
            } elseif (!is_null($customer->getUser()->getZendeskId())) {
                $output->writeln([
                    'Customer ' . $id . ' has already end-user in zendesk [Zendesk ID: ' . $customer->getUser()->getZendeskId() . ']',
                ]);
            } else {
                $zendeskUser = $this->getZendeskUserManager()->create([
                    'name' => $customer->getFName() . ' ' . $customer->getLName(),
                    'email' => $customer->getUser()->getEmail(),
                    'role' => 'end-user',
                    'details' => 'This user was created using API from Summit CMS',
                    'verified' => true,
                ]);
                $customer->getUser()->setZendeskId($zendeskUser->user->id);

                $this->_getCustomerRepository()->save($customer);

                $output->writeln([
                    'Successfully created end-user for customer ' . $id . ' [Zendesk ID: ' . $customer->getUser()->getZendeskId() . ']',
                ]);
            }
        } else {
            $output->writeln(['Unable to authenticate user']);
        }

        $output->writeln(['']);
    }

    protected function setUser($username)
    {
        $user = $this->getUserRepository()->loadUserByUsername($username);
        if (is_null($user)) {
            return false;
        }
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->getContainer()->get('security.token_storage')->setToken($token);

        return true;
    }

    /**
     * @return \DbBundle\Repository\UserRepository
     */
    protected function getUserRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('DbBundle:User');
    }

    /**
     * @return \ZendeskBundle\Manager\UserManager
     */
    protected function getZendeskUserManager()
    {
        return $this->getContainer()->get('zendesk.user_manager');
    }

    /**
     * Get customer repository.
     *
     * @return \DbBundle\Repository\CustomerRepository
     */
    private function _getCustomerRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('DbBundle:Customer');
    }
}
