Postmark
========

This transport layer forms the coupling between Zend\Mail and the Email Service Provider [Postmark](http://postmarkapp.com).
The transport is a drop-in component and can be used to send email messages including Cc & Bcc addresses and attachments.

Installation
------------

It is assumed this module is already installed and enabled in your Zend Framework 2 project. If not, please read first the [installation instructions](../README.md) to do so.

Copy the `./vendor/juriansluiman/slm-mail/config/slm_mail.postmark.local.php.dist` to your `./config/autoload` folder (don't
forget to remove the .dist extension!) and update your API key.

Usage
-----

### Supported functionalities

SlmMail defines a new Message class, `SlmMail\Mail\Message\Postmark`, that you can use to take advantage of
specific Postmark features. The Postmark transport from SlmMail can work with the standard `Zend\Mail\Message` objects, but if you want to use channels or templates, you must use the Postmark message class. Here are a list of supported features.

#### Attachments

You can add any attachment to a Postmark message. Attachments are handled just like you normally send emails with attachments. See the [Zend Framework 2 manual](http://framework.zend.com/manual/2.0/en/modules/zend.mail.message.html) for an extensive explanation of the Message class.

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

// You can use the \SlmMail\Mail\Message\Postage class
// But attachments work with Zend\Mail\Message too
$message = new \Zend\Mail\Message;
$message->setBody($body);
```

#### Tag

To simplify statistics on your account, you can add a single tag to sent messages, so that you can more easily
filter your messages on Postmark dashboard. Note that you can add only one tag per message.

```php
$message = new \SlmMail\Mail\Message\Postmark();
$message->setTag('registration-mail');
```

### Use service locator

If you have access to the service locator, you can retrieve the Postmark transport:

```php
// As stated above, you can also create a specialized Postmark message for more features
$message = new \Zend\Mail\Message();

// set up Message here

$transport = $locator->get('SlmMail\Mail\Transport\PostmarkTransport');
$transport->send($message);
```

Of course, you are encouraged to inject this transport object whenever you need to send an email.

### Advanced usage

The transport layer depends on a service class `SlmMail\Service\PostmarkService` which sends the requests to the Postage
server. However, this service implements also [bounces retrieval api](http://developer.postmarkapp.com/developer-bounces.html) so you can immediately check the
state of the sent email and act upon a bounced message.

The service class is injected into the `SlmMail\Mail\Transport\HttpTransport` but you can get the service class yourself too:

```php
$postageService = $locator->get('SlmMail\Service\PostmarkService');
$bounce         = $postage->getMessageReceipt($uid); // Example
```

The complete list of methods is:

* `send(Message $message)`: used by transport layer, $message instance of `Zend\Mail\Message` ([docs](http://developer.postmarkapp.com/developer-build.html))
* `getDeliveryStats()`: return summary of inactive emails and bounces by type ([docs](http://developer.postmarkapp.com/developer-bounces.html#get-delivery-stats))
* `getBounces($type, $inactive, $emailFilter, $paging)`: fetches a portion of bounces according to the specified input criteria, all arguments are optional and can be set to `null` ([docs](http://developer.postmarkapp.com/developer-bounces.html#get-bounces))
* `getBounce($id)`: get details about a single bounce, $id is the bounce id ([docs](http://developer.postmarkapp.com/developer-bounces.html#get-a-single-bounce))
* `getBounceDump($id)`: return the raw source of the bounce, $id is the bounce id ([docs](http://developer.postmarkapp.com/developer-bounces.html#get-bounce-dump))
* `getBounceTags()`: return a list of tags ([docs](http://developer.postmarkapp.com/developer-bounces.html#get-bounce-tags))
* `activateBounce($id)`:  activates a deactivated bounce, $id is the bounce id ([docs](http://developer.postmarkapp.com/developer-bounces.html#activate-a-bounce))

### Error handling

If an error occurs when a request is made to the Postmark API using `SlmMail\Service\PostmarkService`, some exceptions
are thrown. Each exception implements the `SlmMail\Exception\ExceptionInterface`, so you can easily filter each SlmMail
exceptions.

The following exceptions are thrown, depending on the errors returned by Postmark:

* `SlmMail\Service\Exception\InvalidCredentialsException`: this exception is thrown when invalid or no API key was sent.
* `SlmMail\Service\Exception\ValidationErrorException`: this exception is thrown when malformed or missing data is sent.
* `SlmMail\Service\Exception\RuntimeException`: this exception is thrown for other exceptions.
