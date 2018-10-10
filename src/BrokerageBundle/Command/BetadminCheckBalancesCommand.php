<?php

namespace BrokerageBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use DbBundle\Entity\Transaction;

class BetadminCheckBalancesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('betadmin:check-balances')
            ->setDescription('checking all customer balance from Back office to bet admin');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $response = ['message' => 'Unlink Skype betting successful.', 'success' => true];
            $customerProducts = $this->getCustomerProductRepository()->findContainsPathBrokerageSyncId();
            if ($customerProducts) {

                $fileContent = 'Username' . "\t\t\t" . 'BA Name' . "\t\t\t" . "BO Balance" . "\t\t\t" . "BA Balance" . "\n";
                foreach ($customerProducts as $customerProduct) {
                    
                    if (!empty($customerProduct->getDetail('brokerage.sync_id'))) {
                        $balance = $this->getBrokerageManager()->getBrokerageBalance($customerProduct->getBrokerageSyncId());

                        if (is_numeric($balance)) {
                            $fileContent .= $customerProduct->getUserName() . "\t\t\t" . $customerProduct->getBrokerageFirstName() . " " . $customerProduct->getBrokerageLastName() . "\t\t\t" . ($customerProduct->getBalance() + 0) . "\t\t\t" . $balance . "\n";
                            echo $customerProduct->getUserName() . "\t\t\t" . $customerProduct->getBrokerageFirstName() . " " . $customerProduct->getBrokerageLastName() . "\t\t\t" . ($customerProduct->getBalance() + 0) . "\t\t\t" . $balance . "\n";
                        }
                    }
                }

                file_put_contents($this->getCheckBalanceFile(), $fileContent);
            }
        } catch (\Exception $e) {
        }
    }

    private function getCustomerProductRepository()
    {
        return $this->getContainer()->get('brokerage.customer_product_repository');
    }

    private function getCheckBalanceFile()
    {
        return $this->getContainer()->getParameter('upload_folder') . sprintf('checkBalance.txt');
    }

    /**
     * Get user manager.
     *
     * @return \UserBundle\Manager\UserManager
     */
    protected function getUserManager()
    {
        return $this->getContainer()->get('user.manager');
    }

    private function getBrokerageManager(): \BrokerageBundle\Manager\BrokerageManager
    {
        return $this->getContainer()->get('brokerage.brokerage_manager');
    }

    private function createRequest(): \Symfony\Component\HttpFoundation\Request
    {
        $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $this->getRequestStack()->push($request);

        return $request;
    }

    private function getRequestStack(): \Symfony\Component\HttpFoundation\RequestStack
    {
        return $this->getContainer()->get('request_stack');
    }
}
