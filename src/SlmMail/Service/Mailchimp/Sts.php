<?php

namespace SlmMail\Service\Mailchimp;

use Slm\Service\Mailchimp;

class Sls extends Mailchimp
{
    const API_URI = 'http://%s.sts.mailchimp.com/1.0/';
    
    public function sendEmail (Message $message) {}
    public function verifyEmailAddress () {}
    public function listVerifiedEmailAddresses () {}
    public function deleteVerifiedEmailAddresses () {}
    public function getSendQuota () {}
    public function getSendStatistics () {}
    public function getBounces () {}
    public function getSendState () {}
    public function getTags () {}
    public function getUrlStats () {}
    public function getUrls () {}
}