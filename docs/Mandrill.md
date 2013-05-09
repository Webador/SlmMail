Mandrill
========

This transport layer forms the coupling between Zend\Mail and the Email Service Provider [Mandrill](http://mandrill.com).
The transport is a drop-in component and can be used to send email messages *without* Cc & Bcc addresses and attachments.

Mandrill only supports To recipients currently, but you can hide the recipients using your Mandrill preferences ([learn here how to do it](http://help.mandrill.com/entries/21751312-Can-I-send-to-more-than-one-recipient-at-a-time-)),
or by using options (more on that latter).

Installation
------------

It is assumed this module is already installed and enabled in your Zend Framework 2 project. If not, please read first the [installation instructions](https://github.com/juriansluiman/SlmMail/blob/master/README.md) to do so.

Copy the `./vendor/juriansluiman/slm-mail/config/slm_mail.mandrill.local.php.dist` to your `./config/autoload` folder (don't
forget to remove the .dist extension !) and update your API key.

Usage
-----

### Supported functionnalities

SlmMail defines a new Message class, `SlmMail\Mail\Message\Provider\Mandrill`, that you can use to take advantage of
specific Mandrill features. Here are a list of supported features.

#### Attachments

You can add any attachment to Mandrill message. The content **MUST BE** a base64 encoded string of your content:

```php
$message    = new \SlmMail\Mail\Message\Provider\Mandrill();
$attachment = new \SlmMail\Mail\Message\Attachment('my-file.txt', base64_encode($file), 'text/plain');
$message->addAttachment($attachment);
```

#### Images

You can add any images to Mandrill message. The content must be a base64 encoded string of your content. Images
are similar to attachments, instead that you can use them more easily in Mandrill template (in your templates
hosted on Mandrill, you can use `<img src="cid:THIS_VALUE">`, where THIS_VALUE is the name of the image you add).

Note that the MIME-Type of images must start by image/.

```php
$message = new \SlmMail\Mail\Message\Provider\Mandrill();
$image   = new \SlmMail\Mail\Message\Attachment('my-image.png', base64_encode($file), 'image/png');
$message->addImage($image);
```

#### Options

Mandrill API allows you to add several options to your mail, to tweak if your mails must be tracked, if CSS should
be inline... To add an option:

```php
$message = new \SlmMail\Mail\Message\Provider\Mandrill();
$message->setOption('auto_html', true);

// Or multiple:
$message->setOptions(array('auto_html' => true, 'inline_css' => true));
```

Mandrill service will automatically filter unknown options. Here are the currently supported options:

* important: (boolean) whether or not this message is important, and should be delivered ahead of non-important messages
* track_opens: (boolean) whether or not to turn on open tracking for the message
* track_clicks: (boolean) whether or not to turn on click tracking for the message
* auto_text: (boolean) whether or not to automatically generate a text part for messages that are not given text
* auto_html: (boolean) whether or not to automatically generate an HTML part for messages that are not given HTML
* inline_css: (boolean) whether or not to automatically inline all CSS styles provided in the message HTML - only for HTML documents less than 256KB in size
* url_strip_qs: (boolean) whether or not to strip the query string from URLs when aggregating tracked URL data
* preserve_recipients: (boolean) whether or not to expose all recipients in to "To" header for each email
* tracking_domain: (string) a custom domain to use for tracking opens and clicks instead of mandrillapp.com
* signing_domain: (string) a custom domain to use for SPF/DKIM signing instead of mandrill (for "via" or "on behalf of" in email clients)
* merge: (boolean) whether to evaluate merge tags in the message. Will automatically be set to true if either merge_vars or global_merge_vars are provided.
* google_analytics_domains: (array) an array of strings indicating for which any matching URLs will automatically have Google Analytics parameters appended to their query string automatically.
* google_analytics_campaign: (string) optional string indicating the value to set for the utm_campaign tracking parameter. If this isn't provided the email's from address will be used instead.

#### Tags

To simplify statistics on your account, you can add one or several tags to sent messages, so that you can more easily
filter your messages on Mandrill dashboard.

```php
$message = new \SlmMail\Mail\Message\Provider\Mandrill();
$message->setTag('registration-mail');

// Or multiple:
$message->addTags(array('registration-mail', 'my-designed-mail'));
```

#### Templates

Mandrill supports templates. Templates are created and stored on Mandrill servers, and you can reuse them on server
side. You can pass optional variables that get injected (for more information about how Mandrill templates work, please
refer to their official documentation). There are two kinds of variables: global variables and variables. Variables
are indexed per recipient and override the global variables. This is useful when you need to send multiple messages
at once, while wanting to customize some parts of the mail per recipient (like name...).

```php
$message = new \SlmMail\Mail\Message\Provider\Mandrill();
$message->setTemplate('foo')
        ->setGlobalVariables(array('key1' => 'value1', 'key2' => 'value2'))
        ->setVariables('foo@example.com', array('key1' => 'supervalue1'));
```

Mandrill also supports template content. Those are placeholder defined on your templates that you can define
programatically through SlmMail :

```php
$message = new \SlmMail\Mail\Message\Provider\Mandrill();
$message->setTemplate('foo')
        ->setTemplateContent(array('header' => '<header><h1>This is an example</h1></header>'))
```

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

* `send(Message $message)`: used by transport layer, $message instance of `Zend\Mail\Message` ([docs](https://mandrillapp.com/api/docs/messages.html#method=send))
* `sendTemplate(Message $message)`: used by transport layer if a $message has a template ([docs](https://mandrillapp.com/api/docs/messages.html#method=send-template))

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

Rejection blacklist functions:

* `addRejectionBlacklist($email)`: add an email rejection blacklist ([docs](https://mandrillapp.com/api/docs/rejects.html#method=add))
* `deleteRejectionBlacklist($email)`: delete an email rejection blacklist ([docs](https://mandrillapp.com/api/docs/rejects.html#method=delete))
* `getRejectionBlacklist($email, $includeExpired = false)`: get all the email rejection blacklist ([docs](https://mandrillapp.com/api/docs/rejects.html#method=list))

Rejection whitelist functions:

* `addRejectionWhitelist($email)`: add an email rejection whitelist ([docs](https://mandrillapp.com/api/docs/whitelists.html#method=add))
* `deleteRejectionWhitelist($email)`: delete an email rejection whitelist ([docs](https://mandrillapp.com/api/docs/whitelists.html#method=delete))
* `getRejectionWhitelist($email)`: get all the email rejection whitelist ([docs](https://mandrillapp.com/api/docs/whitelists.html#method=list))

URLs functions:

* `getMostClickedUrls($q = '')`: get the 100 most clicked URLs optionally filtered by search query ([docs](https://mandrillapp.com/api/docs/urls.html#method=list))
* `getRecentUrlInfo($url)`: get the recent history (hourly stats for the last 30 days) for a url ([docs](https://mandrillapp.com/api/docs/urls.html#method=time-series))

Template functions:

* `addTemplate($name, Address $address = null, $subject = '', $html = '', $text = '')`: add a new template to Mandrill ([docs](https://mandrillapp.com/api/docs/templates.html#method=add))
* `updateTemplate($name, Address $address = null, $subject = '', $html = '', $text = '')`: update an existing template ([docs](https://mandrillapp.com/api/docs/templates.html#method=update))
* `deleteTemplate($template)`: delete an existing template ([docs](https://mandrillapp.com/api/docs/templates.html#method=delete))
* `getTemplates()`: get all registered templates on Mandrill ([docs](https://mandrillapp.com/api/docs/templates.html#method=list))
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
* `SlmMail\Service\Exception\RuntimeException`: this exception is thrown for other exceptions.
