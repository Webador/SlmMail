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

    /**
     * Sends message to Postage server
     * 
     * @link http://help.postageapp.com/kb/api/send_message
     * @param Message $message
     * @return string Id of the message
     */
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
        
        $response =  $this->prepareHttpClient('send_message', $data)
                          ->send();
        $messageId = $this->parseResponse($response)->message->id;
        
        return array(
            'uid' => $data['uid'],
            'id'  => $messageId
        );
    }

    /**
     * Get receipt of message by its uid
     * 
     * The Postage apps lets verify message if they are known in the project.
     * This is done with the uid. When the message is known, it returns its
     * message id (independant from the uid). If not, an exception is thrown 
     * because of an invalid message uid.
     * 
     * @link http://help.postageapp.com/kb/api/get_message_receipt
     * @param string $uid
     * @return string Id of the message
     */
    public function getMessageReceipt ($uid)
    {
        $response = $this->prepareHttpClient('get_message_receipt', array('uid' => $uid))
                         ->send();
        
        return $this->parseResponse($response)->message->id;
    }

    /**
     * Get a list of all api methods
     * 
     * @link http://help.postageapp.com/kb/api/get_method_list
     * @return array List of all methods
     */
    public function getMethodList ()
    {
        $response = $this->prepareHttpClient('get_method_list')
                         ->send();
        
        $methods = $this->parseResponse($response)->methods;
        return explode(', ', $methods);
    }

    /**
     * Get info of the account for this API key
     * 
     * @link http://help.postageapp.com/kb/api/get_account_info
     * @return stdClass Object with account info
     */
    public function getAccountInfo ()
    {
        $response = $this->prepareHttpClient('get_account_info')
                         ->send();
        
        return $this->parseResponse($response)->account;
    }

    /**
     * Get info of the project for this API key
     * 
     * @link http://help.postageapp.com/kb/api/get_project_info
     * @return stdClass Object with project info
     */
    public function getProjectInfo ()
    {
        $response = $this->prepareHttpClient('get_project_info')
                         ->send();
        
        return $this->parseResponse($response)->project;
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
        $body = Json::decode($response->getBody());

        if (!$response->isOk()) {
            if ('ok' !== $body->response->status) {
                throw new RuntimeException(sprintf(
                    'Could not send request: api error "%s" (%s)',
                    $body->response->status,
                    $body->response->message));
            } else {
                throw new RuntimeException('Unknown error during request to Postage server');
            }
        }
        
        return $body->data;
    }
}