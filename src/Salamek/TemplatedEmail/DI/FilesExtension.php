<?php

namespace Salamek\TemplatedEmail\DI;

use Nette;
use Nette\DI\Compiler;
use Nette\DI\Configurator;


if (!class_exists('Nette\DI\CompilerExtension')) {
    class_alias('Nette\Config\CompilerExtension', 'Nette\DI\CompilerExtension');
    class_alias('Nette\Config\Compiler', 'Nette\DI\Compiler');
    class_alias('Nette\Config\Configurator', 'Nette\DI\Configurator');
}

/**
 * Class TemplatedEmailExtension
 * @package Salamek\TemplatedEmail\DI
 */
class TemplatedEmailExtension extends Nette\DI\CompilerExtension
{

    public function loadConfiguration()
    {
        $config = $this->getConfig();
        $builder = $this->getContainerBuilder();


        $builder->addDefinition($this->prefix('templatedEmail'))
            ->setClass('Salamek\TemplatedEmail\TemplatedEmail', [$config['sendEmailDebugStorage'], $config['templateStorage'], $config['fromName'], $config['fromEmail']])
            ->addSetup('setSendEmailDebugStorage', [$config['sendEmailDebugStorage']])
            ->addSetup('setTemplateStorage', [$config['templateStorage']])
            ->addSetup('setFromName', [$config['fromName']])
            ->addSetup('setFromEmail', [$config['fromEmail']]);
    }


    /**
     * @param \Nette\Config\Configurator $config
     * @param string $extensionName
     */
    public static function register(Configurator $config, $extensionName = 'templatedEmailExtension')
    {
        $config->onCompile[] = function (Configurator $config, Compiler $compiler) use ($extensionName) {
            $compiler->addExtension($extensionName, new TemplatedEmailExtension());
        };
    }


    /**
     * {@inheritdoc}
     */
    public function getConfig(array $defaults = [], $expand = true)
    {

        $defaults = array(
            'sendEmailDebugStorage' => $this->getContainerBuilder()->parameters['tempDir'] . '/sendEmails',
            'templateStorage' => $this->getContainerBuilder()->parameters['appDir'] . '/emailTemplates',
            'fromName' => 'Templated email',
            'fromEmail' => 'templeted.email@example.com'
        );

        return parent::getConfig($defaults, $expand);
    }
}
