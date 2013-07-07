# CHANGELOG

## 1.0.1

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
