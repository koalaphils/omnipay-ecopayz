<?php

declare(strict_types = 1);

namespace ApiBundle\RequestHandler;

use ApiBundle\Request\TwoFactorCodeRequest;
use AppBundle\Manager\MailerManager;
use AppBundle\Manager\SettingManager;
use DbBundle\Entity\TwoFactorCode;
use TwoFactorBundle\Provider\Message\Email\EmailCodeGenerator;
use TwoFactorBundle\Provider\Message\Email\EmailMessenger;
use TwoFactorBundle\Provider\Message\Sms\SmsCodeGenerator;
use TwoFactorBundle\Provider\Message\Sms\SmsMessenger;
use TwoFactorBundle\Provider\Message\StorageInterface;

class TwoFactorCodeHandler
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var EmailMessenger
     */
    private $emailMessenger;

    /**
     * @var SmsMessenger
     */
    private $smsMessenger;

    /**
     * @var EmailCodeGenerator
     */
    private $emailCodeGenerator;

    /**
     * @var SmsCodeGenerator
     */
    private $smsCodeGenerator;

    /**
     * @var SettingManager
     */
    private $settingManager;

    /**
     * @var MailerManager
     */
    private $mailerManager;

    public function __construct(
        StorageInterface $storage,
        EmailMessenger $emailMessenger,
        SmsMessenger $smsMessenger,
        EmailCodeGenerator $emailCodeGenerator,
        SmsCodeGenerator $smsCodeGenerator,
        SettingManager $settingManager,
        MailerManager $mailerManager
    ) {
        $this->settingManager = $settingManager;
        $this->storage = $storage;
        $this->emailMessenger = $emailMessenger;
        $this->smsMessenger = $smsMessenger;
        $this->emailCodeGenerator = $emailCodeGenerator;
        $this->smsCodeGenerator = $smsCodeGenerator;
        $this->mailerManager = $mailerManager;
    }

    public function handle(TwoFactorCodeRequest $codeRequest): TwoFactorCode
    {
        if ($codeRequest->usePhone()) {
            $codeGenerator = $this->smsCodeGenerator;
            $messenger = $this->smsMessenger;
            $to = $codeRequest->getCountryPhoneCode() . $codeRequest->getPhoneNumber();
            $payload = ['provider' => 'sms', 'phone' => $codeRequest->getPhoneWithCountryCode()];
        } else {
            $codeGenerator = $this->emailCodeGenerator;
            $messenger = $this->emailMessenger;
            $to = $codeRequest->getEmail();
            $payload = ['provider' => 'email', 'email' => $codeRequest->getEmail()];
        }
        $payload['purpose'] = $codeRequest->getPurpose();
        $createdAt = new \DateTimeImmutable();
        $expiredAt = $createdAt->add(date_interval_create_from_date_string($this->settingManager->getSetting('code.expiration')));

        $code = new TwoFactorCode();
        $code
            ->setCode($codeGenerator->generateCode())
            ->setPayload($payload)
            ->setCreatedAt($createdAt)
            ->setExpiredAt($expiredAt)
        ;

        $this->storage->saveCode($code);

        $messenger->sendCode($code->getCode(), $to, $code->getPayload());
        if ($payload['purpose'] === 'register') {
            $subject = $this->settingManager->getSetting('registration.mail.lead_subject');
            $to = $this->settingManager->getSetting('registration.mail.to');
            if ($codeRequest->usePhone()) {
                $payload['from'] = $codeRequest->getCountryPhoneCode() . $codeRequest->getPhoneNumber();
            } else {
                $payload['from'] = $codeRequest->getEmail();
            }

            $this->mailerManager->send($subject, $to, 'leads.html.twig', $payload);
        }

        return $code;
    }
}