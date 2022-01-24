SparkPost
=========

This transport layer forms the coupling between Laminas\Mail and the Email Service Provider [SparkPost](http://sparkpost.com).
The transport is a drop-in component and can be used to send email messages including Cc & Bcc addresses and attachments.
The SparkPost API docks are here:  https://developers.sparkpost.com/api/ .


Installation
------------

It is assumed this module is already installed and enabled in your Zend Framework 2 project. If not, please read first the [installation instructions](../README.md) to do so.

Copy the `./vendor/slm/mail/config/slm_mail.spark_post.local.php.dist` to your `./config/autoload` folder (don't
forget to remove the .dist extension!) and update your API key.

Usage
-----

### Supported functionalities

In addition to the default `Laminas\Mail\Message` class, SlmMail offers the SparkPost-specific `SlmMail\Message\SparkPost` class as input for sending email. The latter supports native templates and attachments for the more advanced use cases.

#### Subaccounts

SparkPost allows multiple *subaccounts* to be used in addition the main account. SlmMail supports subaccounts on SparkPost through the `'subaccount'` option which can be passed along to any method of the `SparkPostService`. Please note that the numeric subaccount **ID** must be used, not the symbolic name of the subaccount.

#### Sending domains

The following SparkPostService-methods let you register, verify and remove sending domains:

* registerSendingDomain: Registers a new sending domain using the DKIM-keypair and selector that were given in the `'dkim'` option. If the sending domain already exists in SparkPost, the existing sending domain is preserved and the function returns successfully.

* removeSendingDomain: Remove a sending domain. If the sending domains does not exist on SparkPost the function returns successfully.

* verifySendingDomain: Requests verification of the DKIM-record of a previously registered sending domain.

#### Attachments

You can add any attachment to a `SparkPost` message using the `addAttachment` method. Please note that the contents of the attachment must be base64-encoded *before* adding the attachment.

SparkPost limits the total message size to ~20 MB, including base64-encoded attachments. In fact, SparkPost's advice is not to include any attachments to increase the chances of landing in the inbox.

### Use service locator

If you have access to the service locator, you can retrieve the SparkPost transport:

```php
// As stated above, you can also create a specialized SparkPost message for more features
$message = new \Laminas\Mail\Message();

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
