<?php

namespace SlmMail;

class ConfigProvider
{
    public function __invoke(): array
    {
        $config = (new Module())->getConfig();

        return [
            'dependencies'  => $config['service_manager'],
            'slm_mail' => $config['slm_mail'],
        ];
    }
}