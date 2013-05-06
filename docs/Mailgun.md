Mailgun
========

This transport layer forms the coupling between Zend\Mail and the Email Service Provider [Mailgun](http://www.mailgun.com).
The transport is a drop-in component and can be used to send email messages with Cc & Bcc addresses and attachments.

Installation
------------

It is assumed this module is already installed and enabled in your Zend Framework 2 project. If not, please read first the [installation instructions](https://github.com/juriansluiman/SlmMail/blob/master/README.md) to do so.

Copy the `./vendor/juriansluiman/slm-mail/config/slm_mail.mailgun.local.php.dist` to your `./config/autoload` folder (don't
forget to remove the .dist extension !) and update your API key.

Usage
-----

### Supported functionalities

SlmMail defines a new Message class, `SlmMail\Mail\Message\Provider\Mailgun`, that you can use to take advantage of
specific Mailgun features. Here are a list of supported features.

#### Attachments

You can add any attachment to Mailgun message. The content **MUST NOT* be a base64 encoded string of your content:

```php
$message    = new \SlmMail\Mail\Message\Provider\Mailgun();
$attachment = new \SlmMail\Mail\Message\Attachment('my-file.txt', file_get_contents('path/to/file'), 'text/plain');
$message->addAttachment($attachment);
```

#### Options

Mailgun API allows you to add several options to your mail, to tweak if your mails must be tracked, when it should
be delivered... To add an option:

```php
$message = new \SlmMail\Mail\Message\Provider\Mailgun();
$message->setOption('tracking_clicks', true);

// Or multiple:
$message->setOptions(array('tracking_clicks' => true, 'tracking_opens' => true));
```

Mailgun service will automatically filter unknown options. Here are the currently supported options:

* dkim: (string) enables/disables DKIM signatures on per-message basis. Pass *yes* or *n*-o*.
* delivery_time: (string) desired time of delivery. The date format must be encoded according to RFC 2822.
* test_mode: (string) enables sending in test mode. Pass *yes* if needed.
* tracking: (string) toggles tracking on a per-message basis. Pass *yes* or *no*.
* tracking_clicks: (string) toggles clicks tracking on a per-message basis. Pass *yes* or *no*.
* tracking_opens: (string) toggles opens tracking on a per-message basis. Pass *yes* or *no*.

#### Tags

To simplify statistics on your account, you can add one or several tags (up to 3) to sent messages, so that you
can more easily filter your messages on Mailgun dashboard.

```php
$message = new \SlmMail\Mail\Message\Provider\Mailgun();
$message->setTag('registration-mail');

// Or multiple:
$message->addTags(array('registration-mail', 'my-designed-mail'));
```

### Use service locator

If you have access to the service locator, you can retrieve the Mailgun transport:

```php
// As stated above, you can also create a specialized Mailgun message for more features
$message = new \Zend\Mail\Message();

// set up Message here

$transport = $locator->get('SlmMail\Mail\Transport\MailgunTransport');
$transport->send($message);
```

Of course, you are encouraged to inject this transport object whenever you need to send an email. Note that if you
have defined a template, it will automatically choose the right method in the service. This is completely transparent
to the user.

### Advanced usage

The transport layer depends on a service class `SlmMail\Service\MailgunService` which sends the requests to the Mandrill
server. However, this service implements also a major part of the Mailgun API.

The service class is injected into the `SlmMail\Mail\Transport\HttpTransport` but you can get the service class yourself too:

```php
$mailgunService = $locator->get('SlmMail\Service\MailgunService');
$bounce         = $mailgunService->getBounce('my@example.com'); // Example
```

Messages functions:

* `send(Message $message)`: used by transport layer, $message instance of `Zend\Mail\Message` ([docs](http://help.postageapp.com/kb/api/send_message))
* `getLogs($limit = 100, $offset = 0)`: get log entries ([docs](http://documentation.mailgun.com/api-logs.html))

Spam functions:

* `getSpamComplaints($limit = 100, $offset = 0)`: get spam complaints (this happens when recipients click "report spam") ([docs](http://documentation.mailgun.com/api-complaints.html))
* `getSpamComplaint($address)`: get a single spam complaint by a given email address. This is useful to check if a particular user has complained ([docs](http://documentation.mailgun.com/api-complaints.htmls))
* `addSpamComplaint($address)`: add an address to the complaints table ([docs](http://documentation.mailgun.com/api-complaints.html))
* `deleteSpamComplaint($address)`: delete an address from spam complaint ([docs](http://documentation.mailgun.com/api-complaints.html))

Bounce functions:

* `getBounces($limit = 100, $offset = 0)`: get bounces ([docs](http://documentation.mailgun.com/api-bounces.html))
* `getBounce($address)`: get a single bounce event by a given email address ([docs](http://documentation.mailgun.com/api-bounces.html))
* `addBounce($address, $code = 550, $error = '')`: add a bounce ([docs](http://documentation.mailgun.com/api-bounces.html))
* `deleteBounce($address)`: delete a bounce ([docs](http://documentation.mailgun.com/api-bounces.html))

### Error handling

If an error occurs when a request is made to the Mailgun API using `SlmMail\Service\Mailgun`, some exceptions
are thrown. Each exception implements the `SlmMail\Exception\ExceptionInterface`, so you can easily filter each SlmMail
exceptions.

The following exceptions are thrown, depending on the errors returned by Mailgun:

* `SlmMail\Service\Exception\InvalidCredentialsException`: this exception is thrown when invalid or no API key was sent.
* `SlmMail\Service\Exception\ValidationErrorException`: this exception is thrown when malformed or missing data is sent.
* `SlmMail\Service\Exception\RuntimeException`: this exception is thrown for other exceptions.
