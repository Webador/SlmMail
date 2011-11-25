<?php

namespace SlmMail\Service;

use Zend\Mail\Message,
    Zend\Http\Client,
    Zend\Http\Request,
    Zend\Http\Response,
    Zend\Mail\Exception\RuntimeException,
    SlmMail\Mail\Message\ElasticEmail as ElasticEmailMessage;

class ElasticEmail
{
    const API_URI = 'https://api.elasticemail.com/';

    protected $apiKey;
    protected $username;
    protected $client;
    protected $statuses = array(0, 1, 2, 4, 5, 6, 7, 8, 9);

    public function setApiKey ($api_key)
    {
        $this->apiKey = $api_key;
    }

    public function setUsername ($username)
    {
        $this->username = $username;
    }

    public function send (Message $message)
    {
        $data = array(
            'username'  => $this->username,
            'api_key'   => $this->apiKey,
            'subject'   => $message->getSubject(),
            'body_html' => $message->getBody(),
            'body_text' => $message->getBodyText(),
        );
        
        $to = array();
        foreach ($message->to() as $address) {
            $to[] = $address->toString();
        }
        foreach ($message->cc() as $address) {
            $to[] = $address->toString();
        }
        foreach ($message->bcc() as $address) {
            $to[] = $address->toString();
        }
        $data['to'] = implode(';', $to);        
        
        $from = $message->from();
        if (1 > count($from)) {
            throw new RuntimeException('Elastic Email has only support for one from address');
        } elseif (count($from)) {
            $from = current($from);
            $data['from']      = $from->getEmail();
            $data['from_name'] = $from->getName();
        }
        
        $replyTo = $message->replyTo();
        if (1 > count($replyTo)) {
            throw new RuntimeException('Elastic Email has only support for one reply-to address');
        } elseif (count($replyTo)) {
            $replyTo = current($replyTo);
            $data['reply_to']      = $replyTo->getEmail();
            $data['reply_to_name'] = $replyTo->getName();
        }
        
        if ($message instanceof ElasticEmailMessage 
            && null !== ($channel = $message->Channel())
        ) {
            $data['channel'] = $channel;
        }
        
        /**
         * @todo Handling attachments for emails
         * 
         * Example code how that possibly might work:
         * 
         * <code>
         * if ($hasAttachment) {
         *      $attachments = array();
         *      foreach ($message->getAttachmentCollection() as $attachment) {
         *          $attachments[] = $this->uploadAttachment($attachment->getName(), $attachment->getContent());
         *      }
         *      $data['attachments'] = implode(';', $attachments);
         *  }
         * </code>
         */
        
        $response = $this->getHttpClient('/mailer/send')
                         ->setMethod(Request::METHOD_POST)
                         ->setParameterPost($data)
                         ->send();
        return $this->parseResponse($response);
    }

    public function uploadAttachment ($name, $content)
    {
        /** @todo Implement uploading */
    }
    
    public function getStatus ($id, $detail)
    {
        $response = $this->getHttpClient('/mailer/status/' . $id)
                         ->setMethod(Request::METHOD_GET)
                         ->send();
        return $this->parseResponse($response);
    }

    public function getLog ($format = null, $compress = null, $status = null, $channel = null, $from = null, $to = null)
    {
        if (null !== $status &&!in_array($status, $this->statuses)) {
            throw new RuntimeException(sprintf(
                'Status %s is not a supported status',
                $status
            ));
        }
        
        $params   = compact($format, $compress, $status, $channel, $from, $to)
                  + array('username' => $this->username, 'api_key' => $this->apiKey);
        
        $response = $this->getHttpClient('/mailer/status/log')
                         ->setMethod(Request::METHOD_GET)
                         ->setParameterGet($params)
                         ->send();
        return $this->parseResponse($response);
    }

    public function getAccountDetails ()
    {
        $params = array('username'  => $this->username, 'api_key'   => $this->apiKey);
        
        $response = $this->getHttpClient('/mailer/account-details')
                         ->setMethod(Request::METHOD_GET)
                         ->setParameterGet($params)
                         ->send();
        return $this->parseResponse($response);
    }

    public function getBounced ($detailed = false)
    {
        $params = array('username' => $this->username, 'api_key' => $this->apiKey);
        if ($detailed) {
            $params['detailed'] = true;
        }
        
        $response = $this->getHttpClient('/mailer/list/bounced')
                         ->setMethod(Request::METHOD_GET)
                         ->setParameterGet($params)
                         ->send();
        return $this->parseResponse($response);
    }

    public function getUnsubscribed ($detailed = false)
    {
        $params = array('username' => $this->username, 'api_key' => $this->apiKey);
        if ($detailed) {
            $params['detailed'] = true;
        }
        
        $response = $this->getHttpClient('/mailer/list/unsubscribed')
                         ->setMethod(Request::METHOD_GET)
                         ->setParameterGet($params)
                         ->send();
        return $this->parseResponse($response);
    }

    protected function getHttpClient ($path)
    {
        if (null === $this->client) {
            $this->client = new Client();
            $this->client->setUri(self::API_URI);
        }

        $this->client->getUri()->setPath($path);
        return $this->client;
    }
    
    protected function parseResponse (Response $response)
    {
        if (!$response->isOk()) {
            switch ($response->getStatusCode()) {
                default:
                    throw new RuntimeException('Unknown error during request to Postmark server');
            }
        }
        
        return $response->getBody();
    }
}