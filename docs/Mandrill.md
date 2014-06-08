Mandrill
========

This transport layer forms the coupling between Zend\Mail and the Email Service Provider [Mandrill](http://mandrill.com).
The transport is a drop-in component and can be used to send email messages.

Mandrill supports To, Cc and Bcc recipients, but you can also hide the recipients using your Mandrill preferences ([learn here how to do it](http://help.mandrill.com/entries/21751312-Can-I-send-to-more-than-one-recipient-at-a-time-)),
or by using options (more on that latter).

Installation
------------

It is assumed this module is already installed and enabled in your Zend Framework 2 project. If not, please read first the [installation instructions](../README.md) to do so.

Copy the `./vendor/juriansluiman/slm-mail/config/slm_mail.mandrill.local.php.dist` to your `./config/autoload` folder (don't
forget to remove the .dist extension!) and update your API key.

Usage
-----

### Supported functionalities

SlmMail defines a new Message class, `SlmMail\Mail\Message\Mandrill`, that you can use to take advantage of
specific Mandrill features. The Mandrill transport from SlmMail can work with the standard `Zend\Mail\Message` objects, but if you want to use channels or templates, you must use the Mandrill message class. Here are a list of supported features.

#### Attachments

You can add any attachment to a Mandrill message. Attachments are handled just like you normally send emails with attachments. See the [Zend Framework 2 manual](http://framework.zend.com/manual/2.0/en/modules/zend.mail.message.html) for an extensive explanation of the Message class.

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

// You can use the \SlmMail\Mail\Message\Mandrill class
// But attachments work with Zend\Mail\Message too
$message = new \Zend\Mail\Message;
$message->setBody($body);
```

#### Images

You can add any images to Mandrill message. However, contrary to attachments, images added using
the following method ARE NOT attached to the mail. Rather, they can be used inside your Mandrill
template (using the following syntax: `<img src="cid:THIS_VALUE">`, where THIS_VALUE is the name
of the image you add) for visual purposes.

Note that the MIME-Type of images must start with `image/`.

```php
$image = new \Zend\Mime\Part(fopen($pathToImage, 'r'));
$image->type     = "image/png";
$image->filename = "my-image.png";

$message = new \SlmMail\Mail\Message\Mandrill();
$message->addImage($image);
```

#### Metadata

You can add any metadata to a Mandrill message. Metadata is set in the same way as template variables (either
globally or by recipient).

```php
$message->setGlobalMetadata(array('key1' => 'value1', 'key2' => 'value2'))
        ->setMetadata('foo@example.com', array('key1' => 'supervalue1'));
```

#### Options

Mandrill API allows you to add several options to your mail, to tweak if your mails must be tracked, if CSS should
be inline... To add an option:

```php
$message = new \SlmMail\Mail\Message\Mandrill();
$message->setOption('auto_html', true);

// Or multiple:
$message->setOptions(array('auto_html' => true, 'inline_css' => true));
```

Mandrill service will filter unknown options. Unsupported options with throw an exception `SlmMail\Mail\Message\Exception\InvalidArgumentException`. Here are the currently supported options:

* important: (boolean) whether or not this message is important, and should be delivered ahead of non-important messages
* track_opens: (boolean) whether or not to turn on open tracking for the message
* track_clicks: (boolean) whether or not to turn on click tracking for the message
* auto_text: (boolean) whether or not to automatically generate a text part for messages that are not given text
* auto_html: (boolean) whether or not to automatically generate an HTML part for messages that are not given HTML
* inline_css: (boolean) whether or not to automatically inline all CSS styles provided in the message HTML - only for HTML documents less than 256KB in size
* metadata: (array) a list of custom metadata that is indexable (deprecated, use `setGlobalMetadata` instead)
* url_strip_qs: (boolean) whether or not to strip the query string from URLs when aggregating tracked URL data
* preserve_recipients: (boolean) whether or not to expose all recipients in to "To" header for each email
* return_path_domain: (string) a custom domain to use for the messages's return-path
* tracking_domain: (string) a custom domain to use for tracking opens and clicks instead of mandrillapp.com
* signing_domain: (string) a custom domain to use for SPF/DKIM signing instead of mandrill (for "via" or "on behalf of" in email clients)
* subaccount: (string) a unique id of a subaccount (it must exists)
* merge: (boolean) whether to evaluate merge tags in the message. Will automatically be set to true if either merge_vars or global_merge_vars are provided.
* google_analytics_domains: (array) an array of strings indicating for which any matching URLs will automatically have Google Analytics parameters appended to their query string automatically.
* google_analytics_campaign: (string) optional string indicating the value to set for the utm_campaign tracking parameter. If this isn't provided the email's from address will be used instead.
* view_content_link: (boolean): whether or not to remove content logging for sensitive emails

#### Tags

To simplify statistics on your account, you can add one or several tags to sent messages, so that you
can more easily filter your messages on Mandrill dashboard.

```php
$message = new \SlmMail\Mail\Message\Mandrill();
$message->setTags(array('registration-mail', 'my-designed-mail'));

// Or add one:
$message->addTag('registration-mail');
```

#### Templates

Mandrill supports templates. Templates are created and stored on Mandrill servers, and you can reuse them on server
side. You can pass optional variables that get injected (for more information about how Mandrill templates work, please
refer to their official documentation). There are two kinds of variables: global variables and variables. Variables
are indexed per recipient and override the global variables. This is useful when you need to send multiple messages
at once, while wanting to customize some parts of the mail per recipient (like name...).

```php
$message = new \SlmMail\Mail\Message\Mandrill();
$message->setTemplate('foo')
        ->setGlobalVariables(array('key1' => 'value1', 'key2' => 'value2'))
        ->setVariables('foo@example.com', array('key1' => 'supervalue1'));
```

Mandrill also supports template content. Those are placeholder defined on your templates that you can define
programatically through SlmMail :

```php
$message = new \SlmMail\Mail\Message\Mandrill();
$message->setTemplate('foo')
        ->setTemplateContent(array('header' => '<header><h1>This is an example</h1></header>'))
```

#### Scheduled messages

Mandrill natively supports the ability to schedule message in the future. You can schedule a message using the optional
sendAt parameter in both `send` and `sendTemplate` methods:

```php
$sendAt = new DateTime();
$sendAt->modify('+1 day'); // send message in 1 day

$message = new \SlmMail\Mail\Message\Mandrill();

$mandrillService->send($message, $sendAt);
```

You can also use the various `getScheduledMessages`, `cancelScheduledMessage` and `rescheduleMessage` from the
Mandrill service to interact with this API.

Internally, the date is automatically converted to UTC and to the appropriate date format that Mandrill is expected.

Please note that this feature of Mandrill introduces additional fees, and require you to have a positive balance
account ([see here for more details](http://help.mandrill.com/entries/24331201-Can-I-schedule-a-message-to-send-at-a-specific-time-).

### Use service locator

If you have access to the service locator, you can retrieve the Mandrill transport:

```php
// As stated above, you can also create a specialized Mandrill message for more features
$message = new \Zend\Mail\Message();

// set up Message here

$transport = $locator->get('SlmMail\Mail\Transport\MandrillTransport');
$transport->send($message);
```

Of course, you are encouraged to inject this transport object whenever you need to send an email. Note that if you
have defined a template, it will automatically choose the right method in the service. This is completely transparent
to the user.

### Advanced usage

The transport layer depends on a service class `SlmMail\Service\MandrillService` which sends the requests to the Mandrill
server. However, this service implements also a major part of the Mandrill API.

The service class is injected into the `SlmMail\Mail\Transport\HttpTransport` but you can get the service class yourself too:

```php
$mandrillService = $locator->get('SlmMail\Service\MandrillService');
$ping            = $mandrillService->pingUser(); // Example
```

Messages functions:

* `send(Message $message, DateTime $sendAt = null)`: used by transport layer, $message instance of `Zend\Mail\Message` ([docs](https://mandrillapp.com/api/docs/messages.html#method=send))
* `sendTemplate(Message $message, DateTime $sendAt = null)`: used by transport layer if a $message has a template ([docs](https://mandrillapp.com/api/docs/messages.html#method=send-template))
* `getMessageInfo($id)`: get all the information about a message by its Mandrill id ([docs](https://mandrillapp.com/api/docs/messages.JSON.html#method=info))
* `getScheduledMessages($to = '')`: get all the scheduled messages, optionally filtered by an email To address ([docs](https://mandrillapp.com/api/docs/messages.JSON.html#method=list-scheduled))
* `cancelScheduledMessage($id = '')`: cancel a scheduled message by its Mandrill id ([docs](https://mandrillapp.com/api/docs/messages.JSON.html#method=cancel-scheduled))
* `rescheduleMessage($id, DateTime $sendAt)`: reschedule an already scheduled message by its Mandrill id and new date ([docs](https://mandrillapp.com/api/docs/messages.JSON.html#method=reschedule))

Users functions:

* `getUserInfo()`: get the information about the API-connected user ([docs](https://mandrillapp.com/api/docs/users.html#method=info))
* `pingUser()`: validate an API key and respond to a ping ([docs](https://mandrillapp.com/api/docs/users.html#method=ping))

Senders functions:

* `getSenders()`: get the senders that have tried to use this account, both verified and unverified ([docs](https://mandrillapp.com/api/docs/senders.html#method=list))
* `getSenderDomains()`: get the sender domains that have been added to this account ([docs](https://mandrillapp.com/api/docs/senders.html#method=domains))
* `getSenderInfo($address)`: get more detailed information about a single sender, including aggregates of recent stats ([docs](https://mandrillapp.com/api/docs/senders.html#method=info))
* `getRecentSenderInfo($address)`: get recent detailed information (last 30 days) about a single sender ([docs](https://mandrillapp.com/api/docs/senders.html#method=time-series))

Tags functions:

* `getTags()`: get all of the user-defined tag information ([docs](https://mandrillapp.com/api/docs/tags.html#method=list))
* `deleteTag($tag)`: delete a tag permanently ([docs](https://mandrillapp.com/api/docs/tags.html#method=delete))
* `getTagInfo($tag)`: get more detailed information about a single tag, including aggregates of recent stats ([docs](https://mandrillapp.com/api/docs/tags.html#method=info))
* `getRecentTagInfo($tag)`: get recent detailed information (last 30 days) about a single tag, including aggregates of recent stats ([docs](https://mandrillapp.com/api/docs/tags.html#method=time-series))

Subaccounts functions:

* `addSubaccount($id, $name = '', $notes = '', $customQuota = null)`: add a new subaccount ([docs](https://mandrillapp.com/api/docs/subaccounts.JSON.html#method=add))
* `deleteSubaccount($id)`: delete an existing subaccount ([docs](https://mandrillapp.com/api/docs/subaccounts.JSON.html#method=delete))
* `getSubaccountInfo($id)`: get info about a specific subaccount ([docs](https://mandrillapp.com/api/docs/subaccounts.JSON.html#method=info))
* `getSubaccounts($prefix = '')`: get all subaccounts, optionally filtered by a prefix ([docs](https://mandrillapp.com/api/docs/subaccounts.JSON.html#method=list))
* `pauseAccount($id)`: pause a subaccount ([docs](https://mandrillapp.com/api/docs/subaccounts.JSON.html#method=pause))
* `resumeAccount($id)`: resume a subaccount ([docs](https://mandrillapp.com/api/docs/subaccounts.JSON.html#method=resume))
* `updateAccount($id, $name = '', $notes = '', $customQuota = null)`: update an existing subaccount ([docs](https://mandrillapp.com/api/docs/subaccounts.JSON.html#method=update))

Rejection blacklist functions:

* `addRejectionBlacklist($email, $subaccount = '', $comment = '')`: add an email rejection blacklist ([docs](https://mandrillapp.com/api/docs/rejects.html#method=add))
* `deleteRejectionBlacklist($email, $subaccount = '')`: delete an email rejection blacklist ([docs](https://mandrillapp.com/api/docs/rejects.html#method=delete))
* `getRejectionBlacklist($email, $includeExpired = false, $subaccount = '')`: get all the email rejection blacklist ([docs](https://mandrillapp.com/api/docs/rejects.html#method=list))

Rejection whitelist functions:

* `addRejectionWhitelist($email)`: add an email rejection whitelist ([docs](https://mandrillapp.com/api/docs/whitelists.html#method=add))
* `deleteRejectionWhitelist($email)`: delete an email rejection whitelist ([docs](https://mandrillapp.com/api/docs/whitelists.html#method=delete))
* `getRejectionWhitelist($email)`: get all the email rejection whitelist ([docs](https://mandrillapp.com/api/docs/whitelists.html#method=list))

URLs functions:

* `getMostClickedUrls($q = '')`: get the 100 most clicked URLs optionally filtered by search query ([docs](https://mandrillapp.com/api/docs/urls.html#method=list))
* `getRecentUrlInfo($url)`: get the recent history (hourly stats for the last 30 days) for a url ([docs](https://mandrillapp.com/api/docs/urls.html#method=time-series))

Template functions:

* `addTemplate($name, Address $address = null, $subject = '', $html = '', $text = '', array $labels = array())`: add a new template to Mandrill ([docs](https://mandrillapp.com/api/docs/templates.html#method=add))
* `updateTemplate($name, Address $address = null, $subject = '', $html = '', $text = '', array $labels = array())`: update an existing template ([docs](https://mandrillapp.com/api/docs/templates.html#method=update))
* `deleteTemplate($template)`: delete an existing template ([docs](https://mandrillapp.com/api/docs/templates.html#method=delete))
* `getTemplates($label = '')`: get all registered templates on Mandrill ([docs](https://mandrillapp.com/api/docs/templates.html#method=list))
* `deleteTemplate($template)`: delete an existing template ([docs](https://mandrillapp.com/api/docs/templates.html#method=delete))
* `getTemplateInfo($template)`: get template info ([docs](https://mandrillapp.com/api/docs/templates.html#method=info))
* `getRecentTemplateInfo($template)`: get recent template info (last 30 days) ([docs](https://mandrillapp.com/api/docs/templates.html#method=time-series))
* `renderTemplate($name, array $content, array $variables = array())`: render an existing template stored on Mandrill ([docs](https://mandrillapp.com/api/docs/templates.html#method=render))

### Error handling

If an error occurs when a request is made to the Mandrill API using `SlmMail\Service\MandrillService`, some exceptions
are thrown. Each exception implements the `SlmMail\Exception\ExceptionInterface`, so you can easily filter each SlmMail
exceptions.

The following exceptions are thrown, depending on the errors returned by Mandrill:

* `SlmMail\Service\Exception\InvalidCredentialsException`: this exception is thrown when invalid or no API key was sent.
* `SlmMail\Service\Exception\ValidationErrorException`: this exception is thrown when malformed or missing data is sent.
* `SlmMail\Service\Exception\UnknownTemplateException`: this exception is thrown when using a template that does not exist on Mandrill.
* `SlmMail\Service\Exception\RuntimeException`: this exception is thrown for other exceptions.
