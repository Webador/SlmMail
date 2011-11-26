SendGrid
===
This transport layer forms the coupling between Zend\Mail and the Email Service Provider [SendGrid](http://sendgrid.com). The transport is a drop-in component and can be used to send email messages including Cc & Bcc addresses and attachments.

Installation
---
It is assumed this module is already installed and enabled in your Zend Framework 2 project. If not, please read first the [installation instructions](https://github.com/juriansluiman/SlmMail/blob/master/README.md) to do so.

Copy the `./vendors/SlmMail/config/module.slmmail.sendgrid.config.php.dist` to your `./config/autoload/module.sendgrid.config.php` and update your api key in `./config/autoload/module.sendgrid.config.php`.

Usage
---
You need access to the `Zend\Di\Di` instance to grab the SendGrid transport layer `SlmMail\Mail\Transport\SendGrid` from there or inject it automatically with your own DI configuration.

### Use locator
If you have `$locator` as your `Zend\Di\Di` instance, you can simply do:

    $message   = new Zend\Mail\Message;
    // set up Message here

    $transport = $locator->get('sendgrid-transport');
    $transport->send($message);

### Inject transport
If you have a class `Foo\Bar` which need the transport injected, create a DI configuration as follows:

    'di' => array(
        'instance' => array(
            'Foo\Bar' => array(
                'parameters' => array(
                    'transport' => 'sendgrid-transport'
                ),
            ),
        ),
    ),

Then you can create your class as follows:

    namespace Foo;
    
    use Zend\Mail\Transport,
        Zend\Mail\Message;
    
    class Bar
    {
        protected $transport;
        
        public function setTransport (Transport $transport)
        {
            $this->transport = $transport;
        }
        
        public function doSomething ()
        {
            $message   = new Message;
            // set up Message here

            $this->transport->send($message);
        }
    }

### Advanced usage
The transport layer depends on a service class `SlmMail\Service\SendGrid` which sends the requests to the SendGrid server. However, this service implements also [the web-api](http://docs.sendgrid.com/documentation/api/web-api/) so you can use all features SendGrid exposes through their api.

The service class is injected into the `SlmMail\Mail\Transport\SendGrid` but you can get the service class yourself too:

    $sendgrid = $locator->get('sendgrid-service');
    $bounce   = $sendgrid->getStats(); // Example
    
The complete list of methods needs is:

1. sendMail()
2. getBlocks()
3. deleteBlock()
4. getBounces()
5. deleteBounces()
6. countBounces()
7. getParseSettings()
8. addParseSetting()
9. editParseSetting()
10. deleteParseSetting()
11. getEventPostUrl()
12. setEventPostUrl()
13. deleteEventPostUrl()
14. getFilters()
15. activateFilters()
16. deactivateFilters()
17. setupFilters()
18. getFilterSettings()
19. getInvalidEmails()
20. deleteInvalidEmails()
21. countInvalidEmails()
22. getProfile()
23. updateProfile()
24. setUsername()
25. setPassword
26. setEmail()
27. getSpamReports()
28. deleteSpamReports()
29. countSpamReports()
30. getStats()
31. getStatsAggregate()
32. getCategoryList()
33. getCategoryStats()
34. getCategoryAggregate()
35. getUnsubscribes()
36. addUnsubscribes()
37. deleteUnsubscribes()
38. countUnsubscribes()