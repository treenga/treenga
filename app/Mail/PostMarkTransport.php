<?php

namespace App\Mail;

use Illuminate\Mail\Transport\Transport;
use Swift_Mime_SimpleMessage;
use GuzzleHttp\ClientInterface;

class PostMarkTransport extends Transport
{
    /**
     * Guzzle client instance.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected $client;

    /**
     * The SparkPost API key.
     *
     * @var string
     */
    protected $key;

    /**
     * Transmission options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Create a new SparkPost transport instance.
     *
     * @param  \GuzzleHttp\ClientInterface $client
     * @param  string $key
     * @param  array $options
     * @return void
     */
    public function __construct(ClientInterface $client, $key)
    {
        $this->key = $key;
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        $mail = [
            'From'     => config('mail.from.address'),
            'To'       => collect($message->getTo())->keys()->implode(','),
            'Subject'  => $message->getSubject(),
            'HtmlBody' => $message->getBody(),
        ];

        if ($message->getCc()) {
            $mail['Cc'] = collect($message->getCc())->keys()->implode(',');
        }

        if ($message->getBcc()) {
            $mail['Bcc'] = collect($message->getBcc())->keys()->implode(',');
        }

        $this->client->post('https://api.postmarkapp.com/email', [
            'headers' => [
                'Accept'                  => 'application/json',
                'Content-Type'            => 'application/json',
                'X-Postmark-Server-Token' => $this->key,
            ],
            'json'    => $mail,
        ]);

        $this->sendPerformed($message);

        return $this->numberOfRecipients($message);
    }
}
