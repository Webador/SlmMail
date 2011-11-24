SlmMail
===
Version 0.0.1 Created by Jurian Sluiman

Introduction
---
SlmMail is an extension to the available Zend\Mail component of the Zend Framework 2. The Zend\Mail allows different transports to send the email and SlmMail provides the transport layer for the following email services:

1. Amazon SES
2. CritSend
3. Elastic Email
4. Mailchimp STS
5. PostageApp
6. Postmark
7. SendGrid
8. SMTP.com

Requirements
---
* Zend Framework 2

Installation
---
Clone this project into your `./vendors/` directory and enable it in your `application.config.php`. To use one of the transport layers, see the documentation in the `docs` folder.
