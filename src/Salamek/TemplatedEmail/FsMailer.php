<?php
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Salamek\TemplatedEmail;

use Nette;
use Nette\Mail\IMailer;
use Nette\Mail\Message;
use Nette\Utils\Strings;

class FsMailer implements IMailer
{
    use Nette\SmartObject;

    /** @var string */
    private $targetDir;

    /**
     * FsMailer constructor.
     * @param string $targetDir
     */
    public function __construct($targetDir)
    {
        $this->targetDir = $targetDir;
    }

    /**
     * Sends email.
     * @param Message $mail
     * @return void
     */
    public function send(Message $mail)
    {
        $tmp = clone $mail;
        $tmp->setHeader('Subject', null);
        $tmp->setHeader('To', null);

        file_put_contents($this->targetDir . (Strings::endsWith($this->targetDir,
                '/') ? '' : '/') . trim($mail->getEncodedHeader('To')) . '-' . $mail->getSubject() . '-' . date('Y-m-d H:i:s') . '.html', $mail->getHtmlBody());
    }
}
