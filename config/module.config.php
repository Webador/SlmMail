<?php

return array(
    'service_manager' => array(
        'factories' => array(
            /**
             * Transport
             */
            'SlmMail\Mail\Transport\MandrillTransport' => 'SlmMail\Factory\MandrillTransportFactory',

            /**
             * Services
             */
            'SlmMail\Service\MandrillService'          => 'SlmMail\Factory\MandrillServiceFactory'
        )
    )
);
