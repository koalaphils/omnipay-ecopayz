<?php

namespace AppBundle\Manager;

use MediaBundle\Manager\MediaManager;
use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Component\Process\Process;
use Twig\Environment;
use Twig\Template;

class MailerManager
{
    private $mailer;

    /**
     * @var TwigEngine
     */
    private $templating;
    private $mailerEmailFrom;
    private $spoolCommand;
    private $mediaManager;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * @param $mailer Swiftmailer mailer instance.
     * @param $templating Twig instance.
     */
    public function __construct($mailer, $templating, $mailerEmailFrom, $spoolCommand, MediaManager $mediaManager, Environment $twig)
    {
        $this->mailer = $mailer;
        $this->templating = $templating;
        $this->mailerEmailFrom = $mailerEmailFrom;
        $this->spoolCommand = $spoolCommand;
        $this->mediaManager = $mediaManager;
        $this->twig = $twig;
    }

    /**
     * @param $subject
     * @param $to
     * @param $template
     * @param $params
     * @param null $replyTo
     * @param null $cc
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function send($subject, $to, $template, $params, $replyTo = null, $cc = null)
    {
        $emailFolder = 'emails' . DIRECTORY_SEPARATOR;
        $file = $this->mediaManager->getFile($emailFolder . $template);

        if ($file->getSize() > 0) {
            $subject = $this->twig->createTemplate($subject)->render($params);

            $message = \Swift_Message::newInstance()
                ->setSubject($subject)
                ->setFrom(trim($this->mailerEmailFrom))
                ->setTo(trim($to))
                ->setBody(
                    $this->templating->render($file->getRealPath(), $params),
                    'text/html'
                );
            if (!is_null($replyTo)) {
                $message->setReplyTo(trim($replyTo));
            }

            if (!is_null($cc)) {
                $message->setCc($cc);
            }

            $this->mailer->send($message);
            $process = new Process($this->spoolCommand);
            $process->run();
        }
    }
}
