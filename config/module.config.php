<?php

return array(
    'service_manager' => array(
        'factories' => array(
            /**
             * Transport
             */
            'SlmMail\Mail\Transport\MandrillTransport' => 'SlmMail\Factory\MandrillTransportFactory',
            'SlmMail\Mail\Transport\PostageTransport'  => 'SlmMail\Factory\PostageTransportFactory',
            'SlmMail\Mail\Transport\PostmarkTransport' => 'SlmMail\Factory\PostmarkTransportFactory',
            'SlmMail\Mail\Transport\SesTransport'      => 'SlmMail\Factory\SesTransportFactory',

            /**
             * Services
             */
            'SlmMail\Service\MandrillService' => 'SlmMail\Factory\MandrillServiceFactory',
            'SlmMail\Service\PostageService'  => 'SlmMail\Factory\PostageServiceFactory',
            'SlmMail\Service\PostmarkService' => 'SlmMail\Factory\PostmarkServiceFactory',
            'SlmMail\Service\SesService'      => 'SlmMail\Factory\SesServiceFactory'
        )
    )
);
