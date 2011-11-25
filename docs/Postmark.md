Postmark
===
This transport layer forms the coupling between Zend\Mail and the Email Service Provider [Postmark](http://postmarkapp.com). The transport is a drop-in component and can be used to send email messages including Cc & Bcc addresses and attachments.

Installation
---
It is assumed this module is already installed and enabled in your Zend Framework 2 project. If not, please read first the [installation instructions](https://github.com/juriansluiman/SlmMail/blob/master/README.md) to do so.

Copy the `./vendors/SlmMail/config/module.slmmail.postmark.config.php.dist` to your `./config/autoload/module.postmark.config.php` and update your api key in `./config/autoload/module.postmark.config.php`.

Usage
---
You need access to the `Zend\Di\Di` instance to grab the Postmark transport layer `SlmMail\Mail\Transport\Postmark` from there or inject it automatically with your own DI configuration.

### Use locator
If you have `$locator` as your `Zend\Di\Di` instance, you can simply do:

    $message   = new Zend\Mail\Message;
    // set up Message here

    $transport = $locator->get('postmark-transport');
    $transport->send($message);

### Inject transport
If you have a class `Foo\Bar` which need the transport injected, create a DI configuration as follows:

    'di' => array(
        'instance' => array(
            'Foo\Bar' => array(
                'parameters' => array(
                    'transport' => 'postmark-transport'
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
The transport layer depends on a service class `SlmMail\Service\Postmark` which sends the requests to the Postmark server. However, this service implements also the [bounces retrieval api](http://developer.postmarkapp.com/developer-bounces.html) so you can immediately check the state of the sent email and act upon a bounced message.

The service class is injected into the `SlmMail\Mail\Transport\Postmark` but you can get the service class yourself too:

    $postmark = $locator->get('postmark-service');
    $bounce   = $postmark->getBounce($id); // Example
    
The complete list of methods is:

1. `sendEmail($message)`: used by transport layer, $message instance of `Zend\Mail\Message` ([docs](http://developer.postmarkapp.com/developer-build.html))
2. `getDeliveryStats()`: return summary of inactive emails and bounces by type ([docs](http://developer.postmarkapp.com/developer-bounces.html#get-delivery-stats))
3. `getBounces($type, $inactive, $emailFilter, $paging)`: fetches a portion of bounces according to the specified input criteria, all arguments are optional and can be set to `null` ([docs](http://developer.postmarkapp.com/developer-bounces.html#get-bounces))
4. `getBounce($id)`: get details about a single bounce, $id is the bounce id ([docs](http://developer.postmarkapp.com/developer-bounces.html#get-a-single-bounce))
5. `getBounceDump($id)`: return the raw source of the bounce, $id is the bounce id ([docs](http://developer.postmarkapp.com/developer-bounces.html#get-bounce-dump))
6. `getBounceTags()`: return a list of tags ([docs](http://developer.postmarkapp.com/developer-bounces.html#get-bounce-tags))
7. `activateBounce($id)`:  activates a deactivated bounce, $id is the bounce id ([docs](http://developer.postmarkapp.com/developer-bounces.html#activate-a-bounce))