<?php

namespace SlmMail\Service

use Zend\Mail\Message,
    Zend\Http\Client,
    Zend\Http\Request,
    Zend\Http\Response,
    Zend\Json\Json;

class Postmark
{
    const API_URI = 'http://api.postmarkapp.com/';
    const RECIPIENT_LIMIT = 20;
    
    protected $apiKey;
    protected $client;
    protected $filters = array(
        'HardBounce', 
        'Transient', 
        'Unsubscribe', 
        'Subscribe', 
        'AutoResponder', 
        'AddressChange', 
        'DnsError', 
        'SpamNotification', 
        'OpenRelayTest', 
        'Unknown', 
        'SoftBounce', 
        'VirusNotification', 
        'ChallengeVerification', 
        'BadEmailAddress', 
        'SpamComplaint', 
        'ManuallyDeactivated', 
        'Unconfirmed', 
        'Blocked'
    );
    
    public function setApiKey ($api_key)
    {
        $this->apiKey = $api_key;
        return $this;
    }
    
    public function sendEmail (Message $message)
    {
        $data = array(
            'Subject'  => $message->getSubject(),
            'HtmlBody' => $message->getBody(), // @todo get corrent html/plain versions
        );
        
        $to = array();
        foreach ($message->to() as $address) {
            $to[] = $address->toString();
        }
        $data['To'] = implode(',', $to);
        
        $cc = array();
        foreach ($message->cc() as $address) {
            $cc[] = $address->toString();
        }
        if (20 < count($cc)) {
            throw new RuntimeException('Limitation exceeded for CC recipients');
        } elseif (count($cc)) {
            $data['Cc'] = implode(',', $cc);
        }
        
        $bcc = array();
        foreach ($message->bcc() as $address) {
            $bcc[] = $address->toString();
        }
        if (20 < count($bcc)) {
            throw new RuntimeException('Limitation exceeded for BCC recipients');
        } elseif (count($bcc)) {
            $data['Bcc'] = implode(',', $bcc);
        }
        
        $replyTo = $message->replyTo();
        if (1 > count($replyTo)) {
            throw new RuntimeException('Postmark has only support for one reply-to address');
        } elseif (count($replyTo)) {
            $replyTo = current($replyTo);
            $data['ReplyTo'] = $replyTo->toString();
        }
        
        // @todo implement from and tags
        $data = array(
            'From'     => implode( ',', $from),
            'tag'      => implode(',', $tags)
        );
        
        // @todo already handling attachments?
        if ($hasAttachment) {
            $attachments = array();
            foreach ($message->getAttachmentCollection() as $attachment) {
                $attachments[] = array(
                    'ContentType' => $attachment->getContentType(),
                    'Name'        => $attachment->getName(),
                    'Content'     => $attachment->getContent(),
                );
            }
            $data['Attachments'] = $attachments;
        }

        $response = $this->getHttpClient('/email')
                         ->setRawBody(Json::encode($data))
                         ->send();
        
        return $this->parseResponse($response);
    }
    
    public function getDeliveryStats ()
    {
        $response = $this->getHttpClient('/deliverystats')
                         ->send();
        
        return $this->parseResponse($response);
    }
    
    public function getBounces ($type = null, $inactive = null, $emailFilter = null, $paging = null)
    {   
        if (null !== $type &&!in_array($type, $filters)) {
            throw new RuntimeException(sprintf(
                'Type %s is not a supported filter',
                $type
            ));
        }
        
        $params   = compact('type', 'inactive', 'emailFilter', 'paging');
        $response = $this->getHttpClient('/bounces');
                         ->setParameterGet($params)
                         ->send();
                         
        return $this->parseResponse($response);
    }
    
    public function getBounce ($id)
    {
        $response = $this->getHttpClient('/bounces/' . $id);
                         ->send();
                                 
        return $this->parseResponse($response);
    }
    
    public function getBounceDump ($id)
    {
        $response = $this->getHttpClient('/bounces/' . $id . '/dump');
                         ->send();
                                 
        return $this->parseResponse($response);
    }
    
    public function getBounceTags ()
    {
        $response = $this->getHttpClient('/bounces/tags');
                         ->send();
                                 
        return $this->parseResponse($response);
    }
    
    public function activateBounce ($id)
    {
        $response = $this->getHttpClient('/bounces/' . $id . '/activate')
                         ->setMethod(Request::METHOD_PUT);
                         ->send();
                                 
        $response = $this->parseResponse($response);
        return $response['Body'];
    }
    
    protected function getHttpClient ($path)
    {
        if (null === $this->client) {
            $headers = array(
                'Accept'                  => 'application/json',
                'X-Postmark-Server-Token' => $this->apiKey
            )
            
            $this->client = new Client();
            $this->client->setUri(self::API_URI)
                         ->setMethod(Request::METHOD_GET)
                         ->setHeaders($headers);
        }
        
        $this->client->getUri()->setPath($path);
        return $this->client;
    }
    
    protected function parseResponse (Response $response)
    {
        if (!$response->isOk()) {
            switch ($response->getStatusCode()) {
                401:
                    throw new RuntimeException('Could not send request: authentication error');
                    break;
                422:
                    $error = Json::decode($response->getBody());
                    throw new RuntimeException(sprintf(
                        'Could not send request: api error code %s (%s)', 
                        $error['ErrorCode'], $error['Message']));
                    break;
                500:
                    throw new RuntimeException('Could not send request: Postmark server error');
                    break;
                default:
                    throw new RuntimeException('Unknown error during request to Postmark server');
            }
        }
        
        return Json::decode($response->getBody());
    }
}