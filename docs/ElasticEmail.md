ElasticEmail
===
This transport layer forms the coupling between Zend\Mail and the Email Service Provider [ElasticEmail](http://http://elasticemail.com/). The transport is a drop-in component and can be used to send email messages including Cc & Bcc addresses and attachments.

Installation
---
It is assumed this module is already installed and enabled in your Zend Framework 2 project. If not, please read first the [installation instructions](https://github.com/juriansluiman/SlmMail/blob/master/README.md) to do so.

Copy the `./vendors/SlmMail/config/module.slmmail.elasticemail.config.php.dist` to your `./config/autoload/module.slmmail.config.php` and update your api key and username in `./config/autoload/module.slmmail.config.php`.

Usage
---
You need access to the `Zend\Di\Di` instance to grab the ElasticEmail transport layer `SlmMail\Mail\Transport\ElasticEmail` from there or inject it automatically with your own DI configuration.

### Use locator
If you have `$locator` as your `Zend\Di\Di` instance, you can simply do:

    $message   = new Zend\Mail\Message;
    // set up Message here

    $transport = $locator->get('elasticemail-transport');
    $transport->send($message);

### Inject transport
If you have a class `Foo\Bar` which need the transport injected, create a DI configuration as follows:

    'di' => array(
        'instance' => array(
            'Foo\Bar' => array(
                'parameters' => array(
                    'transport' => 'elasticemail-transport'
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
The transport layer depends on a service class `SlmMail\Service\ElasticEmail` which sends the requests to the ElasticEmail server. However, this service implements also [the api](http://elasticemail.com/api-documentation) so you can immediately check the state of the sent email and act upon a bounced message.

The service class is injected into the `SlmMail\Mail\Transport\ElasticEmail` but you can get the service class yourself too:

    $elasticEmail = $locator->get('elasticemail-service');
    $bounce       = $elasticEmail->getLog(); // Example
    
The complete list of methods is still TBD.