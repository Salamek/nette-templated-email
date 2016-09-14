<?php

namespace Salamek\TemplatedEmail;

use Nette\Http\Request;
use Nette\Mail\Message;
use Nette\Mail\SendmailMailer;
use Nette\Mail\SmtpMailer;
use Nette\Utils\Strings;
use Latte\Loaders\StringLoader;
use Latte\Engine;
use Tracy\Debugger;

/**
 * Class TemplatedEmail
 * @package Salamek\TemplatedEmail
 */
class TemplatedEmail extends Nette\Object
{

    /** @var Request */
    private $httpRequest;

    /** @var string */
    private $template = '';

    /** @var null| */
    private $translator = null;

    /** @var null|array */
    private $setFrom = null;

    /** @var array */
    private $addTo = [];

    /** @var array */
    private $parameters = [];

    /** @var null|string */
    private $setSubject = null;

    /** @var null|string */
    private $addReplyTo = null;

    /** @var array */
    private $addAttachment = [];

    private $sendEmailDebugStorage;

    /** @var string */
    private $templateStorage;

    /** @var string */
    private $fromName;

    /** @var string */
    private $fromEmail;

    private $mailer;


    public function __construct($sendEmailDebugStorage, $templateStorage, $fromName, $fromEmail, Request $httpRequest, Mailer $mailer)
    {
        $this->sendEmailDebugStorage = $sendEmailDebugStorage;
        $this->templateStorage = $templateStorage;
        $this->fromName = $fromName;
        $this->fromEmail = $fromEmail;
        $this->httpRequest = $httpRequest;
        $this->mailer = $mailer;
    }

    public function setTranslator($translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param string $templateStorage
     */
    public function setTemplateStorage($templateStorage)
    {
        $this->templateStorage = $templateStorage;
    }

    /**
     * @param mixed $sendEmailDebugStorage
     */
    public function setSendEmailDebugStorage($sendEmailDebugStorage)
    {
        $this->sendEmailDebugStorage = $sendEmailDebugStorage;
    }

    /**
     * @param string $fromName
     */
    public function setFromName($fromName)
    {
        $this->fromName = $fromName;
    }

    /**
     * @param string $fromEmail
     */
    public function setFromEmail($fromEmail)
    {
        $this->fromEmail = $fromEmail;
    }

    /**
     * @param string $email
     * @param null $name
     * @return $this
     */
    public function setFrom($email, $name = null)
    {
        $emailDelimiter = '@';
        //make it lowercase for case when some idiot enters IdiotIdiotic@example.com
        $parts = explode($emailDelimiter, Strings::lower($email));

        //Add missing domain name
        if (count($parts) == 1) {
            $parts[] = $this->httpRequest->getUrl()->host;
        }

        if (!$name) {
            $name = $this->fromName;
        }

        $this->setFrom = array(implode($emailDelimiter, $parts), $name);


        return $this;
    }

    public function reset()
    {
        $this->template = '';
        $this->setFrom = null;
        $this->addTo = [];
        $this->parameters = [];
        $this->setSubject = null;
        $this->addReplyTo = null;
        $this->addAttachment = [];
    }

    /**
     * @param $latte
     * @throws \Exception
     */
    public function addTemplate($latte)
    {
        if (!is_file($latte)) {
            throw new \Exception(sprintf('Mail template %s not found', $latte));
        }

        $this->template .= file_get_contents($latte);
    }

    /**
     * @param array $parameters
     * @return $this
     */
    public function setParameters(array$parameters)
    {
        $parameters['domain'] = $this->httpRequest->getUrl()->host;
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * @param $email
     * @param null $name
     * @return $this
     */
    public function addTo($email, $name = null)
    {
        $this->addTo[] = [$email, $name];
        return $this;
    }

    /**
     * @param $subject
     * @return $this
     */
    public function setSubject($subject)
    {
        $this->setSubject = $subject;
        return $this;
    }

    /**
     * @param $email
     * @param null $name
     * @return $this
     */
    public function addReplyTo($email, $name = null)
    {
        $this->addReplyTo = [$email, $name];
        return $this;
    }

    /**
     * @param $file
     * @return $this
     */
    public function addAttachment($file)
    {
        $this->addAttachment[] = $file;
        return $this;
    }

    /**
     *
     */
    public function send()
    {
        $mail = new Message;

        if ($this->setFrom) {
            $mail->setFrom($this->setFrom[0], $this->setFrom[1]);
        } else {
            $mail->setFrom($this->fromEmail, $this->fromName);
        }

        foreach ($this->addTo AS $addTo) {
            $mail->addTo($addTo[0], $addTo[1]);
        }

        if ($this->setSubject) {
            $mail->setSubject($this->setSubject);
        }

        if ($this->addReplyTo) {
            $mail->addReplyTo($this->addReplyTo[0], $this->addReplyTo[1]);
        }

        foreach ($this->addAttachment AS $addAttachment) {
            $mail->addAttachment($addAttachment);
        }

        $latte = new Engine;
        $latte->setLoader(new StringLoader());
        if ($this->translator) {
            $latte->addFilter('translate', $this->translator === null ? null : [$this->translator, 'translate']);
        }

        $mail->setHtmlBody($latte->renderToString($this->template, $this->parameters), './images/email/');


        if (!Debugger::$productionMode) {
            $mailer = new FsMailer($this->sendEmailDebugStorage);
        } else {
            $mailer = $this->mailer;
        }

        $mailer->send($mail);

        //Reset everything after send
        $this->reset();
    }
}
