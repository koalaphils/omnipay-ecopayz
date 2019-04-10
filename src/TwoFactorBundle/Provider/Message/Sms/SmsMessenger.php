<?php

declare(strict_types = 1);

namespace TwoFactorBundle\Provider\Message\Sms;

use AppBundle\Manager\MailerManager;
use MediaBundle\Manager\MediaManager;
use TwoFactorBundle\Provider\Message\MessengerInterface;
use TwoFactorBundle\Provider\Message\Sms\Plugin\PluginInterface;

class SmsMessenger implements MessengerInterface
{
    /**
     * @var string
     */
    private $template;

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

    public function __construct(\Twig_Environment $twigEnvironment, SmsMessengerInterface $messenger, MediaManager $mediaManager, string $template)
    {
        $this->template = $template;
        $this->messenger = $messenger;
        $this->templating = $twigEnvironment;
        $this->mediaManager = $mediaManager;
    }

    public function sendCode(string $code, string $to, array $payload = []): void
    {
        $file = $this->mediaManager->getFile('emails' . DIRECTORY_SEPARATOR . $this->template);

        $message = $this->templating->render($file->getRealPath(), array_merge($payload, ['code' => $code]));
        $this->messenger->send($message, $to);
    }
}