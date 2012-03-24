<?php

namespace SlmMail\Service\Mailchimp;

use Zend\Mail\Message,
    Zend\Http\Request,
    Zend\Http\Response,
    Zend\Mail\Exception\RuntimeException,
    SlmMail\Service\Amazon,
    SlmMail\Mail\Message\Mailchimp as MailchimpMessage;

class Sts extends Amazon
{
    const API_URI = 'http://%s.sts.mailchimp.com/1.0/';
    
    protected $apiKey;
    protected $client;
    
    public function __construct ($api_key)
    {
        $this->apiKey = $api_key;
    }
    
    public function sendEmail (Message $message)
    {
        $params = array(
            'message' => array(
                'subject' => $message->getSubject(),
                'html'    => $message->getBody(),
                'text'    => $message->getBodyText(),
            )
        );
        
        foreach ($message->to() as $address) {
            $params['message']['to_email'][] = $address->getEmail();
            $params['message']['to_name'][]  = $address->getName();
        }
        foreach ($message->cc() as $address) {
            $params['message']['cc_email'][] = $address->getEmail();
            $params['message']['cc_name'][]  = $address->getName();
        }
        foreach ($message->bcc() as $address) {
            $params['message']['bcc_email'][] = $address->getEmail();
            $params['message']['bcc_name'][]  = $address->getName();
        }
        
        
        $from = $message->from();
        if (1 !== count($from)) {
            throw new RuntimeException('Mailchimp requires exactly one from address');
        }
        $from->rewind();
        $from = $from->current();
        $data['message']['from_email']      = $from->getEmail();
        $data['message']['from_name'] = $from->getName();
        
        foreach ($message->replyTo() as $address) {
            $params['message']['reply_to'][] = $address->getEmail();
        }
        
        if ($message instanceof MailchimpMessage) {
            if (null !== ($flag = $message->getTrackClicks())) {
                $params['track_clicks'] = $flag;
            }
            
            if (null !== ($flag = $message->getTrackOpens())) {
                $params['track_opens'] = $flag;
            }
            
            if (null !== ($tags = $message->getTags())) {
                $params['tags'] = $tags;
            }
        }
        
        $response = $this->prepareHttpClient('SendEmail', $params)
                         ->send();
        
        return $this->parseResponse($response);
    }
    
    public function getBounces ($since = null)
    {
        $params = array();
        if (null !== $since) {
            $params['since'] = $since;
        }
        
        $response = $this->prepareHttpClient('GetBounces', $params)
                         ->send();
        
        return $this->parseResponse($response);
    }
    
    public function getSendStats ($tag_id = null, $since = null)
    {
        $params = compact($tag_id, $since);
        $params = $this->filterNullParams($params);
        
        $response = $this->prepareHttpClient('GetSendStats', $params)
                         ->send();
        
        return $this->parseResponse($response);
    }
    
    public function getTags ()
    {
        $response = $this->prepareHttpClient('GetTags')
                         ->send();
        
        return $this->parseReponse($response);
    }
    
    public function getUrlStats ($url_id = null, $since = null)
    {
        $params = compact($url_id, $since);
        $params = $this->filterNullParams($params);
        
        $response = $this->prepareHttpClient('GetUrlStats', $params)
                         ->send();
        
        return $this->parseResponse($response);
    }
    
    public function getUrls ()
    {
        $response = $this->prepareHttpClient('GetUrls')
                         ->send();
        
        return $this->parseReponse($response);
    }

    protected function prepareHttpClient ($path, array $data = array())
    {
        $data = $data + array('apikey' => $this->apiKey);
        $host = sprintf(self::API_URI, substr($this->apiKey, strpos($this->apiKey, '-') + 1));

        return $this->getHttpClient()
                    ->setMethod(Request::METHOD_POST)
                    ->setUri($host . $path . '.php')
                    ->setParameterGet($data);
    }           
    
    protected function parseResponse (Response $response)
    {
        $body = unserialize($response->getBody());
        
        if (!$response->isOk()) {
            switch ($response->getStatusCode()) {
                case 500:
                    throw new RuntimeException(sprintf(
                            'Could not send request: Mailchimp server error (%s)',
                            $body['message']));
                    break;
                default:
                    throw new RuntimeException('Unknown error during request to Mailchimp server');
            }
        }
        
        return unserialize($response->getBody());
    }
}