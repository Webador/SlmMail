SendGrid
=======

This transport layer forms the coupling between Zend\Mail and the Email Service Provider [SendGrid](http://sendgrid.com).
The transport is a drop-in component and can be used to send email messages including Cc & Bcc addresses and attachments.

Installation
------------

It is assumed this module is already installed and enabled in your Zend Framework 2 project. If not, please read first the [installation instructions](../README.md) to do so.

Copy the `./vendor/juriansluiman/slm-mail/config/slm_mail.send_grid.local.php.dist` to your `./config/autoload` folder (don't
forget to remove the .dist extension!) and update your API key.

Usage
-----

### Supported functionalities

SlmMail consumes for SendGrid just the standard `Zend\Mail\Message` object.

#### Attachments

You can add any attachment to a SendGrid message. Attachments are handled just like you normally send emails with attachments. See the [Zend Framework 2 manual](http://framework.zend.com/manual/2.0/en/modules/zend.mail.message.html) for an extensive explanation of the Message class.

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

$message = new \Zend\Mail\Message;
$message->setBody($body);
```

### Use service locator

If you have access to the service locator, you can retrieve the SendGrid transport:

```php
// As stated above, you can also create a specialized SendGrid message for more features
$message = new \Zend\Mail\Message();

// set up Message here

$transport = $locator->get('SlmMail\Mail\Transport\SendGridTransport');
$transport->send($message);
```

Of course, you are encouraged to inject this transport object whenever you need to send an email.

### Advanced usage

The transport layer depends on a service class `SlmMail\Service\SendGridService` which sends the requests to the SendGrid
server. However, this service implements also [the api](http://sendgrid.com/docs/API_Reference/Web_API/index.html) so you can
immediately check the state of the sent email and act upon a bounced message.

The service class is injected into the `SlmMail\Mail\Transport\HttpTransport` but you can get the service class yourself too:

```php
$sendgridService = $locator->get('SlmMail\Service\SendGridService');
$bounce          = $sendgrid->getStatistics(); // Example
```

The complete list of methods is:

* `send(Message $message)`: used by transport layer, $message instance of `Zend\Mail\Message` ([docs](http://sendgrid.com/docs/API_Reference/Web_API/mail.html))
* `getStatistics($date, $startDate, $endDate, $aggregate)`: get statistics of your account ([docs](http://sendgrid.com/docs/API_Reference/Web_API/statistics.html))
* `getBounces($date, $days, $startDate, $endDate, $email, $limit, $offset)`: get the list of bounces ([docs](http://sendgrid.com/docs/API_Reference/Web_API/bounces.html))
* `deleteBounces($startDate, $endDate, $email)`: delete an address from the bounce list ([docs](http://sendgrid.com/docs/API_Reference/Web_API/bounces.html))
* `countBounces($startDate, $endDate)`: count the number of bounces ([docs](http://sendgrid.com/docs/API_Reference/Web_API/bounces.html))
* `getSpamReports($date, $days, $startDate, $endDate, $email, $limit, $offset)`: get entries from the spam report list ([docs](http://sendgrid.com/docs/API_Reference/Web_API/spam_reports.html))
* `deleteSpamReport($email)`: delete an address from the spam report list ([docs](http://sendgrid.com/docs/API_Reference/Web_API/spam_reports.html))
* `getBlocks($date, $days, $startDate, $endDate)`: get the list of blocks ([docs](http://sendgrid.com/docs/API_Reference/Web_API/blocks.html))
* `deleteBlock($email)`: delete an address from the blocks list ([docs](http://sendgrid.com/docs/API_Reference/Web_API/blocks.html))

### Error handling

If an error occurs when a request is made to the SendGrid API using `SlmMail\Service\SendGridService`, some exceptions
are thrown. Each exception implements the `SlmMail\Exception\ExceptionInterface`, so you can easily filter each SlmMail
exceptions.

SendGrid error handling is rather poor, therefore only one, generic exception is thrown for each error:

* `SlmMail\Service\Exception\RuntimeException`: this exception is thrown for other exceptions.

You can get the exact message and error code the following way:

```php
catch (\SlmMail\Service\Exception\RuntimeException $e) {
    $message = $e->getMessage();
    $code    = $e->getCode();
}
```
