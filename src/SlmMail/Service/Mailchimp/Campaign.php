<?php

namespace SlmMail\Service\Mailchimp;

use Slm\Service\Mailchimp;

class Campaign extends Mailchimp
{
    const API_URI = 'http://%s.mailchimp.com/1.3/';
    
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
}