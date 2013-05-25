Amazon SES
==========

This transport layer forms the coupling between Zend\Mail and the Email Service Provider [Amazon SES](http://aws.amazon.com/ses/).
The transport is a drop-in component and can be used to send email messages with Cc & Bcc addresses (please note that
currently, Amazon SES does not support attachments).

SlmMail provides a very thin wrapper around the official AWS client (you may also use the official client directly
if you want). However, using SlmMail has the advantage that you can switch very quickly from one provider to another.

> As the date of writing, Amazon SES is only supported in "us-east-1" region. Make sure to reflect this into your AWS
config parameters.

Installation
------------

Contrary to other providers, you need to install another module in order to use Amazon SES: the [official AWS SDK
module for Zend Framework 2](https://github.com/aws/aws-sdk-php-zf2). Please refer to the module's documentation to
install it.

The reason for using the official SDK is that the official AWS module allows you to centralize your credentials in
one single place instead of scattering it into several modules. If you are using AWS services, you are likely using
it for other services than Amazon SES.

It is also assumed that SlmMail is already installed and enabled in your Zend Framework 2 project. If not, please read first the [installation instructions](../README.md) to do so.

Usage
-----

### Supported functionalities

Amazon SES scope is very basic and does not support more feature than what is provided by default by `Zend\Mail\Message` class.
Therefore, contrary to other email providers, no message class was defined for SES.

### Use service locator

If you have access to the service locator, you can retrieve the Amazon SES transport:

```php
$message = new \Zend\Mail\Message();

// set up Message here

$transport = $locator->get('SlmMail\Mail\Transport\SesTransport');
$transport->send($message);
```

Of course, you are encouraged to inject this transport object whenever you need to send an email.

### Advanced usage

The transport layer depends on a service class `SlmMail\Service\SesService` which sends the requests to the Amazon SES
server. However, this service implements also [the api](http://docs.aws.amazon.com/ses/latest/APIReference/API_Operations.html).

The service class is injected into the `SlmMail\Mail\Transport\HttpTransport` but you can get the service class yourself too:

```php
$sesService = $locator->get('SlmMail\Service\SesService');
$bounce     = $sesService->verifyEmailIdentity('myaddress@gmail.com'); // Example
```

Message functions:

* `send(Message $message)`: used by transport layer, $message instance of `Zend\Mail\Message` ([docs](http://help.postageapp.com/kb/api/send_message))
* `getSendQuota`: get the user's current sending limits ([docs](http://docs.aws.amazon.com/ses/latest/APIReference/API_GetSendQuota.html))
* `getSendStatistics`: get the user's sending statistics. The result is a list of data points, representing the last two weeks of sending activity ([docs](http://docs.aws.amazon.com/ses/latest/APIReference/API_GetSendStatistics.html))

Identities and emails functions:

* `getIdentities($identityType = '', $maxItems = 50, $nextToken = '')`: get a list containing all of the identities (email addresses and domains) for a specific AWS Account, regardless of verification status ([docs](http://docs.aws.amazon.com/ses/latest/APIReference/API_ListIdentities.html))
* `deleteIdentity($identity)`: delete the specified identity (email address or domain) from the list of verified identities ([docs](http://docs.aws.amazon.com/ses/latest/APIReference/API_DeleteIdentity.html))
* `getIdentityDkimAttributes(array $identities)`: get the current status of Easy DKIM signing for an entity ([docs](http://docs.aws.amazon.com/ses/latest/APIReference/API_GetIdentityDkimAttributes.html))
* `getIdentityNotificationAttributes(array $identities)`: Given a list of verified identities (email addresses and/or domains), returns a structure describing identity notification attributes ([docs](http://docs.aws.amazon.com/ses/latest/APIReference/API_GetIdentityNotificationAttributes.html))
* `getIdentityVerificationAttributes(array $identities)`: given a list of identities (email addresses and/or domains), returns the verification status and (for domain identities) the verification token for each identity ([docs](http://docs.aws.amazon.com/ses/latest/APIReference/API_GetIdentityVerificationAttributes.html))
* `setIdentityDkimEnabled($identity, $dkimEnabled)`: enables or disables Easy DKIM signing of email sent from an identity ([docs](http://docs.aws.amazon.com/ses/latest/APIReference/API_SetIdentityDkimEnabled.html))
* `setIdentityFeedbackForwardingEnabled($identity, $forwardingEnabled)`: given an identity (email address or domain), enables or disables whether Amazon SES forwards feedback notifications as email ([docs](http://docs.aws.amazon.com/ses/latest/APIReference/API_SetIdentityFeedbackForwardingEnabled.html))
* `setIdentityNotificationTopic($identity, $notificationType, $snsTopic = '')`: given an identity (email address or domain), sets the Amazon SNS topic to which Amazon SES will publish bounce and complaint notifications for emails sent with that identity as the Source ([docs](http://docs.aws.amazon.com/ses/latest/APIReference/API_SetIdentityNotificationTopic.html))
* `verifyDomainDkim($domain)`: get a set of DKIM tokens for a domain ([docs](http://docs.aws.amazon.com/ses/latest/APIReference/API_VerifyDomainDkim.html))
* `verifyDomainIdentity($identity)`: verify a domain identity ([docs](http://docs.aws.amazon.com/ses/latest/APIReference/API_VerifyDomainIdentity.html))
* `verifyEmailIdentity($address)`: verify an email address. This action causes a confirmation email message to be sent to the specified address ([docs](http://docs.aws.amazon.com/ses/latest/APIReference/API_VerifyEmailIdentity.html))

> Some functions of the official SDK (`deleteVerifiedEmailAddress`, `listVerifiedEmailAddresses` and `verifyEmailAddress`) have
been deprecated in May 2012. This is why are not part of SlmMail wrapper.

### Error handling

If an error occurs when a request is made to the Amazon SES API using `SlmMail\Service\SesService`, some exceptions
are thrown. Each exception implements the `SlmMail\Exception\ExceptionInterface`, so you can easily filter each SlmMail
exceptions.

The following exceptions are thrown, depending on the errors returned by Amazon SES:

* `SlmMail\Service\Exception\InvalidCredentialsException`: this exception is thrown when security tokens are wrong.
* `SlmMail\Service\Exception\ValidationErrorException`: this exception is thrown when malformed, invalid or missing data is sent (for instance when someone try to send an email using an unverified sender).
* `SlmMail\Service\Exception\RuntimeException`: this exception is thrown for other exceptions.

You can get the exact message and error code the following way:

```php
catch (\SlmMail\Service\Exception\InvalidCredentialsException $e) {
    $message = $e->getMessage();
    $code    = $e->getCode();
}
```
