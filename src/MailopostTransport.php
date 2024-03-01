<?php

namespace Steel97\FlarumMailopost;

use Flarum\Mail\DriverInterface;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\MessageBag;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Swift_TransportException;
use Swift_Events_EventListener;
use Swift_Events_SendEvent;
use Swift_Mime_SimpleMessage;
use Swift_Transport;

class MailopostTransport implements Swift_Transport
{
    public $plugins = [];
    private Client $client;
    private string $token;
    private string $payment;
    private string $endpoint;

    public function __construct(Client $client, string $token, string $payment, ?string $endpoint = null)
    {
        $this->client = $client;
        $this->token = $token;
        $this->payment = $payment;
        $this->endpoint = $endpoint ?? 'https://api.mailopost.ru/v1/email/';
    }

    /**
     * {@inheritdoc}
     *
     * @return int
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        $to = $this->getTo($message);

        $bcc = $message->getBcc();

        $message->setBcc([]);

        try {
            $response = $this->client->request(
                'POST',
                $this->endpoint.'messages',
                [
                    'headers' => [
                        'Authorization' => 'Bearer '.$this->token,
                        'Content-Type' => 'application/json'
                    ],
                    'body' => json_encode($this->payload($message, $to))
                ]
            );
        } catch (GuzzleException $e) {
            throw new Swift_TransportException('Request to Mailopost API failed.', $e->getCode(), $e);
        }

        $messageId = $this->getMessageId($response);

        $message->getHeaders()->addTextHeader('X-Message-ID', $messageId);
        $message->getHeaders()->addTextHeader('X-Mailopost-Message-ID', $messageId);

        $message->setBcc($bcc);

        $this->sendPerformed($message);

        return $this->numberOfRecipients($message);
    }

    protected function getReversePath(Swift_Mime_SimpleMessage $message)
    {
        $return = $message->getReturnPath();
        $sender = $message->getSender();
        $from = $message->getFrom();
        $path = null;
        if (!empty($return)) {
            $path = $return;
        } elseif (!empty($sender)) {
            // Don't use array_keys
            reset($sender); // Reset Pointer to first pos
            $path = key($sender); // Get key
        } elseif (!empty($from)) {
            reset($from); // Reset Pointer to first pos
            $path = key($from); // Get key
        }

        return $path;
    }

    private function payload(Swift_Mime_SimpleMessage $message, $to)
    {
        return [
            'from_email' => $this->getReversePath($message),
            'from_name' => $message->getSender() ?? 'noreply',
            'to' => $to,
            'subject' => $message->getSubject(),
            'text' => $message->getBody(),
            'payment' => $this->payment
        ];
    }

    private function getTo(Swift_Mime_SimpleMessage $message)
    {
        return collect($this->allContacts($message))->map(function ($display, $address) {
            //return $display ? $display." <{$address}>" : $address;
            return $address;
        })->values()->implode(',');
    }

    private function allContacts(Swift_Mime_SimpleMessage $message)
    {
        return array_merge(
            (array) $message->getTo(), (array) $message->getCc(), (array) $message->getBcc()
        );
    }

    private function getMessageId($response)
    {
        return object_get(
            json_decode($response->getBody()->getContents()), 'id'
        );
    }


    /// asdfasdfasdf
    public function isStarted()
    {
        return true;
    }

    public function start()
    {
        return true;
    }

    public function stop()
    {
        return true;
    }

    public function ping()
    {
        return true;
    }

    public function registerPlugin(Swift_Events_EventListener $plugin)
    {
        array_push($this->plugins, $plugin);
    }

    private function beforeSendPerformed(Swift_Mime_SimpleMessage $message)
    {
        $event = new Swift_Events_SendEvent($this, $message);

        foreach ($this->plugins as $plugin) {
            if (method_exists($plugin, 'beforeSendPerformed')) {
                $plugin->beforeSendPerformed($event);
            }
        }
    }

    private function sendPerformed(Swift_Mime_SimpleMessage $message)
    {
        $event = new Swift_Events_SendEvent($this, $message);

        foreach ($this->plugins as $plugin) {
            if (method_exists($plugin, 'sendPerformed')) {
                $plugin->sendPerformed($event);
            }
        }
    }

    private function numberOfRecipients(Swift_Mime_SimpleMessage $message)
    {
        return count(array_merge(
            (array) $message->getTo(), (array) $message->getCc(), (array) $message->getBcc()
        ));
    }
}