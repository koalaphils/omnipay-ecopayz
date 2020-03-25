<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Doctrine\Bundle\DoctrineCacheBundle\DoctrineCacheBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle(),
            new FOS\OAuthServerBundle\FOSOAuthServerBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Nelmio\CorsBundle\NelmioCorsBundle(),
            new FOS\RestBundle\FOSRestBundle(),
            new Nelmio\ApiDocBundle\NelmioApiDocBundle(),
            new Payum\Bundle\PayumBundle\PayumBundle(),
            // BO
            new AppBundle\AppBundle(),
            new DbBundle\DbBundle(),
            new UserBundle\UserBundle(),
            new GroupBundle\GroupBundle(),
            new ProductBundle\ProductBundle(),
            new CurrencyBundle\CurrencyBundle(),
            new CountryBundle\CountryBundle(),
            new CustomerBundle\CustomerBundle(),
            new \MemberBundle\MemberBundle(),
            new GatewayBundle\GatewayBundle(),
            new NoticeBundle\NoticeBundle(),
            // new ZendeskBundle\ZendeskBundle(),
            new TicketBundle\TicketBundle(),
            new PaymentOptionBundle\PaymentOptionBundle(),
            new TransactionBundle\TransactionBundle(),
            new BonusBundle\BonusBundle(),
            new MediaBundle\MediaBundle(),
            new SessionBundle\SessionBundle(),
            new ThemeBundle\ThemeBundle(),
            new WebSocketBundle\WebSocketBundle(),
            new ApiBundle\ApiBundle(),
            new GatewayTransactionBundle\GatewayTransactionBundle(),
            new AuditBundle\AuditBundle(),
            new ReportBundle\ReportBundle(),
            new PaymentBundle\PaymentBundle(),
            new CommissionBundle\CommissionBundle(),
            new JMS\JobQueueBundle\JMSJobQueueBundle(),
            new JMS\DiExtraBundle\JMSDiExtraBundle($this),
            new JMS\AopBundle\JMSAopBundle(),
            new PinnacleBundle\PinnacleBundle(),
            new \TwoFactorBundle\TwoFactorBundle(),
            new \Snc\RedisBundle\SncRedisBundle(),
            new \Aws\Symfony\AwsBundle(),
            new MemberRequestBundle\MemberRequestBundle(),
            new ProductIntegrationBundle\ProductIntegrationBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'), true)) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();

            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
            if ('dev' === $this->getEnvironment()) {
                $bundles[] = new Symfony\Bundle\WebServerBundle\WebServerBundle();
            }
        }

        return $bundles;
    }

    public function getRootDir()
    {
        return __DIR__;
    }

    public function getCacheDir()
    {
        return dirname(__DIR__) . '/var/cache/' . $this->getEnvironment();
    }

    public function getLogDir()
    {
        return dirname(__DIR__) . '/var/logs';
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir() . '/config/config_' . $this->getEnvironment() . '.yml');
    }
}
