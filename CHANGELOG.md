# CHANGELOG
## 2.1.2
- ElasticEmail API endpoint `/send` tell us to use POST method instead of GET. So, when sending big html messages via GET it returns 414 error. Fixed to use POST in the method `send` of the `ElasticEmailService.php`

## 2.1.1
- MailGun API endpoint `/logs` is deprecated. So, you should use the `getEvents` function instead of `getLog` from `MailGunService.php`.
- Small fix in the ElasticMail Api return's when authentication fails.
- Small PHP syntax's fixes 

## 2.1.0
- Added ZF3 support
-- which allow install zend-servicemanager both versions v2 or v3 by composer
- Updated & reduced composer dependencies (dropped install full ZF2 framework)
- Small PHP syntax's fixes & added gitignore file


## 2.0.0
- bump to aws-sdk-for-php ZF2 module to 2.*
-- which in turn bumps support to AWS SDK for PHP v3


## 1.6.0

- MailGun API now uses the `v3` endpoint. Normally, [this is BC-free](http://blog.mailgun.com/default-api-version-now-v3/), but if you are using
MailGun, make sure to try your application before!

## 1.5.3

- Add the new `merge_language` option to Mandrill service.

## 1.5.2

- Mandrill service now properly used the `sendAt` delayed date when sending a templated mail when calling the
`send` method.

## 1.5.1

- Fix small typo in README to include ~1.5
- Remove unnecessary import statements in code base
- Return API status codes in Postage/Postmark api exceptions

## 1.5.0

- You can now schedule Mandrill emails in the future by using the optional `sendAt` variable in both `send` and
`sendTemplate` methods.
- `getScheduledMessages`, `cancelScheduledMessage` and `rescheduleMessage` methods have been also added to the
Mandrill service to handle those messages.
- AlphaMail is dead and no longer operates. It is therefore removed from SlmMail.

## 1.4.1

- Fix Sendgrid attachment issues with Outlook clients

## 1.4.0

- Mailgun users can now use batch sending. You can now send the same email to multiple recipients in one API call.

## 1.3.2

- Simplify ZF dependency to allow any 2.x versions. This fix some subtle bugs that can happen with Composer

## 1.3.1

- Fix a bug when extracting errors for SendGrid ([#60](https://github.com/juriansluiman/SlmMail/pull/60))

## 1.3.0

- [BC] Removing the Version class (it's a hassle to maintain and has little use)
- Improve support for Cc and Bcc in Mandrill
- Allow support for per-recipient metadata in Mandrill messages through the "setMetadata" method.
- Using the "metadata" option for Mandrill message has been deprecated, please now use the "setGlobalMetadata" method.

## 1.2.0

- Add support for adding an email to a subaccount's rejection blacklist
- Add support for Mandrill template labels

## 1.1.1

- Allow non-template Mandrill messages to have merge variables

## 1.1.0

- Add support for Mandrill subaccounts API
- Allow up to one BCC address for Mandrill
- Add following options to Mandrill messages: return_path_domain, subaccount
- Updated Mandrill doc
- Add support for Mailgun routes
- Updated Mailgun doc

## 1.0.1

- Fix a problem when sending messages via SendGrid.
- Add the new "getMessageInfo" to Mandrilll service.
- Add a new "UnknownTemplateException" for services that support templates and report this error (currently, only Mandrill)

## 1.0.0

- Doc update

## 1.0.0 RC3

- Add exceptions support for Amazon SES.
- Now allow the new "metadata" option in Mandrill message
- Http adapter can now be configured more easily

## 1.0.0 RC2

- Licensing of `composer.json` and all files inside SlmMail
- Addition of a CHANGELOG document
- Bug fixes for Options properties in Mandrill
- Bug fixes in the AbstractService to return `null` when no text or html part has been found
- Addition of a CONTRIBUTING document
- Fix AlphaMail service by removal of the username and fixes in the message send function

## 1.0.0 RC1

- Complete rewrite of SlmMail: it is now completely up-to-date with Zend Framework 2
- Additional providers: Mandrill, Mailgun and AlphaMail
- All providers: completed and tested
