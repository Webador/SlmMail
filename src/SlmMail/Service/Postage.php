<?php

namespace SlmMail\Service;

use StdClass,
    DateTime,
    Zend\Mail\Message,
    Zend\Http\Client,
    Zend\Http\Request,
    Zend\Http\Response,
    Zend\Json\Json,
    Zend\Mail\Exception\RuntimeException,
    SlmMail\Mail\Message\Postage as PostageMessage;

class Postage
{
    const API_URI = 'https://api.postageapp.com/v.1.0/';

    protected $apiKey;
    protected $client;

    /**
     * Constructor
     * 
     * @param string $api_key 
     */
    public function __construct ($api_key)
    {
        $this->apiKey = $api_key;
    }

    public function sendMessage (Message $message)
    {
        $args = array();
        
        $to = array();
        foreach ($message->to() as $address) {
            $to[] = $address->toString();
        }
        $args['recipients'] = array(implode(',', $to));
        
        if (count($message->cc())) {
            throw new RuntimeException('Postage does not support CC addresses');
        }
        if (count($message->bcc())) {
            throw new RuntimeException('Postage does not support BCC addresses');
        }
        
        $args['headers'] = array('subject' => $message->getSubject());
        $args['content'] = array(
            'text/plain' => $message->getBodyText(),
            'text/html'  => $message->getBody(),
        );
        
        $from = $message->from();
        if (1 !== count($from)) {
            throw new RuntimeException('Postage requires exactly one from address');
        }
        $from->rewind();
        $args['headers']['from'] = $from->current()->toString();
        
        $replyTo = $message->replyTo();
        if (1 < count($replyTo)) {
            throw new RuntimeException('Postage has only support for one reply-to address');
        } elseif (count($replyTo)) {
            $replyTo->rewind();
            $args['headers']['reply-to'] = $replyTo->current()->toString();
        }        
        
        /**
         * @todo Handling attachments for emails
         * 
         * Example code how that possibly might work:
         * 
         * <code>
         * if ($hasAttachment) {
         *      $attachments = new StdClass;
         *      foreach ($message->getAttachmentCollection() as $attachment) {
         *          $obj               = new StdClass;
         *          $obj->content_type = $attachment->getContentType();
         *          $obj->content      = base64_encode($attachment->getContent());
         * 
         *          $name               = $attachment->getName();
         *          $attachments->$name = $obj;  
         *      }
         *      $args['attachments'] = $attachments;
         *  }
         * </code>
         */
         
        if ($message instanceof PostageMessage) {
            if (null !== ($template = $message->getTemplate())) {
                $args['template'] = $template;
            }
            
            if (null !== ($variables = $message->getVariables())) {
                $args['variables'] = $variables;
            }
        }
        
        $data = array(
            'arguments' => $args,
            'uid'       => sha1(Json::encode($args + array(new DateTime)))
        );
        
        $response = $this->prepareHttpClient('send_message', $data)
                         ->send();
        
        return $this->parseResponse($response);
    }

    public function getMessageReceipt ($uid)
    {
        $response = $this->prepareHttpClient('get_message_receipt', array('uid' => $uid))
                         ->send();
        
        return $this->parseResponse($response);
    }

    public function getMethodList ()
    {
        $response = $this->prepareHttpClient('get_method_list')
                         ->send();
        
        return $this->parseResponse($response);
    }

    public function getAccountInfo ()
    {
        $response = $this->prepareHttpClient('get_account_info')
                         ->send();
        
        return $this->parseResponse($response);
    }

    public function getProjectInfo ()
    {
        $response = $this->prepareHttpClient('get_project_info')
                         ->send();
        
        return $this->parseResponse($response);
    }
    
    public function getHttpClient ()
    {
        if (null === $this->client) {
            $this->client = new Client;
            
            $this->client->setMethod(Request::METHOD_POST);
            
            $this->client->getRequest()
                         ->headers()
                         ->addHeaderLine('Content-Type', 'application/json');
        }
        
        return $this->client;
    }
    
    public function setHttpClient (Client $client)
    {
        $this->client = $client;
    }

    protected function prepareHttpClient ($path, array $data = array())
    {
        $data = Json::encode($data + array('api_key' => $this->apiKey));

        return $this->getHttpClient()
                    ->setUri(self::API_URI . $path . '.json')
                    ->setRawBody($data);
    }

    protected function parseResponse (Response $response)
    {
        /**
         * @todo Add a more fine-grained error response check
         */
        if (!$response->isOk()) {
            throw new RuntimeException('Unknown error during request to Postage server');
        }
        
        return Json::decode($response->getBody());
    }
}