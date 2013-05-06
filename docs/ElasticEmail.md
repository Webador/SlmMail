ElasticEmail
============

This transport layer forms the coupling between Zend\Mail and the Email Service Provider [Elastic Email](http://elasticemail.com).
The transport is a drop-in component and can be used to send email messages including Cc & Bcc addresses and attachments.

Installation
------------

It is assumed this module is already installed and enabled in your Zend Framework 2 project. If not, please read first the [installation instructions](../README.md) to do so.

Copy the `./vendor/juriansluiman/slm-mail/config/slm_mail.elastic_email.local.php.dist` to your `./config/autoload` folder (don't
forget to remove the .dist extension !) and update your username and API key.

Usage
-----

### Supported functionalities

SlmMail defines a new Message class, `SlmMail\Mail\Message\Provider\ElasticEmail`, that you can use to take advantage of
specific Elastic Email features. Here are a list of supported features.

#### Attachments

You can add any attachment to Elastic Email message. Contrary to most other providers, the content **MUST NOT** be a base64
encoded string of your content (you can omit the content type - third parameter of Attachment constructor - because
Elastic Email does not use it):

```php
$message    = new \SlmMail\Mail\Message\Provider\ElasticEmail();
$attachment = new \SlmMail\Mail\Message\Attachment('my-file.txt', base64_encode($file));
$message->addAttachment($attachment);
```

> Please note that Elastic Email attachments handling is a bit different than other email providers. Indeed, attachments
are not sent with the email, but through another request to Elastic Email API to upload attachment. This is done
automatically for you by SlmMail, but remember to reduce your attachments, because each attachment will generate one
more REST request to their API.

#### Template

Elastic Email has a primitive support for templates. Templates are created and stored from your Elastic Email account,
and you can reuse it by calling the `setTemplate` method:

```php
$message = new \SlmMail\Mail\Message\Provider\ElasticEmail();
$message->setTemplate('registration-mail');
```

### Use service locator

If you have access to the service locator, you can retrieve the Elastic Email transport:

```php
// As stated above, you can also create a specialized Elastic Email message for more features
$message = new \Zend\Mail\Message();

// set up Message here

$transport = $locator->get('SlmMail\Mail\Transport\ElasticEmailTransport');
$transport->send($message);
```

Of course, you are encouraged to inject this transport object whenever you need to send an email.

### Advanced usage

The transport layer depends on a service class `SlmMail\Service\ElasticEmailService` which sends the requests to the Elastic Email
server.

The service class is injected into the `SlmMail\Mail\Transport\HttpTransport` but you can get the service class yourself too:

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

### Error handling

Elastic Email error handling really sucks because it only send HTTP response with status code of 200 (Success), hence
making error handling very difficult for third-party libraries. Therefore, at the time of writing, there are no error
reporting for Elastic Email.
