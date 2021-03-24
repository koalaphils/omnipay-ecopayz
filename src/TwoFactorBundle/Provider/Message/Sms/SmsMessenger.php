<?php

declare(strict_types = 1);

namespace TwoFactorBundle\Provider\Message\Sms;

use AppBundle\Manager\MailerManager;
use MediaBundle\Manager\MediaManager;
use TwoFactorBundle\Provider\Message\MessengerInterface;
use TwoFactorBundle\Provider\Message\Sms\Plugin\PluginInterface;
use TwoFactorBundle\Provider\Message\TemplateProvider\TemplateProviderInterface;

class SmsMessenger implements MessengerInterface
{
    /**
     * @var array
     */
    private $templates;

    /**
     * @var SmsMessengerInterface
     */
    private $messenger;

    /**
     * @var \Twig_Environment
     */
    private $templating;

    /**
     * @var MediaManager
     */
    private $mediaManager;

    /**
     * @var TemplateProviderInterface
     */
    private $templateProvider;

    public function __construct(\Twig_Environment $twigEnvironment, SmsMessengerInterface $messenger, MediaManager $mediaManager, TemplateProviderInterface $templateProvider, array $templates)
    {
        $this->templates = $templates;
        $this->messenger = $messenger;
        $this->templating = $twigEnvironment;
        $this->mediaManager = $mediaManager;
        $this->templateProvider = $templateProvider;
    }

    public function sendCode(string $code, string $to, array $payload = []): void
    {
        if (array_has($this->templates, $payload['purpose'])) {
            $template = $this->templateProvider->getTemplateInfo($this->templates[$payload['purpose']]);
        } else {
            $template = $this->templateProvider->getTemplateInfo($this->templates['default']);
        }

        $file = $this->mediaManager->getFile('emails' . DIRECTORY_SEPARATOR . $template['file']);

        $message = $this->templating->render($file->getRealPath(), array_merge($payload, ['code' => $code]));
        $this->messenger->send($message, $to);
    }
}