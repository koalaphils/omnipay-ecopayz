<?php

namespace ApiBundle\Subscriber;

use Firebase\JWT\JWT;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use ApiBundle\Event\CustomerCreatedEvent;
    
/**
 * Description of CustomerSubscriber
 *
 * @author Paolo Abendanio <cesar.abendanio@zmtsys.com>
 */
class CustomerSubscriber implements EventSubscriberInterface
{
    use \Symfony\Component\DependencyInjection\ContainerAwareTrait;

    private $asianconnectUrl;
    private $asianconnect09Domain;

    public function __construct(string $asianconnectUrl, string $asianconnect09Domain)
    {
        $this->asianconnectUrl = $asianconnectUrl;
        $this->asianconnect09Domain = $asianconnect09Domain;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'customer.created' => 'onCustomerCreated',
        ];
    }

    public function onCustomerCreated(CustomerCreatedEvent $event)
    {
        $ativationCode = [
            'username' => $event->getCustomer()->getUser()->getUsername(),
            'password' => $this->getContainer()->getParameter('customer_temp_password'),
            'activation_code' => $event->getCustomer()->getUser()->getActivationCode(),
        ];

        $customer = $event->getCustomer();
        $paymentMethod = $customer->getPaymentOptions();
        $paymentMethod = $paymentMethod->map(function($entity) {
            return $entity->getPaymentOption();
        })->toArray();
        $products = $customer->getProducts();
        $products = $products->map(function($entity) {
            return $entity;
        })->toArray();
        $params = [
            'fullName' => $customer->getFullName(),
            'birthDate' => $customer->getBirthDate(),
            'email' => $customer->getUser()->getEmail(),
            'country' => $customer->getCountry()->getName(),
            'currency' => $customer->getCurrency()->getName(),
            'phone' => array_get($customer->getContacts(), 0),
            'paymentMethod' => array_get($paymentMethod, 0),
            'socials' => array_get($customer->getSocials(), 0),
            'products' => $products,
            'originFrom' => $this->getOrigin($event->getOriginUrl()),
            'activationCode' => JWT::encode($ativationCode, 'AMSV2'),
            'ipAddress' => $customer->getUser()->getPreference('ipAddress'),
            'affiliate' => $customer->getDetail('affiliate'),
            'referrer' =>  $customer->getUser()->getPreference('referrer'),
            'tag' => $customer->getTags(),
            'isAffiliate' => $customer->isTagAsAffiliate(),
            'username' => $event->getCustomer()->getUser()->getUsername(),
            'password' => $event->getTempPassword()
        ];
        $this->sendAdminMail($params);
        $this->sendActivationMail($params);
    }

    protected function getContainer()
    {
        return $this->container;
    }

    protected function getTranslator(): \Symfony\Component\Translation\TranslatorInterface
    {
        return $this->getContainer()->get('translator');
    }

    private function sendActivationMail(array $params)
    {
        $this->getUserManager()->sendActivationMail($params);
    }

    private function sendAdminMail(array $params)
    {
        $ccMail = null;
        $replyTo = $params['email'];
        $originFrom = $params['originFrom'];

        # AC66-612 - hiding this feature until further notice
        # if ($this->wasRegisteredFromAsianconnect($originFrom)) {
        #    $ccMail = $this->getParameter('mailer_email_cc');
        # }

        $adminEmail = $this->getParameter('mailer_email_from');
        $subjectEmail = $this->getTranslator()->trans('email.subject.welcome', ['%fullName%' => $params['fullName'], ] , "AppBundle");

        if ($params['isAffiliate']) {
            $adminEmail = $this->getParameter('mailer_email_affiliate_from');
            $subjectEmail = $this->getTranslator()->trans('email.subject.welcomeAffiliate', ['%fullName%' => $params['fullName'], ] , "AppBundle");
        }
        
        $this->getMailer()
            ->send(
                $subjectEmail,
                $adminEmail,
                'registration-admin.html.twig',
                $params,
                $replyTo,
                $ccMail
            );
    }

    private function getMailer(): \AppBundle\Manager\MailerManager
    {
        return $this->getContainer()->get('app.mailer_manager');
    }

    private function getMediaManager(): \MediaBundle\Manager\MediaManager
    {
        return $this->getContainer()->get('media.manager');
    }

    private function getUserManager(): \UserBundle\Manager\UserManager
    {
        return $this->getContainer()->get('user.manager');
    }

    protected function getParameter($parameterName)
    {
        return $this->getContainer()->getParameter($parameterName);
    }

    private function getOrigin($originUrl): string
    {
        return $this->isRegisteredFromAsianconnect09($originUrl) ? $this->asianconnectUrl : $originUrl;
    }

    private function isRegisteredFromAsianconnect09($originUrl) : bool
    {
        $domainOrigin = parse_url($originUrl, PHP_URL_HOST);

        return $this->asianconnect09Domain === $domainOrigin ? true : false;
    }
}
