AlphaMail
=========

This transport layer forms the coupling between Zend\Mail and the Email Service Provider [AlphaMail](http://amail.io).

Installation
------------

It is assumed this module is already installed and enabled in your Zend Framework 2 project. If not, please read first the [installation instructions](../README.md) to do so.

Copy the `./vendor/juriansluiman/slm-mail/config/slm_mail.alpha_mail.local.php.dist` to your `./config/autoload` folder (don't
forget to remove the .dist extension !) and update your username and API key.

Usage
-----

> IMPORTANT: AlphaMail REST API is a bit different from other providers. Indeed, it does not support sending emails
directly, by specifying HTML and/or text message. Instead, it requires that you define what they call "project", which
is a message associated with a template. Those projects can either be through their website or programmatically by
using AlphaMail service. AlphaMail templates use a custom templating language which is called "Comlang". If you plan
to create templates programmatically, please refer to the documentation of this templating language.

### Supported functionalities

SlmMail defines a new Message class, `SlmMail\Mail\Message\Provider\AlphaMail`, that you can use to take advantage of
specific AlphaMail features. Here are a list of supported features.

#### Project

AlphaMail has a concept of "project". Each sent message MUST be associated with a project id:

```php
$message = new \SlmMail\Mail\Message\Provider\AlphaMail();
$message->setProject(2);
```

### Use service locator

If you have access to the service locator, you can retrieve the AlphaMail transport:

```php
// As stated above, you can also create a specialized AlphaMail message for more features
$message = new \Zend\Mail\Message();

// set up Message here

$transport = $locator->get('SlmMail\Mail\Transport\AlphaMailTransport');
$transport->send($message);
```

Of course, you are encouraged to inject this transport object whenever you need to send an email.

### Advanced usage

The transport layer depends on a service class `SlmMail\Service\AlphaMail` which sends the requests to the AlphaMail
server.

The service class is injected into the `SlmMail\Mail\Transport\HttpTransport` but you can get the service class yourself too:

```php
$alphaMailService = $locator->get('SlmMail\Service\AlphaMailService');
$tokens           = $alphaMailService->getTokens(); // Example
```

The complete list of methods is:

Messages functions:

* `send(Message $message)`: used by transport layer, $message instance of `Zend\Mail\Message` ([docs](http://app.amail.io/#/docs/api/))
* `getEmailStatus($id)`: get status for sent email. You can retrieve the identifier as a return value of `send` method ([docs](http://app.amail.io/#/docs/api/))

Projects functions:

* `createProject($name, $subject, $templateId, $signatureId = 0)`: create a new project ([docs](http://app.amail.io/#/docs/api/))
* `deleteProject($id)`: delete an existing project ([docs](http://app.amail.io/#/docs/api/))
* `getProjects()`: get all projects ([docs](http://app.amail.io/#/docs/api/))
* `getProject($id)`: get a project by its identifier ([docs](http://app.amail.io/#/docs/api/))

Templates functions:

* `createTemplate($name, $text = '', $html = '')`: create a new template ([docs](http://app.amail.io/#/docs/api/))
* `deleteTemplate($id)`: delete an existing template ([docs](http://app.amail.io/#/docs/api/))
* `getTemplates()`: get all templates ([docs](http://app.amail.io/#/docs/api/))
* `getTemplate($id)`: get a template by its identifier ([docs](http://app.amail.io/#/docs/api/))

Signatures functions:

* `createSignature($name, $domain)`: create a new signature ([docs](http://app.amail.io/#/docs/api/))
* `deleteSignature($id)`: delete an existing signature ([docs](http://app.amail.io/#/docs/api/))
* `getSignatures()`: get all signatures ([docs](http://app.amail.io/#/docs/api/))
* `getSignature($id)`: get a signature by its identifier ([docs](http://app.amail.io/#/docs/api/))

Token functions:

* `getTokens()`: retrieve all the tokens (they determine API access to your AlphaMail account) ([docs](http://app.amail.io/#/docs/api/))

### Error handling

If an error occurs when a request is made to the AlphaMail API using `SlmMail\Service\AlphaMailService`, some exceptions
are thrown. Each exception implements the `SlmMail\Exception\ExceptionInterface`, so you can easily filter each SlmMail
exceptions.

The following exceptions are thrown, depending on the errors returned by AlphaMail:

* `SlmMail\Service\Exception\InvalidCredentialsException`: this exception is thrown when invalid or no API key was sent.
* `SlmMail\Service\Exception\ValidationErrorException`: this exception is thrown when malformed or missing data is sent.
* `SlmMail\Service\Exception\RuntimeException`: this exception is thrown for other exceptions.
