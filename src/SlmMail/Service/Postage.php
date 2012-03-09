<?php

namespace SlmMail\Service;

use StdClass,
    DateTime,
    Zend\Mail\Message,
    Zend\Http\Client,
    Zend\Http\Request,
    Zend\Http\Response,
    Zend\Json\Json,
    Zend\Mail\Exception\RuntimeException;

class Postage
{
    const API_URI = 'https://api.postageapp.com/';
    const API_VERSION = 'v.1.0';

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
        $data = array('api_key' => $this->apiKey);
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
            $from->rewind();
            $args['headers']['reply-to'] = $from->current()->toString();
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
        
        $data['arguments'] = $args;
        $data['uid']       = sha1(Json::encode($args + array(new DateTime)));
        $response = $this->getHttpClient('send_message')
                         ->setRawBody(Json::encode($data))
                         ->send();
        
        return $this->parseResponse($response);
    }

    public function getMessageReceipt ($uid)
    {
        $data = array('api_key' => $this->apiKey, 'uid' => $uid);
        
        $response = $this->getHttpClient('get_message_receipt')
                         ->setRawBody(Json::encode($data))
                         ->send();
        
        return $this->parseResponse($response);
    }

    public function getMethodList ()
    {
        $data = array('api_key' => $this->apiKey);
        
        $response = $this->getHttpClient('get_method_list')
                         ->setRawBody(Json::encode($data))
                         ->send();
        
        return $this->parseResponse($response);
    }

    public function getAccountInfo ()
    {
        $data = array('api_key' => $this->apiKey);
        
        $response = $this->getHttpClient('get_account_info')
                         ->setRawBody(Json::encode($data))
                         ->send();
        
        return $this->parseResponse($response);
    }

    public function getProjectInfo ()
    {
        $data = array('api_key' => $this->apiKey);
        
        $response = $this->getHttpClient('get_project_info')
                         ->setRawBody(Json::encode($data))
                         ->send();
        
        return $this->parseResponse($response);
    }

    protected function getHttpClient ($path)
    {
        if (null === $this->client) {
            $this->client = new Client;
            $this->client->setUri(self::API_URI)
                         ->setMethod(Request::METHOD_GET);
        }

        $this->client->getUri()->setPath(self::API_VERSION . '/' . $path . '.json');
        return $this->client;
    }

    protected function parseResponse (Response $response)
    {
        // @todo look for errors
        if (!$response->isOk()) {
            throw new RuntimeException('Unknown error during request to Postage server');
        }
        
        return Json::decode($response->getBody());
    }
}