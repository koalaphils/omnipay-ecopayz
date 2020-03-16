<?php

namespace TwoFactorBundle\EventListener;

use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use DbBundle\Entity\User;
use DbBundle\Entity\TwoFactorCode;
use TwoFactorBundle\Provider\Message\Email\EmailMessenger;
use TwoFactorBundle\Provider\Message\Email\EmailCodeGenerator;
use TwoFactorBundle\Provider\Message\StorageInterface;
use TwoFactorBundle\Provider\Message\CodeGeneratorInterface;
use AppBundle\Manager\SettingManager;

class LoginSuccessListener
{   
    private $codeGenerator;
    private $storage;
    private $emailMessenger;
    private $settingManager;
    
    public function __construct(
        EmailCodeGenerator $codeGenerator,
        StorageInterface $storage,
        EmailMessenger $emailMessenger,
        SettingManager $settingManager)
    {
        $this->codeGenerator = $codeGenerator;
        $this->storage = $storage;
        $this->emailMessenger = $emailMessenger;
        $this->settingManager = $settingManager;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        if (!$event->getAuthenticationToken() instanceof UsernamePasswordToken)
        {
            return;
        }

        //Check if user can do two-factor authentication
        $token = $event->getAuthenticationToken();
        $user = $token->getUser();
        if (!$user instanceof User)
        {
            return;
        }

        $email = $user->getEmail();
        $payload = [
            'provider' => 'email', 
            'email' => $email,
            'purpose' => 'default'
        ];
        $createdAt = new \DateTimeImmutable();
        $expiredAt = $createdAt->add(date_interval_create_from_date_string($this->settingManager->getSetting('code.expiration')));

        $code = new TwoFactorCode();
        $code
            ->setCode($this->codeGenerator->generateCode())
            ->setPayload($payload)
            ->setCreatedAt($createdAt)
            ->setExpiredAt($expiredAt)
        ;
        
        $this->storage->saveCode($code);

        // Mark the session to let RequestListener know that the user needs to be authenticated
        // via email otp
        $event->getRequest()->getSession()->set(self::getKey($token), null);
        $this->emailMessenger->sendCode($code->getCode(), $email, $code->getPayload());
    }

    // Generate key to be used when storing in session.
    static function getKey($token): string {
        return sprintf('two_factor_%s_%s', $token->getProviderKey(), $token->getUsername());
    }
}