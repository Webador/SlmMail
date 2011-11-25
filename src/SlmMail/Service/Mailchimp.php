<?php

namespace SlmMail\Service;

use Zend\Mail\Message,
    Zend\Http\Client,
    Zend\Http\Request,
    Zend\Http\Response,
    Zend\Json\Json,
    Zend\Mail\Exception\RuntimeException;

class Mailchimp
{
    const API_URI = 'http://%s.mailchimp.com/1.3/';
    
    protected $apiKey;
    protected $client;
    
    public function setApiKey ($api_key)
    {
        $this->apiKey = $api_key;
    }
    
    /** Campaign */
    // A lot here to be done
    public function getCampainsForEmail () {}
    
    /** eCommerce */
    public function getEcommerceOrders () {}
    public function deleteEcommerceOrder () {}
    public function addEcommerceOrder () {}
    
    /** Folder */
    public function getFolders () {}
    public function addFolder () {}
    public function updateFolder () {}
    public function deleteFolder () {}
    
    /** Golden monkeys */
    public function getGoldenMonkeys () {}
    public function addGoldenMonkeys () {}
    public function deleteGoldenMonkeys () {}
    public function getGoldenMonkeysActivity () {}
    
    /** Lists */
    // A lot here to be done
    public function getListsForEmail () {}
    
    /** Security */
    public function getApiKeys () {}
    public function addApiKey () {}
    public function expireApiKey () {}
    
    /** Templates */
    public function getTemplates () {}
    public function getTemplate () {}
    public function addTemplate () {}
    public function updateTemplate () {}
    public function deleteTemplate () {}
    public function undeleteTemplate () {}
    
    /** Miscellaneous */
    public function generateText () {}
    public function getAccountDetails () {}
    public function inlineCss () {}
    public function ping () {}
    public function chimpChatter () {}
    
    
    protected function getHttpClient ()
    {
        if (null === $this->client) {
            $code = substr($this->apiKey, strpos('-')+1);
            $uri  = sprintf(self::API_URI, $code);
            
            $this->client = new Client();
            $this->client->setUri($uri)
                         ->setMethod(Request::METHOD_GET);
        }
        
        return $this->client;
    }
    
    protected function parseResponse (Response $response)
    {
        // @todo look for errors
        
        return Json::decode($response->getBody());
    }
}