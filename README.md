SlmMail
=======
Version 1.0.0 Created by Jurian Sluiman and Michaël Gallego

Introduction
------------

SlmMail is a module that integrates with various third-parties API to send mails. Integration is provided with the
API of those services. It does not handle SMTP.

Please note that SlmMail only supports Transactional services. Services for campaign marketing emails (like MailChimp
or MailJet) are out-of-the scope of this module.

Here are the currently supported services:

* AlphaMail (complete)
* Amazon SES (complete)
* Elastic Email (complete)
* Mailgun (nearly complete - advanced features like Routes are not supported -)
* Mandrill (complete)
* Postmark (complete)
* Postage (complete)
* Send Grid (nearly complete)

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

### Pricing comparison

Here is a table of prices for each service providers (if they are outdated please create an issue). Of course, you
are encouraged to have a look at features instead of just pricing, but this may be important too ;-) :

Provider     | Price | Example with 100 000 emails/month |
------------ | ----- | ---------------------------------
Alpha Mail   | up to 6 000 emails/month: free, up to 40 000 emails/month: 9.49 $/month, up to 100 000 emails/month: 79.49$/month, 199.49 $/month, up to 700 000 emails/month: 399.49$/month | 79.49 $ / month
Amazon SES   | 2 000 messages/day for free, then 0.10$ for 1000 | 4 $ / month
Elastic Mail | depends on what you send AND your reputation accross time | between 95$ and 50$ / month
Mailgun      | five plans depending on a minimum required per month. Free plan: 200 mails / day ; standard plan: 1$ for 1000, minimum of 19$ per month required ; express plan: 0.50$ per 1000, minimum of 59$ per month required ; priority plan: 0.40$ per 1000, minimum of 199$ per month required ; first-class plan: 0.10$ per 1000, minimum of 499$ per month required | 10$ / month with the first-class plan or 40$ / month with the priority plan
Mandrill     | up to 12 000 emails per month for free, then 0.20$ per 1000 for next 1 milion mail, then 0.15 $ per 1000 for next 5 million mails, then 0.10 $ per 1000 | 17.60$ / month
Postmark     | 1.50 $ per 1000 mails. You can get discounted prices by buying more credits at once (lowest price is 0.50$ per 1000) | 150 $ / month
Postage      | five plans with a maximum of emails for each. Carrier pigeon: 9$ / month, with a maximum of 10 000 emails / month ; Falcon: 29$ / month with a maximum of 40 000 / month ; Owl: 79$ / month with a maximum of 100 000 / month ; Eagle: 199$ / month with a maximum of 400 000 / month ; Pterodactyl: 399$ / month with a maximum of 1 000 000 per month | 79 $ / month
Send Grid    | five plans with a maximum of emails for each. Bronze: 9.95$ / month with a maximum of 40 000 emails ; Silver: 79.95 $ / month with a maximum of 100 000 / month ; Gold: 199.95 $ / month with a maximum of 300 000 / month ; Platinum: 399.95 $ / month with a maximum of 700 000 / month | 79.95 $


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
 1. Better exception handling for Amazon SES to work the same as other services
 2. More tests
