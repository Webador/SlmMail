Postage
===
This transport layer forms the coupling between Zend\Mail and the Email Service Provider [Postage](http://postageapp.com). The transport is a drop-in component and can be used to send email messages including attachments but *without* Cc & Bcc addresses.

Installation
---
It is assumed this module is already installed and enabled in your Zend Framework 2 project. If not, please read first the [installation instructions](https://github.com/juriansluiman/SlmMail/blob/master/README.md) to do so.

Copy the `./vendors/SlmMail/config/module.slmmail.postage.config.php.dist` to your `./config/autoload/module.postage.config.php` and update your api key in `./config/autoload/module.postage.config.php`.

Usage
---
You need access to the `Zend\Di\Di` instance to grab the Postage transport layer `SlmMail\Mail\Transport\Postage` from there or inject it automatically with your own DI configuration.

### Use locator
If you have `$locator` as your `Zend\Di\Di` instance, you can simply do:

    $message   = new Zend\Mail\Message;
    // set up Message here

    $transport = $locator->get('postage-transport');
    $transport->send($message);

### Inject transport
If you have a class `Foo\Bar` which need the transport injected, create a DI configuration as follows:

    'di' => array(
        'instance' => array(
            'Foo\Bar' => array(
                'parameters' => array(
                    'transport' => 'postage-transport'
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
The transport layer depends on a service class `SlmMail\Service\Postage` which sends the requests to the Postage server. However, this service implements also [the api](http://help.postageapp.com/kb/api/api-overview) so you can immediately check the state of the sent email and act upon a bounced message.

The service class is injected into the `SlmMail\Mail\Transport\Postage` but you can get the service class yourself too:

    $postmark = $locator->get('postage-service');
    $bounce   = $postmark->getMessageReceipt($uid); // Example
    
The complete list of methods is:

1. `sendMessage($message)`: used by transport layer, $message instance of `Zend\Mail\Message` ([docs](http://help.postageapp.com/kb/api/send_message))
2. `getMessageReceipt($uid)`: get receipt of message, $uid the returned uid from `sendMessage()` ([docs](http://help.postageapp.com/kb/api/get_message_receipt))
3. `getMethodList()`: get list of available methods ([docs](http://help.postageapp.com/kb/api/get_method_list))
4. `getAccountInfo()`: get information about the account ([docs](http://help.postageapp.com/kb/api/get_account_info))
5. `getProjectInfo()`: get information about the project ([docs](http://help.postageapp.com/kb/api/get_project_info))