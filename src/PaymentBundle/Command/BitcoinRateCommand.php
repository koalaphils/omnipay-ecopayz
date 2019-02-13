<?php


namespace PaymentBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use AppBundle\Helper\Publisher;
use PaymentBundle\Manager\BitcoinManager;
use PaymentBundle\Service\Blockchain;
use WebSocketBundle\Topics;

class BitcoinRateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('bitcoin:rate')
            ->addArgument('currency', InputArgument::REQUIRED, 'Get currency value from 1 BTC');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $currency = $input->getArgument('currency');
        $bitcoinAdjustment = $this->getBitcoinManager()->createBitcoinAdjustment($currency);
        $this->getBitcoinManager()->publishAdjustedBtcExchangeRate($bitcoinAdjustment->createWebsocketPayload());
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
