<?php

namespace AppBundle\Manager;

use MediaBundle\Manager\MediaManager;
use Symfony\Component\Process\Process;

class MailerManager
{
    private $mailer;
    private $templating;
    private $mailerEmailFrom;
    private $spoolCommand;
    private $mediaManager;

    /**
     * @param $mailer Swiftmailer mailer instance.
     * @param $templating Twig instance.
     */
    public function __construct($mailer, $templating, $mailerEmailFrom, $spoolCommand, MediaManager $mediaManager)
    {
        $this->mailer = $mailer;
        $this->templating = $templating;
        $this->mailerEmailFrom = $mailerEmailFrom;
        $this->spoolCommand = $spoolCommand;
        $this->mediaManager = $mediaManager;
    }

    /**
     * @param string $subject
     * @param string $to
     * @param string $template
     * @param array $params
     * @param $replyTo mixed(string|null)
     */
    public function send($subject, $to, $template, $params, $replyTo = null, $cc = null)
    {
        $emailFolder = 'emails' . DIRECTORY_SEPARATOR;
        $file = $this->mediaManager->getFile($emailFolder . $template);

        if ($file->getSize() > 0) {
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
