Postage
=======

This transport layer forms the coupling between Zend\Mail and the Email Service Provider [Postage](http://postageapp.com).
The transport is a drop-in component and can be used to send email messages including attachments but *without* Cc & Bcc
addresses.

Installation
------------

It is assumed this module is already installed and enabled in your Zend Framework 2 project. If not, please read first the [installation instructions](../README.md) to do so.

Copy the `./vendor/juriansluiman/slm-mail/config/slm_mail.postage.local.php.dist` to your `./config/autoload` folder (don't
forget to remove the .dist extension!) and update your API key.

Usage
-----

### Supported functionalities

SlmMail defines a new Message class, `SlmMail\Mail\Message\Postage`, that you can use to take advantage of
specific Postage features. The Postage transport from SlmMail can work with the standard `Zend\Mail\Message` objects, but if you want to use channels or templates, you must use the Postage message class. Here are a list of supported features.

#### Attachments

You can add any attachment to a Postage message. Attachments are handled just like you normally send emails with attachments. See the [Zend Framework 2 manual](http://framework.zend.com/manual/2.0/en/modules/zend.mail.message.html) for an extensive explanation of the Message class.

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

#### Template

Postage supports templates. Templates are created and stored on Postage servers, and you can reuse them on server
side. You can pass optional variables that get injected (for more information about how Postage templates work, please
refer to their official documentation):

```php
$message = new \SlmMail\Mail\Message\Postage();
$message->setTemplate('foo')
        ->setVariables(array('key1' => 'value1', 'key2' => 'value2'));
```

### Use service locator

If you have access to the service locator, you can retrieve the Postage transport:

```php
// As stated above, you can also create a specialized Postage message for more features
$message = new \Zend\Mail\Message();

// set up Message here

$transport = $locator->get('SlmMail\Mail\Transport\PostageTransport');
$transport->send($message);
```

Of course, you are encouraged to inject this transport object whenever you need to send an email.

### Advanced usage

The transport layer depends on a service class `SlmMail\Service\PostageService` which sends the requests to the Postage
server. However, this service implements also [the api](http://help.postageapp.com/kb/api/api-overview) so you can
immediately check the state of the sent email and act upon a bounced message.

The service class is injected into the `SlmMail\Mail\Transport\HttpTransport` but you can get the service class yourself too:

```php
$postageService = $locator->get('SlmMail\Service\PostageService');
$bounce         = $postage->getMessageReceipt($uid); // Example
```

The complete list of methods is:

* `send(Message $message)`: used by transport layer, $message instance of `Zend\Mail\Message` ([docs](http://help.postageapp.com/kb/api/send_message))
* `getMessageReceipt($uid)`: get receipt of message, $uid the returned uid from `send()` ([docs](http://help.postageapp.com/kb/api/get_message_receipt))
* `getMessageTransmission($uid)`: get data on individual recipients' delivery and open status ([docs](http://help.postageapp.com/kb/api/get_message_transmissions))
* `getMetrics`: get metrics for a project ([docs](http://help.postageapp.com/kb/api/get_metrics))
* `getMethodList()`: get list of available methods ([docs](http://help.postageapp.com/kb/api/get_method_list))
* `getAccountInfo()`: get information about the account ([docs](http://help.postageapp.com/kb/api/get_account_info))
* `getProjectInfo()`: get information about the project ([docs](http://help.postageapp.com/kb/api/get_project_info))
* `getMessageUids()`: get a list of all message UIDs within your project ([docs](http://help.postageapp.com/kb/api/get_messages))

### Error handling

If an error occurs when a request is made to the Postage API using `SlmMail\Service\PostageService`, some exceptions
are thrown. Each exception implements the `SlmMail\Exception\ExceptionInterface`, so you can easily filter each SlmMail
exceptions.

Postage error handling is rather poor, therefore only one, generic exception is thrown for each error:

* `SlmMail\Service\Exception\RuntimeException`: this exception is thrown for other exceptions.

You can get the exact message and error code the following way:

```php
catch (\SlmMail\Service\Exception\RuntimeException $e) {
    $message = $e->getMessage();
    $code    = $e->getCode();
}
```
