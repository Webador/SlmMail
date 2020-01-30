SlmMail
=======

[![Build Status](https://travis-ci.org/JouwWeb/SlmMail.png)](https://travis-ci.org/JouwWeb/SlmMail)
[![Latest Stable Version](https://poser.pugx.org/slm/mail/v/stable.png)](https://packagist.org/packages/slm/mail)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/JouwWeb/SlmMail/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/JouwWeb/SlmMail/?branch=master)

SlmMail is a module that integrates with various third-parties API to send mails. Integration is provided with the
API of those services. It does _not_ handle SMTP.

Here are the currently supported services:

* [Elastic Email](http://elasticemail.com) (complete)
* [Mailgun](http://www.mailgun.com) (complete)
* [Mandrill](http://mandrill.com) (complete)
* [Postmark](https://postmarkapp.com) (complete)
* [Postage](http://postageapp.com) (complete)
* [Send Grid](http://sendgrid.com) (nearly complete)
* [Spark Post](http://sparkpost.com) (nearly complete)
* [Amazon SES](http://aws.amazon.com/ses) (nearly complete, attachments are missing)

Requirements
------------

* PHP 7.2  
* [Laminas-MVC](https://github.com/laminas/laminas-mvc) or [Mezzio](https://github.com/mezzio/mezzio)
* [Amazon AWS ZF 2 Module](https://github.com/aws/aws-sdk-php-zf2): only if you plan to use Amazon SES service

Installation
------------

There are multiple ways to install and use this repo:

### Laminas MVC

1. Install with `composer require slm/mail`
1. Enable `SlmMail` in your `application.config.php`.
2. To use one of the transport layers, see the documentation in the [docs](https://github.com/JouwWeb/SlmMail/tree/master/docs) folder.

### Mezzio

1. Install with `composer require slm/mail`.
2. Because we register a _config-provider_ in the composer file, this will trigger an automatic installation. 
3. Copy one of the `.dist` files in the `./config/` directory of this repo, and move it to your `config/autoload` directory.


Usage 
-----

One can now fetch the dependencies from the service manager. And now compose a message:


```php
$message = new \Laminas\Mail\Message();
$message
    ->setTo('send@to')
    ->setFrom('send@by')
    ->setSubject('Subject')
    ->setBody('Contents');

$mandrillService = $container->get(\SlmMail\Service\MandrillService::class);
$mandrillService->send($message);
```` 

Documentation
-------------

Documentation for SlmMail is splitted for each provider:

* [Elastic Email](/docs/ElasticEmail.md)
* [Mailgun](/docs/Mailgun.md)
* [Mandrill](/docs/Mandrill.md)
* [Postage](/docs/Postage.md)
* [Postmark](/docs/Postmark.md)
* [SendGrid](/docs/SendGrid.md)
* [SparkPost](/docs/SparkPost.md)
* [Amazon SES](/docs/Ses.md)

Cook-book
---------

### How to send an HTML email ?

Every email providers used in SlmMail allow to send HTML emails. However, by default, if you set the mail's content
using the `setBody` content, this content will be considered as the plain text version as shown below:

```php
$message = new \Laminas\Mail\Message();

// This will be considered as plain text message, even if the string is valid HTML code
$message->setBody('Hello world');
```

To send a HTML version, you must specify the body as a MimeMessage, and add the HTML version as a MIME part, as
shown below:

```php
$message = new \Laminas\Mail\Message();

$htmlPart = new \Laminas\Mime\Part('<html><body><h1>Hello world</h1></body></html>');
$htmlPart->type = "text/html";

$textPart = new \Laminas\Mime\Part('Hello world');
$textPart->type = "text/plain";

$body = new \Laminas\Mime\Message();
$body->setParts(array($textPart, $htmlPart));

$message->setBody($body);
```

> For accessibility purposes, you should *always* provide both a text and HTML version of your mails.

### How to configure HttpClient with http_options and http_adapter

By default the adapter is Laminas\Http\Client\Adapter\Socket but you can override it with other adapter like this in your slm_mail.*.local.php

```php
'slm_mail' => array(
    // Here your email service provider options

    'http_adapter' => 'Laminas\Http\Client\Adapter\Proxy' // for example
)
```

If you want to change some options of your adapter please refer to you adapter class in var $config [here](https://github.com/laminas/laminas-http/tree/master/src/Client/Adapter) and override these in your slm_mail.*.local.php like this :

```php
'slm_mail' => array(
    // Here your email service provider options

    // example for Socket adapter
    'http_options' => array(
        'sslverifypeer' => false,
        'persistent' => true,
    ),
)
```

### Which provider should I choose?

We won't answer you :-)! Each provider has their own set of features. You should carefully read each website
to discover which one suits your needs best.

Who to thank?
-------------

[Jurian Sluiman](https://github.com/juriansluiman) and [Michaël Gallego](https://github.com/bakura10) did the initial work on creating this repo, and maintained it for a long time. 

Currently it is maintained by:

* [Roel van Duijnhoven](https://github.com/roelvanduijnhoven)
