Mailgun
========

This transport layer forms the coupling between Zend\Mail and the Email Service Provider [Mailgun](http://www.mailgun.com).
The transport is a drop-in component and can be used to send email messages with Cc & Bcc addresses and attachments.

Installation
------------

It is assumed this module is already installed and enabled in your Zend Framework 2 project. If not, please read first the [installation instructions](../README.md) to do so.

Copy the `./vendor/juriansluiman/slm-mail/config/slm_mail.mailgun.local.php.dist` to your `./config/autoload` folder (don't
forget to remove the .dist extension!) and update your API key.

Usage
-----

### Supported functionalities

SlmMail defines a new Message class, `SlmMail\Mail\Message\Mailgun`, that you can use to take advantage of
specific Mailgun features. The Mailgun transport from SlmMail can work with the standard `Zend\Mail\Message` objects, but if you want to use channels or templates, you must use the Mailgun message class. Here are a list of supported features.

#### Attachments

You can add any attachment to a Mailgun message. Attachments are handled just like you normally send emails with attachments. See the [Zend Framework 2 manual](http://framework.zend.com/manual/2.0/en/modules/zend.mail.message.html) for an extensive explanation of the Message class.

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

// You can use the \SlmMail\Mail\Message\Mailgun class
// But attachments work with Zend\Mail\Message too
$message = new \Zend\Mail\Message;
$message->setBody($body);
```

#### Options

Mailgun API allows you to add several options to your mail. Possible options are the tracking of mails and further delivery options. To add an option, use the `setOption()` method:

```php
$message = new \SlmMail\Mail\Message\Mailgun();
$message->setOption('tracking_clicks', true);

// Or multiple:
$message->setOptions(array('tracking_clicks' => true, 'tracking_opens' => true));
```

Mailgun service will filter unknown options. Unsupported options with throw an exception `SlmMail\Mail\Message\Exception\InvalidArgumentException`. Here are the currently supported options:

* dkim: (string) enables/disables DKIM signatures on per-message basis. Pass *yes* or *no*.
* delivery_time: (string) desired time of delivery. The date format must be encoded according to RFC 2822.
* test_mode: (string) enables sending in test mode. Pass *yes* if needed.
* tracking: (string) toggles tracking on a per-message basis. Pass *yes* or *no*.
* tracking_clicks: (string) toggles clicks tracking on a per-message basis. Pass *yes* or *no*.
* tracking_opens: (string) toggles opens tracking on a per-message basis. Pass *yes* or *no*.

#### Tags

To simplify statistics on your account, you can add one or several tags to sent messages, so that you
can more easily filter your messages on Mailgun dashboard. Note Mailgun only allows up to 3 tags to be
attached.

```php
$message = new \SlmMail\Mail\Message\Mailgun();
$message->setTags(array('registration-mail', 'my-designed-mail'));

// Or add one:
$message->addTag('registration-mail');
```

### Batch sending

Batch sending allows you to send an e-mail to a group from one single API call. By using recipient variables,
you can define custom parameters for each recipient.

```php
$message = new \SlmMail\Mail\Message\Mailgun();
$message->addTo('demo1@mailgun.com');
$message->addTo('demo2@mailgun.com');

$message->setSubject("Hi %recipient.name%");
$message->setBody("Hi, activate your account by clicking on http://mailgun.com/activate/%recipient.key%");

// Set all variables for demo1@mailgun.com
$message->setRecipientVariables('demo1@mailgun.com', array('name' => 'Demo', 'key' => 'key1'));

// Or add one by one for demo2@mailgun.com:
$message->addRecipientVariable('demo2@mailgun.com', 'name', 'Demo');
$message->addRecipientVariable('demo2@mailgun.com', 'key', 'key2');
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

The transport layer depends on a service class `SlmMail\Service\MailgunService` which sends the requests to the Mailgun
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

Route functions:

* `addRoute($description, $expression, $actions, $priority = 0)`: add a new route ([docs](http://documentation.mailgun.com/api-routes.html))
* `deleteRoute($id)`: delete an existing route ([docs](http://documentation.mailgun.com/api-routes.html))
* `getRoutes($limit = 100, $offset = 0)`: get routes ([docs](http://documentation.mailgun.com/api-routes.html))
* `getRoute($id)`: get route by its identifier ([docs](http://documentation.mailgun.com/api-routes.html))
* `updateRoute($id, $description, $expression, $actions, $priority = 0)`: update an existing route ([docs](http://documentation.mailgun.com/api-routes.html))


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
