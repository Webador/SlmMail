<?php

namespace SlmMail\Mail\Transport;

use Zend\Mail\Transport,
    Zend\Mail\Message,
    Zend\Http\Client,
    Zend\Http\Request,
    Zend\Mail\Transport\Exception\RuntimeException;

class ElasticEmail implements Transport
{
    const API_URI = 'https://api.elasticemail.com/mailer/send';
    
    protected $apiKey;
    protected $client;
    
    public function setApiKey ($api_key)
    {
        $this->apiKey = $api_key;
        return $this;
    }
    
    public function send (Message $message)
    {
        // prepare data
        $data = array(
            'username'  => $this->username, // your account email address
            'api_key'   => $this->apiKey,
            'from'      => '',
            'from_name' => '',
            'to'        => '',      // semi colon separated list of email recipients
            'subject'   => $message->getSubject(),
            'body_html' => '',      // optional
            'body_text' => ''       // optional
            'reply_to'  => ''       // optional
            'reply_to_name' => ''   // optional
            'channel'   => ''       // optional, default to from address
        );
        
        $response = $this->getHttpClient()
                         ->setParameterPost($data)
                         ->send();
        
        if (!$respons->isOk()) {
            throw new RuntimeException('Postmark error: ');
        }
        
        // handle response and return transaction ID
    }
    
    protected function getHttpClient ()
    {
        if (null === $this->client) {
            $this->client = new Client();
            $this->client->setUri(self::API_URI)
                         ->setMethod(Request::METHOD_POST);
        }
        
        return $this->client;
    }
}