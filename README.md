SlmMail
===
Version 0.0.1 Created by Jurian Sluiman

Introduction
---
SlmMail is an extension to the available Zend\Mail component of the Zend Framework 2. The Zend\Mail allows different transports to send the email and SlmMail provides the transport layer for the following email services:

 1. [Amazon SES](https://github.com/juriansluiman/SlmMail/blob/master/docs/AmazonSes.md)
 2. [Elastic Email](https://github.com/juriansluiman/SlmMail/blob/master/docs/ElasticEmail.md)
 3. [Mailchimp STS](https://github.com/juriansluiman/SlmMail/blob/master/docs/Mailchimp.md)
 4. [Postage](https://github.com/juriansluiman/SlmMail/blob/master/docs/Postage.md)
 5. [Postmark](https://github.com/juriansluiman/SlmMail/blob/master/docs/Postmark.md)
 6. [SendGrid](https://github.com/juriansluiman/SlmMail/blob/master/docs/SendGrid.md)

Requirements
---
* [Zend Framework 2](https://github.com/zendframework/zf2)

Installation
---
Clone this project into your `./vendors/` directory and enable it in your `application.config.php`. To use one of the transport layers, see the documentation in the [docs](https://github.com/juriansluiman/SlmMail/tree/master/docs) folder.

TODO
---
 1. Amazon SES & Mailchimp: complete implementation
 2. ElasticEmail & Postage & Postmark & SendGrid: send attachments with email
 3. ElasticEmail & Postage: better error detection for API calls
 4. SendGrid: set some arguments as optional
 5. SendGrid: implementation filter API calls