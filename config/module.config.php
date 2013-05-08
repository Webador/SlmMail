<?php

return array(
    'service_manager' => array(
        'factories' => array(
            /**
             * Transport
             */
            'SlmMail\Mail\Transport\AlphaMailTransport'    => 'SlmMail\Factory\AlphaMailTransportFactory',
            'SlmMail\Mail\Transport\ElasticEmailTransport' => 'SlmMail\Factory\ElasticEmailTransportFactory',
            'SlmMail\Mail\Transport\MailgunTransport'      => 'SlmMail\Factory\MailgunTransportFactory',
            'SlmMail\Mail\Transport\MandrillTransport'     => 'SlmMail\Factory\MandrillTransportFactory',
            'SlmMail\Mail\Transport\PostageTransport'      => 'SlmMail\Factory\PostageTransportFactory',
            'SlmMail\Mail\Transport\PostmarkTransport'     => 'SlmMail\Factory\PostmarkTransportFactory',
            'SlmMail\Mail\Transport\SendGridTransport'     => 'SlmMail\Factory\SendGridTransportFactory',
            'SlmMail\Mail\Transport\SesTransport'          => 'SlmMail\Factory\SesTransportFactory',

            /**
             * Services
             */
            'SlmMail\Service\AlphaMailService'    => 'SlmMail\Factory\AlphaMailServiceFactory',
            'SlmMail\Service\ElasticEmailService' => 'SlmMail\Factory\ElasticEmailServiceFactory',
            'SlmMail\Service\MailgunService'      => 'SlmMail\Factory\MailgunServiceFactory',
            'SlmMail\Service\MandrillService'     => 'SlmMail\Factory\MandrillServiceFactory',
            'SlmMail\Service\PostageService'      => 'SlmMail\Factory\PostageServiceFactory',
            'SlmMail\Service\PostmarkService'     => 'SlmMail\Factory\PostmarkServiceFactory',
            'SlmMail\Service\SendGridService'     => 'SlmMail\Factory\SendGridServiceFactory',
            'SlmMail\Service\SesService'          => 'SlmMail\Factory\SesServiceFactory',

            /**
             * HTTP client
             */
            'SlmMail\Http\Client' => 'SlmMail\Factory\HttpClientFactory',
        ),
    ),
);
