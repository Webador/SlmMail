Elastic Email
============

This transport layer forms the coupling between Zend\Mail and the Email Service Provider [Elastic Email](http://elasticemail.com).
The transport is a drop-in component and can be used to send email messages including Cc & Bcc addresses and attachments.

Installation
------------

It is assumed this module is already installed and enabled in your Zend Framework 2 project. If not, please read first the [installation instructions](../README.md) to do so.

Copy the `./vendor/juriansluiman/slm-mail/config/slm_mail.elastic_email.local.php.dist` to your `./config/autoload` folder (don't
forget to remove the .dist extension!) and update your username and API key.

Usage
-----

### Supported functionalities

SlmMail defines a new Message class, `SlmMail\Mail\Message\ElasticEmail`, that you can use to take advantage of
specific Elastic Email features. The Elastic Email transport from SlmMail can work with the standard `Zend\Mail\Message` objects, but if you want to use channels or templates, you must use the Elastic Email message class. Here are a list of supported features.

#### Attachments

You can add any attachment to an Elastic Email message. Attachments are handled just like you normally send emails with attachments. See the [Zend Framework 2 manual](http://framework.zend.com/manual/2.0/en/modules/zend.mail.message.html) for an extensive explanation of the Message class.

```php
$text = new \Zend\Mime\Part($textContent);
$text->type = "text/plain";

$html = new \Zend\Mime\Part($htmlMarkup);
$html->type = "text/html";

$pdf = new \Zend\Mime\Part(fopen($pathToPdf, 'r'));
$pdf->type     = "application/pdf";
$pdf->filename = "my-attachment.pdf";

$body = new \Zend\Mime\Message;
$body->setParts(array($text, $html, $pdf));

// You can use the \SlmMail\Mail\Message\ElasticEmail class
// But attachments work with Zend\Mail\Message too
$message = new \Zend\Mail\Message;
$message->setBody($body);
```

> Please note that Elastic Email attachments handling is a bit different than other email providers. Attachments
are not sent within the email, but uploaded first to the Elastic Email server. This is done automatically for you
by SlmMail, but remember to reduce your attachments, because each attachment will generate onemore REST request
to their API.

#### Template

Elastic Email has support for templates. Templates are created and stored from your Elastic Email account, and
you can reuse it by calling the `setTemplate` method:

```php
$message = new \SlmMail\Mail\Message\ElasticEmail();
$message->setTemplate('registration-mail');
```

#### Channel

Elastic Email has support for channels. Channels can be used to group emails send from your Elastic Email account, and
you can set the channel by calling the `setChannel` method:

```php
$message = new \SlmMail\Mail\Message\ElasticEmail();
$message->setChannel('registration');
```

### Use service locator

If you have access to the service locator, you can retrieve the Elastic Email transport:

```php
// You can also use the Elastic Email message class
$message = new \Zend\Mail\Message();

// set up Message here

$transport = $locator->get('SlmMail\Mail\Transport\ElasticEmailTransport');
$transport->send($message);
```

Of course, you are encouraged to inject this transport object whenever you need to send an email.

### Advanced usage

The transport layer depends on a service class `SlmMail\Service\ElasticEmailService` which sends the requests to the Elastic Email server. The service class is injected into the `SlmMail\Mail\Transport\HttpTransport` but you can get the service class yourself too:

```php
$elasticEmailService = $locator->get('SlmMail\Service\ElasticEmailService');
$accountDetails      = $elasticEmailService->getAccountDetails(); // Example
```

The complete list of methods is:

* `send(Message $message)`: used by transport layer, $message instance of `Zend\Mail\Message` ([docs](http://elasticemail.com/api-documentation/send))
* `getEmailStatus($id)`: get status for sent email. You can retrieve the identifier as a return value of `send` method ([docs](http://elasticemail.com/api-documentation/status))
* `uploadAttachment(Attachment $attachment)`: upload an attachment to Elastic Email ([docs](http://elasticemail.com/api-documentation/attachments-upload))
* `getAccountDetails()`: get account details (credit left...) ([docs](http://elasticemail.com/api-documentation/account-details))
* `getActiveChannels($format = 'xml')`: get a list of active channels ([docs](http://elasticemail.com/api-documentation/channels))
* `deleteChannel($name, $format = 'xml')`: delete a channel ([docs](http://elasticemail.com/api-documentation/channels))

The `getEmailStatus()`, `getAccountDetails()` and `getActiveChannels()` methods will return an array with the fields of information. Elastic Email returns an XML string and SlmMail converts the XML values to this array.

### Error handling

Elastic Email error handling is a non-standard approach. Elastic Email returns always the HTTP 200 code and has no uniform way of exceptional cases. SlmMail tries to handle these cases as best as possible, in most cases a `SlmMail\Service\Exception\RuntimeException` is thrown, but there might be some edge cases where the errors are not caught by SlmMail.