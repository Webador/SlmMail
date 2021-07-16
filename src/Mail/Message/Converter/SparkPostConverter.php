<?php

namespace SlmMail\Mail\Message\Converter;

use SlmMail\Mail\Message\SparkPost;
use SlmMail\Mail\Message\Mandrill;

class SparkPostConverter
{
    public static function fromMandrill(Mandrill $mandrill): SparkPost
    {
        // TODO: Handle more complex cases where e.g. the Mandrill option 'subaccount' is represented by a header in SparkPost
        $sparkPost = new SparkPost(self::fromMandrillOptions($mandrill->getOptions()));

        $sparkPost->setHeaders($mandrill->getHeaders());
        $sparkPost->setTo($mandrill->getTo());
        $sparkPost->setFrom($mandrill->getFrom());
        if ($mandrill->getSender()) {
            $sparkPost->setSender($mandrill->getSender());
        }
        $sparkPost->setReplyTo($mandrill->getReplyTo());
        $sparkPost->setCc($mandrill->getCc());
        $sparkPost->setBcc($mandrill->getBcc());
        if ($mandrill->getSubject()) {
            $sparkPost->setSubject($mandrill->getSubject());
        }
        $sparkPost->setEncoding($mandrill->getEncoding());
        $sparkPost->setBody($mandrill->getBody());
        $sparkPost->setTemplateId($mandrill->getTemplate());
        $sparkPost->setAllVariables($mandrill->getVariables());
        $sparkPost->setGlobalVariables($mandrill->getGlobalVariables());

        return $sparkPost;
    }

    /**
     * Translate/copy options that map (roughly) 1:1 between Mandrill and SparkPost
     */
    public static function fromMandrillOptions($mandrillOptions): array
    {
        $optionsMap = [
            'important' => 'important',
            'track_clicks' => 'click_tracking',
            'track_open' => 'open_tracking',
        ];

        $sparkPostOptions = [];

        foreach($optionsMap as $hasOption) {
            if(array_key_exists($hasOption, $mandrillOptions)) {
                $sparkPostOptions[$hasOption] = $mandrillOptions[$hasOption];
            }
        }

        return $sparkPostOptions;
    }
}
