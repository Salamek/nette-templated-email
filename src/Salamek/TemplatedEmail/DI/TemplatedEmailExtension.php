<?php

namespace Salamek\TemplatedEmail\DI;

use Nette;
use Salamek\TemplatedEmail\TemplatedEmail;


/**
 * Class TemplatedEmailExtension
 * @package Salamek\TemplatedEmail\DI
 */
class TemplatedEmailExtension extends Nette\DI\CompilerExtension
{
    /** @var string */
    public static $prefix = 'templatedEmail';

    /** @var bool */
    private $debugMode;

    public function __construct(bool $debugMode)
    {
        $this->debugMode = $debugMode;
    }

    public function loadConfiguration(): void
    {
        $config = $this->getConfig();
        $builder = $this->getContainerBuilder();
        $builder->addDefinition($this->prefix(self::$prefix))
            ->setFactory(TemplatedEmail::class, [
                $this->debugMode,
                $config['sendEmailDebugStorage'],
                $config['templateStorage'],
                $config['fromName'],
                $config['fromEmail']
            ]);
    }
}
