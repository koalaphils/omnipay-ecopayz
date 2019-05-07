<?php

declare(strict_types = 1);

namespace TwoFactorBundle\Provider\Message\Email;

use AppBundle\Manager\MailerManager;
use TwoFactorBundle\Provider\Message\MessengerInterface;

class EmailMessenger implements MessengerInterface
{
    /**
     * @var string
     */
    private $template;

    /**
     * @var MailerManager
     */
    private $mailerManager;

    public function __construct(MailerManager $mailerManager, string $template)
    {
        $this->template = $template;
        $this->mailerManager = $mailerManager;
    }

    public function sendCode(string $code, string $to, array $payload = []): void
    {
        $this->mailerManager->send(
            'Your PIWI247 Verification Code',
            $to,
            $this->template,
            array_merge(['code' => $code], $payload)
        );
    }
}