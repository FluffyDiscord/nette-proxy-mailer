<?php

namespace ProxyMailer\Mailer;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Nette\Mail\Mailer;
use Nette\Mail\Message;
use Tracy\Debugger;

class ProxyMailer implements Mailer
{
    private $client = null;

    private $endpoint = null;
    private $referer = null;
    private $basic_auth_user_password = null;
    private $host = null;
    private $port = null;
    private $username = null;
    private $password = null;
    private $security = null;

    public function __construct(
        $endpoint,
        $referer,
        $basic_auth_user_password = null,
        $host = null,
        $port = null,
        $username = null,
        $password = null,
        $security = null
    )
    {
        $this->endpoint = $endpoint;
        $this->referer = $referer;
        $this->basic_auth_user_password = $basic_auth_user_password;
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->security = $security;
    }

    function send(Message $mail): void
    {
        $from = $mail->getFrom();
        $from = array_keys($from);
        $from = reset($from);

        $payload = [
            'subject' => $mail->getSubject(),
            'from' => $from,
            'to' => [],
            'html' => $mail->getBody(),

            'host' => $this->host,
            'username' => $this->username,
            'password' => $this->password,
            'security' => $this->security,
            'port' => $this->port,

            'attachments' => []
        ];

        foreach ($mail->getAttachments() as $index => $attachment) {
            $name = $attachment->getHeader('Content-Disposition');

            $matches = [];
            preg_match('/filename="(.+)"/m', $name, $matches);

            $name = $matches[1] ?? (string)$index;

            $payload['attachments'][$name] = base64_encode($attachment->getBody());
        }

        $recipients = array_merge(
            (array)$mail->getHeader('To'),
            (array)$mail->getHeader('Cc'),
            (array)$mail->getHeader('Bcc')
        );
        foreach ($recipients as $email => $_) {
            $payload['to'][] = $email;
        }

        try {
            $this->getClient()->post('', [
                RequestOptions::JSON => $payload,
            ]);
        } catch (\Throwable $throwable) {
            if (Debugger::isEnabled()) {
                Debugger::log($throwable->getMessage(), Debugger::ERROR);
            }
        }
    }

    private function getClient()
    {
        if ($this->client === null) {
            $options = [
                'base_uri' => $this->endpoint,
                RequestOptions::HEADERS => [
                    'Referer' => $this->referer,
                ],
            ];

            if ($this->basic_auth_user_password) {
                $options[RequestOptions::AUTH] = explode(':', $this->basic_auth_user_password);
            }

            $this->client = new Client($options);
        }

        return $this->client;
    }
}