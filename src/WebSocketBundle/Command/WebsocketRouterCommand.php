<?php

namespace WebSocketBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WampPost\WampPost;

class WebsocketRouterCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('websocket:start')
            ->setDescription('Start the default Thruway WAMP router')
            ->setHelp('The <info>%command.name%</info> starts the Thruway WAMP router.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $output->writeln('Making a go at starting the Thruway Router');

            //Configure stuff
            $config = $this->getContainer()->getParameter('voryx_thruway');

            /* @var $server \Thruway\Peer\Router */
            $server = $this->getContainer()->get('voryx.thruway.server');

            $wp = new WampPost(
                $config['realm'],
                $server->getLoop(),
                $this->getContainer()->getParameter('voryx_thruway.wamppost.ip'),
                $this->getContainer()->getParameter('voryx_thruway.wamppost.port')
            );

            $server->addTransportProvider(new \WebSocketBundle\Transport\InternalClientTransportProvider($wp));
            //Trusted provider (bound to loopback and requires no authentication)
            $trustedProvider = new \WebSocketBundle\Transport\WebSocketTransportProvider($config['router']['ip'], $config['router']['trusted_port']);
            $trustedProvider->setTrusted(true);
            $server->addTransportProvider($trustedProvider);

            $server->start();
        } catch (\Exception $e) {
            $logger = $this->getContainer()->get('logger');
            $logger->addCritical('EXCEPTION:' . $e->getMessage());
            $output->writeln('EXCEPTION:' . $e->getMessage());
        }
    }
}
