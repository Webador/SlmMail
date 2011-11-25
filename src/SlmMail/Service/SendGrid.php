<?php

namespace SlmMail\Service;

use Zend\Mail\Message,
    Zend\Http\Client,
    Zend\Http\Request,
    Zend\Http\Response,
    Zend\Json\Json,
    Zend\Mail\Exception\RuntimeException;

class SendGrid
{
    const API_URI = 'https://sendgrid.com/';
    
    protected $apiKey;
    protected $username;
    protected $client;
    
    public function setApiKey ($api_key)
    {
        $this->apiKey = $api_key;
    }
    
    public function setUsername ($username)
    {
        $this->username = $username;
    }
    
    /** Mail */
    public function sendMail (Message $message) {}
    
    /** Bounces */
    public function getBounce () {}
    public function deleteBounce () {}
    
    /** Blocks */
    public function getBlocks () {}
    public function deleteBlocks () {}
    
    /** Email parse settings */
    public function getParseSettings () {}
    public function setParseSettings () {}
    public function editParseSettings () {}
    public function deleteParseSettings () {}
    
    /** Events */
    public function getEvents () {}
    public function setEvents () {}
    public function deleteEvents () {}
    
    /** Filters */
    public function getFilters () {}
    public function activateFilters () {}
    public function deactivateFilters () {}
    public function setupFilters () {}
    public function getFilterSettings () {}
    
    /** Invalid emails */
    public function getInvalidEmails () {}
    public function deleteInvalidEmails () {}
    
    /** Profile */
    public function getProfile () {}
    public function setProfile () {}
    public function setUsername () {}
    public function setPassword () {}
    public function setEmail () {}
    
    /** Spam reports */
    public function getSpamReports () {}
    public function deleteSpamReports () {}
    
    /** Stats */
    public function getStats () {}
    
    /** Unsubscribes */
    public function addUnsubscribes () {}
    public function getUnsubscribes () {}
    public function deleteUnsubscribes () {}
    
    protected function getHttpClient ($path, $format = 'json')
    {
        if (null === $this->client) {
            $this->client = new Client();
            $this->client->setUri(self::API_URI)
                         ->setMethod(Request::METHOD_GET);
        }
        
        $this->client->getUri()->setPath('api/' . $path . '.' . $format);
        return $this->client;
    }
    
    protected function parseJsonResponse (Response $response)
    {
        // @todo look for errors
        
        return Json::decode($response->getBody());
    }
}