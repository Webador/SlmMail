SlmMail
===
Version 0.0.1 Created by Jurian Sluiman

Introduction
---
SlmMail is an extension to the available Zend\Mail component of the Zend Framework 2. The Zend\Mail allows different transports to send the email and SlmMail provides the transport layer for the following email services:

1. [Amazon SES](https://github.com/juriansluiman/SlmMail/blob/master/docs/SES.md)
2. [CritSend](https://github.com/juriansluiman/SlmMail/blob/master/docs/CritSend.md)
3. [Elastic Email](https://github.com/juriansluiman/SlmMail/blob/master/docs/ElasticEmail.md)
4. [Mailchimp STS](https://github.com/juriansluiman/SlmMail/blob/master/docs/Mailchimp.md)
5. [PostageApp](https://github.com/juriansluiman/SlmMail/blob/master/docs/PostageApp.md)
6. [Postmark](https://github.com/juriansluiman/SlmMail/blob/master/docs/Postmark.md)
7. [SendGrid](https://github.com/juriansluiman/SlmMail/blob/master/docs/SendGrid.md)

Requirements
---
* [Zend Framework 2](https://github.com/zendframework/zf2)

Installation
---
Clone this project into your `./vendors/` directory and enable it in your `application.config.php`. To use one of the transport layers, see the documentation in the `docs` folder.
