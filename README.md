SlmMail
=======
Version 0.2.0 Created by Jurian Sluiman

Introduction
------------

SlmMail is a module that integrates with various third-parties API to send mails. Integration is provided with the
API of those services. It does not handle SMTP.

Here are the currently supported services:

* Mandrill

Requirements
------------
* PHP 5.4: SlmMail makes use of traits (hence we dropped PHP 5.3 support)
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
