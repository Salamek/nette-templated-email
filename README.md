# Nette Files

This is a simple templated email system for [Nette Framework](http://nette.org/)

## Instalation

The best way to install salamek/nette-templated-email is using  [Composer](http://getcomposer.org/):


```sh
$ composer require salamek/nette-templated-email:@dev
```

Then you have to register extension in `config.neon`.

```yaml
extensions:
	templatedEmail: Salamek\TemplatedEmail\DI\TemplatedEmailExtension

templatedEmail:
    sendEmailDebugStorage: %tempDir%/sendEmails
    templateStorage: %appDir%/emailTemplates
    fromName: 'dravemCMS'
    fromEmail: 'info@example.com'

```
