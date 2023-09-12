<?php

namespace Salamek\TemplatedEmail;

use Nette\Application\LinkGenerator;
use Nette\Bridges\ApplicationLatte\LatteFactory;
use Nette\Bridges\ApplicationLatte\UIMacros;
use Nette\Http\Request;
use Nette\IOException;
use Nette\Mail\Mailer;
use Nette\Mail\Message;
use Nette\Utils\Strings;
use Nette\Localization\Translator;
use Nette\SmartObject;



/**
 * Class TemplatedEmail
 * @package Salamek\TemplatedEmail
 */
class TemplatedEmail
{
    use SmartObject;

    /** @var bool */
    private $debugMode;

    /** @var Request */
    private $httpRequest;

    /** @var string */
    private $template = '';

    /** @var null|Translator */
    private $translator = null;

    /** @var null|array */
    private $setFrom = null;

    /** @var array */
    private $addTo = [];

    /** @var array */
    private $addBcc = [];

    /** @var array */
    private $addCc = [];

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

    /** @var Mailer */
    private $mailer;

    /** @var LatteFactory */
    private $latteFactory;

    /** @var LinkGenerator */
    private $linkGenerator;

    /**
     * TemplatedEmail constructor.
     * @param bool $debugMode
     * @param string $sendEmailDebugStorage
     * @param string $templateStorage
     * @param string $fromName
     * @param string $fromEmail
     * @param Request $httpRequest
     * @param Mailer $mailer
     * @param LatteFactory $latteFactory
     * @param LinkGenerator $linkGenerator
     * @param Translator|null $translator
     */
    public function __construct(
        bool $debugMode,
        string $sendEmailDebugStorage,
        string $templateStorage,
        string $fromName,
        string $fromEmail,
        Request $httpRequest,
        Mailer $mailer,
        LatteFactory $latteFactory,
        LinkGenerator $linkGenerator,
        Translator $translator = null
    )
    {
        $this->debugMode = $debugMode;
        $this->sendEmailDebugStorage = $sendEmailDebugStorage;
        $this->templateStorage = $templateStorage;
        $this->fromName = $fromName;
        $this->fromEmail = $fromEmail;
        $this->httpRequest = $httpRequest;
        $this->mailer = $mailer;
        $this->translator = $translator;
        $this->latteFactory = $latteFactory;
        $this->linkGenerator = $linkGenerator;

        $this->mkdir($sendEmailDebugStorage);
        $this->mkdir($templateStorage);
    }

    /**
     * @param $name
     * @param $parameters
     * @throws \Exception
     * @return TemplatedEmail
     */
    public function __call(string $name, array $parameters): TemplatedEmail
    {
        $this->reset();
        $this->setTemplate($this->templateStorage . '/' . $name . '.latte');
        $this->setParameters($parameters[0]);

        return $this;
    }

    /**
     * @param Translator $translator
     */
    public function setTranslator(Translator $translator): void
    {
        $this->translator = $translator;
    }

    /**
     * @param string $templateStorage
     */
    public function setTemplateStorage(string $templateStorage): void
    {
        $this->templateStorage = $templateStorage;
    }

    /**
     * @param string $sendEmailDebugStorage
     */
    public function setSendEmailDebugStorage(string $sendEmailDebugStorage): void
    {
        $this->sendEmailDebugStorage = $sendEmailDebugStorage;
    }

    /**
     * @param string $fromName
     */
    public function setFromName(string $fromName): void
    {
        $this->fromName = $fromName;
    }

    /**
     * @param string $fromEmail
     */
    public function setFromEmail(string $fromEmail): void
    {
        $this->fromEmail = $fromEmail;
    }

    /**
     * @param string $email
     * @param string|null $name
     * @return TemplatedEmail
     */
    public function setFrom(string $email, string $name = null): TemplatedEmail
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

    public function reset(): void
    {
        $this->template = null;
        $this->setFrom = null;
        $this->addTo = [];
        $this->addBcc = [];
        $this->addCc = [];
        $this->parameters = [];
        $this->setSubject = null;
        $this->addReplyTo = null;
        $this->addAttachment = [];
    }

    /**
     * @param string $template
     * @throws \Exception
     */
    public function setTemplate(string $template): void
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
    public function setParameters(array $parameters): TemplatedEmail
    {
        $parameters['domain'] = $this->httpRequest->getUrl()->host;
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * @param string $email
     * @param string|null $name
     * @return $this
     */
    public function addTo(string $email, string $name = null): TemplatedEmail
    {
        $this->addTo[] = [$email, $name];
        return $this;
    }

    /**
     * @param string $email
     * @param string|null $name
     * @return $this
     */
    public function addBcc(string $email, string $name = null): TemplatedEmail
    {
        $this->addBcc[] = [$email, $name];
        return $this;
    }

    /**
     * @param string $email
     * @param string|null $name
     * @return $this
     */
    public function addCc(string $email, string $name = null): TemplatedEmail
    {
        $this->addCc[] = [$email, $name];
        return $this;
    }

    /**
     * @param $subject
     * @return $this
     */
    public function setSubject(string $subject): TemplatedEmail
    {
        $this->setSubject = $subject;
        return $this;
    }

    /**.
     * @param string $email
     * @param string|null $name
     * @return TemplatedEmail
     */
    public function addReplyTo(string $email, string $name = null): TemplatedEmail
    {
        $this->addReplyTo = [$email, $name];
        return $this;
    }

    /**
     * @param string $file
     * @return TemplatedEmail
     */
    public function addAttachment(string $file): TemplatedEmail
    {
        $this->addAttachment[] = $file;
        return $this;
    }

    /**
     *
     */
    public function send(): void
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

        foreach ($this->addBcc AS $addBcc) {
            $mail->addTo($addBcc[0], $addBcc[1]);
        }

        foreach ($this->addCc AS $addCc) {
            $mail->addTo($addCc[0], $addCc[1]);
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


        $latte = $this->latteFactory->create();
        //$latte->setLoader(new StringLoader());
        if ($this->translator) {
            $latte->addFilter('translate', $this->translator === null ? null : [$this->translator, 'translate']);
        }

        UIMacros::install($latte->getCompiler());
        $latte->addProvider('uiControl', $this->linkGenerator);

        $mail->setHtmlBody($latte->renderToString($this->template, $this->parameters), $this->templateStorage.'/images');


        if ($this->debugMode) {
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
    private function mkdir(string $dir): void
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
