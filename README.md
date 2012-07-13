SlmMail
===
Version 0.0.2 Created by Jurian Sluiman

Introduction
---
SlmMail is an extension to the available Zend\Mail component of the Zend Framework 2. The Zend\Mail component allows different transports to send the email and SlmMail provides the transport layer for the following email services:

 1. [Amazon SES](https://github.com/juriansluiman/SlmMail/blob/master/docs/AmazonSes.md) (*implementation not completed*)
 2. [Elastic Email](https://github.com/juriansluiman/SlmMail/blob/master/docs/ElasticEmail.md)
 3. [Mailchimp STS](https://github.com/juriansluiman/SlmMail/blob/master/docs/Mailchimp.md) (*implementation not completed*)
 4. [Postage](https://github.com/juriansluiman/SlmMail/blob/master/docs/Postage.md)
 5. [Postmark](https://github.com/juriansluiman/SlmMail/blob/master/docs/Postmark.md)
 6. [SendGrid](https://github.com/juriansluiman/SlmMail/blob/master/docs/SendGrid.md)

Requirements
---
* [Zend Framework 2](https://github.com/zendframework/zf2)

Installation
---
Add "juriansluiman/slm-mail" to your composer.json file and update your dependencies. Enable SlmMail in your `application.config.php`. To use one of the transport layers, see the documentation in the [docs](https://github.com/juriansluiman/SlmMail/tree/master/docs) folder.

If you do not have a composer.json file in the root of your project, copy the contents below and put that into a file called `composer.json` and save it in the root of your project:

```
{
    "require": {
        "juriansluiman/slm-mail": "dev-master"
    },
    "minimum-stability": "dev"
}
```

The execute the following commands in a CLI:

```
curl -s http://getcomposer.org/installer | php
php composer.phar install
```

Now you should have a `vendor` directory, including a `juriansluiman/slm-mail`. In your bootstrap code, make sure you include the `vendor/autoload.php` file to properly load the SlmMail module.

TODO
---
 1. Amazon SES & Mailchimp: complete implementation
 2. ElasticEmail & Postage & Postmark & SendGrid: send attachments with email
 3. ElasticEmail: better error detection for API calls
 4. SendGrid: set some arguments as optional
 5. SendGrid: implementation filter API calls