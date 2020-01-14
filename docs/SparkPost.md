
SparkPost


This transport layer forms the coupling between Zend\Mail and the Email Service Provider [SparkPost](http://sparkpost.com).
The transport is a drop-in component and can be used to send email messages including Cc & Bcc addresses and attachments.
The SparkPost api docks are here:  https://developers.sparkpost.com/api/ .


Installation
------------

It is assumed this module is already installed and enabled in your Zend Framework 2 project. If not, please read first the [installation instructions](../README.md) to do so.

Copy the `./vendor/juriansluiman/slm-mail/config/slm_mail.spark_post.local.php.dist` to your `./config/autoload` folder (don't
forget to remove the .dist extension!) and update your API key.

Usage
-----

### Supported functionalities

SlmMail consumes for SparkPost just the standard `Zend\Mail\Message` object.


#### Attachments

You can add any attachment to a SparkPost message. Attachments are handled just like you normally send emails with attachments. See the [Zend Framework 2 manual](http://framework.zend.com/manual/2.0/en/modules/zend.mail.message.html) for an extensive explanation of the Message class.
=======
SlmMail consumes for SparkPosrt just the standard `Zend\Mail\Message` object.

#### Attachments

You can add any attachment to a SparkPost message. Attachments are handled just like you normally send emails with attachments. See the [Zend Framework 2 manual](http://framework.zend.com/manual/2.0/en/modules/zend.mail.message.html) for an extensive explanation of the Message class.
>>>>>>> Temporary merge branch 2

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

If you have access to the service locator, you can retrieve the SparkPost transport:

```php
// As stated above, you can also create a specialized SparkPost message for more features
$message = new \Zend\Mail\Message();

// set up Message here

$transport = $locator->get('SlmMail\Mail\Transport\SparkPostTransport');
$transport->send($message);
```

Of course, you are encouraged to inject this transport object whenever you need to send an email.


The transport layer depends on a service class `SlmMail\Service\SparkPostService` which sends the requests to the SparkPost
server.

The service class is injected into the `SlmMail\Mail\Transport\HttpTransport` but you can get the service class yourself too:

```php
$sparkpostService = $locator->get('SlmMail\Service\SparkPostService');
```

### Error handling

If an error occurs when a request is made to the SparkPost API using `SlmMail\Service\SparkPostService`, some exceptions
are thrown. Each exception implements the `SlmMail\Exception\ExceptionInterface`, so you can easily filter each SlmMail
exceptions.

SparkPost error handling is rather poor, therefore only one, generic exception is thrown for each error:

* `SlmMail\Service\Exception\RuntimeException`: this exception is thrown for other exceptions.

You can get the exact message and error code the following way:

```php
catch (\SlmMail\Service\Exception\RuntimeException $e) {
    $message = $e->getMessage();
    $code    = $e->getCode();
}
```
