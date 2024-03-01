<?php

namespace Steel97\FlarumMailopost;

use GuzzleHttp\Client;
use Flarum\Mail\DriverInterface;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\MessageBag;
use Swift_Transport;

class MailopostDriver implements DriverInterface
{
    public function availableSettings(): array
    {
        return [
            'mailopost_api_base_url' => 'https://api.mailopost.ru/v1/email/',
            'mailopost_token' => '',
            'mailopost_payment' => [
                'subscriber_priority' => 'Subscriber priority',
                'credit_priority' => 'Credit priority',
                'subscriber' => 'Subscriber',
                'credit' => 'Credit',
            ],
        ];
    }

    public function validate(SettingsRepositoryInterface $settings, Factory $validator): MessageBag
    {
        return $validator->make($settings->all(), [
            'mailopost_api_base_url' => 'required',
            'mailopost_token' => 'required',
            'mailopost_payment' => 'required|in:subscriber_priority,credit_priority,subscriber,credit',
        ])->errors();
    }

    public function canSend(): bool
    {
        return true;
    }

    public function buildTransport(SettingsRepositoryInterface $settings): Swift_Transport
    {
        $client = new Client();
        return new MailopostTransport($client, $settings->get('mailopost_token'), $settings->get('mailopost_payment'), $settings->get('mailopost_api_base_url'));
    }
}