<?php

declare(strict_types = 1);

namespace TwoFactorBundle\Provider\Message\Email;

use AppBundle\Manager\MailerManager;
use TwoFactorBundle\Provider\Message\MessengerInterface;
use TwoFactorBundle\Provider\Message\TemplateProvider\TemplateProviderInterface;

class EmailMessenger implements MessengerInterface
{
    /**
     * @var string[]
     */
    private $templates;

    /**
     * @var MailerManager
     */
    private $mailerManager;

    /**
     * @var TemplateProviderInterface
     */
    private $templateProvider;

    public function __construct(MailerManager $mailerManager, TemplateProviderInterface $templateProvider, array $templates)
    {
        $this->templates = $templates;
        $this->mailerManager = $mailerManager;
        $this->templateProvider = $templateProvider;
    }

    public function sendCode(string $code, string $to, array $payload = []): void
    {
        if (array_has($this->templates, $payload['purpose'])) {
            $template = $this->templateProvider->getTemplateInfo($this->templates[$payload['purpose']]);
        } else {
            $template = $this->templateProvider->getTemplateInfo($this->templates['default']);
        }

        $this->mailerManager->send(
            $template['subject'],
            $to,
            $template['file'],
            array_merge(['code' => $code], $payload)
        );
    }
}