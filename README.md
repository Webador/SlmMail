SlmMail
=======
Version 0.2.0 Created by Jurian Sluiman

Introduction
------------

SlmMail is a module that integrates with various third-parties API to send mails. Integration is provided with the
API of those services. It does not handle SMTP.

Please note that SlmMail only supports Transactional services. Services for campaign marketing emails (like MailChimp
or MailJet) are out-of-the scope of this module.

Here are the currently supported services:

* Amazon SES (complete)
* Elastic Email (complete)
* Mailgun (nearly complete - advanced features like Routes are not supported -)
* Mandrill (complete)
* Postmark (complete)
* Postage (complete)

Requirements
------------
* PHP 5.4: SlmMail makes use of traits (hence we dropped PHP 5.3 support)
* [Zend Framework 2](https://github.com/zendframework/zf2)
* [Amazon AWS ZF 2 Module]: only if you plan to use Amazon SES service

Installation
------------
Add "juriansluiman/slm-mail" to your composer.json file and update your dependencies. Enable SlmMail in your
`application.config.php`. To use one of the transport layers, see the documentation in the [docs](https://github.com/juriansluiman/SlmMail/tree/master/docs) folder.

If you do not have a composer.json file in the root of your project, copy the contents below and put that into a
file called `composer.json` and save it in the root of your project:

```
{
    "require": {
        "juriansluiman/slm-mail": "dev-master"
    },
    "minimum-stability": "dev"
}
```

Then execute the following commands in a CLI:

```
curl -s http://getcomposer.org/installer | php
php composer.phar install
```

Now you should have a `vendor` directory, including a `juriansluiman/slm-mail`. In your bootstrap code, make sure
you include the `vendor/autoload.php` file to properly load the SlmMail module.

### Amazon SES

If you want to use Amazon SES, you need to install the official AWS ZF 2 module. Please refer to the documentation
of Amazon SES in the [docs](https://github.com/juriansluiman/SlmMail/tree/master/docs) folder.

Cook-book
---------

### How to send an HTML email ?

Every email providers used in SlmMail allow to send HTML emails. However, by default, if you set the mail's content
using the `setBody` content, this content will be considered as the plain text version as shown above:

```php
$message = new \Zend\Mail\Message();

// This will be considered as plain text message, even if the string is valid HTML code
$message->setBody('Hello world');
```

To send a HTML version, you must specify the body as a MimeMessage, and add the HTML version as a MIME part, as
shown above:

```php
$message = new \Zend\Mail\Message();

$htmlPart = new \Zend\Mime\Part('<html><body><h1>Hello world</h1></body></html>');
$htmlPart->type = "text/html";

$textPart = new \Zend\Mime\Part('Hello world');
$textPart->type = "text/plain";

$body = new \Zend\Mime\Message();
$body->setParts(array($textPart, $htmlPart));

$message->setBody($body);
```

> For accessibility purposes, you should *always* provide both a text and HTML version of your mails.

TODO
----
 1. SendGrid support
 2. Better exception handling for Amazon SES to work the same as other services
 3. More tests
