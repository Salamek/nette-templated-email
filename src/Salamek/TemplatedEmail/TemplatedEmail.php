<?php

namespace Salamek\TemplatedEmail;


use Nette\Application\Application;
use Nette\Bridges\ApplicationLatte\UIMacros;
use Nette\Http\Request;
use Nette\IOException;
use Nette\Localization\ITranslator;
use Nette\Mail\IMailer;
use Nette\Mail\Message;
use Nette\Utils\Strings;
use Latte\Loaders\StringLoader;
use Latte\Engine;
use Tracy\Debugger;
use Nette\Object;

/**
 * Class TemplatedEmail
 * @package Salamek\TemplatedEmail
 */
class TemplatedEmail extends Object
{

    /** @var Request */
    private $httpRequest;

    /** @var string */
    private $template = '';

    /** @var null|ITranslator */
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

    /** @var string */
    private $sendEmailDebugStorage;

    /** @var string */
    private $templateStorage;

    /** @var string */
    private $fromName;

    /** @var string */
    private $fromEmail;

    /** @var Application */
    private $application;

    /** @var \Nette\Application\IPresenter */
    private $presenter;

    /** @var IMailer */
    private $mailer;

    /**
     * TemplatedEmail constructor.
     * @param $sendEmailDebugStorage
     * @param $templateStorage
     * @param $fromName
     * @param $fromEmail
     * @param Request $httpRequest
     * @param IMailer $mailer
     * @param Application $application
     * @param ITranslator|null $translator
     */
    public function __construct($sendEmailDebugStorage, $templateStorage, $fromName, $fromEmail, Request $httpRequest, IMailer $mailer, Application $application, ITranslator $translator = null)
    {
        $this->sendEmailDebugStorage = $sendEmailDebugStorage;
        $this->templateStorage = $templateStorage;
        $this->fromName = $fromName;
        $this->fromEmail = $fromEmail;
        $this->httpRequest = $httpRequest;
        $this->mailer = $mailer;
        $this->application = $application;
        $this->presenter = $application->getPresenter();
        $this->translator = $translator;

        $this->mkdir($sendEmailDebugStorage);
        $this->mkdir($templateStorage);
    }

    /**
     * @param $name
     * @param $parameters
     * @throws \Exception
     * @return TemplatedEmail
     */
    public function __call($name, $parameters)
    {
        $this->reset();
        $this->setTemplate($this->templateStorage . '/' . $name . '.latte');
        $this->setParameters($parameters[0]);

        return $this;
    }

    /**
     * @param ITranslator $translator
     */
    public function setTranslator(ITranslator $translator)
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
        $this->template = null;
        $this->setFrom = null;
        $this->addTo = [];
        $this->parameters = [];
        $this->setSubject = null;
        $this->addReplyTo = null;
        $this->addAttachment = [];
    }

    /**
     * @param $template
     * @throws \Exception
     */
    public function setTemplate($template)
    {
        if (!is_file($template)) {
            throw new \Exception(sprintf('Mail template %s not found', $template));
        }

        $this->template = $template;
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
        //$latte->setLoader(new StringLoader());
        if ($this->translator) {
            $latte->addFilter('translate', $this->translator === null ? null : [$this->translator, 'translate']);
        }

        $latte->addProvider('uiPresenter', $this->presenter);
        $latte->addProvider('uiControl', $this->presenter);

        UIMacros::install($latte->getCompiler());

        $mail->setHtmlBody($latte->renderToString($this->template, $this->parameters), $this->templateStorage.'/images');


        if (!Debugger::$productionMode) {
            $mailer = new FsMailer($this->sendEmailDebugStorage);
        } else {
            $mailer = $this->mailer;
        }

        $mailer->send($mail);

        //Reset everything after send
        $this->reset();
    }

    /**
     * @param $dir
     */
    private function mkdir($dir)
    {
        $oldMask = umask(0);
        @mkdir($dir, 0777, true);
        @chmod($dir, 0777);
        umask($oldMask);

        if (!is_dir($dir) || !is_writable($dir)) {
            throw new IOException("Please create writable directory $dir.");
        }
    }
}
