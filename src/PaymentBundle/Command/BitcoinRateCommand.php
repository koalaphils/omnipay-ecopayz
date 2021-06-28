<?php


namespace PaymentBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use AppBundle\Helper\Publisher;
use DbBundle\Entity\Transaction;
use PaymentBundle\Manager\BitcoinManager;
use PaymentBundle\Service\Blockchain;
use WebSocketBundle\Topics;

class BitcoinRateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('bitcoin:rate')
            ->addArgument('currency', InputArgument::REQUIRED, 'Get currency value from 1 BTC')
            ->addArgument('type', InputArgument::OPTIONAL, 'Either deposit or withdrawal. Default to deposit');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $currency = $input->getArgument('currency');
        $type = $input->getArgument('type');

        if ($type === null) {
            $type = 'deposit';
        }

        if ($type === 'deposit') {
            $type = Transaction::TRANSACTION_TYPE_DEPOSIT;;
        }

        if ($type === 'withdrawal') {
            $type = Transaction::TRANSACTION_TYPE_WITHDRAW;
        }

        $bitcoinAdjustment = $this->getBitcoinManager()->createBitcoinAdjustment($currency, $type);
        $this->getBitcoinManager()->publishAdjustedBtcExchangeRate($bitcoinAdjustment->createWebsocketPayload($type));
    }
    
    private function getBlockchain(): Blockchain
    {
        return $this->getContainer()->get('payment.blockchain');
    }
    
    private function getBitcoinManager(): BitcoinManager
    {
        return $this->getContainer()->get('payment.bitcoin_manager');
    }
    
    private function getWampUrl(): string
    {
        return $this->getContainer()->getParameter('websocket.wamp');
    }

    private function getPublisher(): Publisher
    {
        return $this->getContainer()->get('app.publisher');
    }
}
