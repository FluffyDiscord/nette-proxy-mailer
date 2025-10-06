<?php

namespace ProxyMailer\Mailer;

use Nette\Mail\Mailer;
use Nette\Mail\Message;
use Tracy\Debugger;

class ProxyMailer implements Mailer
{
    private $endpoint = null;
    private $basic_auth_user_password = null;
    private $host = null;
    private $port = null;
    private $username = null;
    private $password = null;
    private $security = null;

    public function __construct(
        $endpoint,
        $basic_auth_user_password = null,
        $host = null,
        $port = null,
        $username = null,
        $password = null,
        $security = null
    )
    {
        $this->endpoint = $endpoint;
        $this->basic_auth_user_password = $basic_auth_user_password;
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->security = $security;
    }

    function send(Message $mail): void
    {
        $ch = curl_init();

        $rawPayload = [
            'subject' => $mail->getSubject(),
            'from' => $mail->getFrom(),
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

            $name = isset($matches[1]) ? $matches[1] : (string)$index;

            $rawPayload['attachments'][$name] = base64_encode($attachment->getBody());
        }

        foreach (array_merge(
            (array) $mail->getHeader('To'),
            (array) $mail->getHeader('Cc'),
            (array) $mail->getHeader('Bcc')
        ) as $email => $_) {
            $rawPayload['to'][] = $email;
        }

        $payload = json_encode($rawPayload);

        $headers = [
            'Content-Type: application/json',
        ];
        if($this->basic_auth_user_password) {
            $headers[] = 'Authorization: Basic ' . base64_encode($this->basic_auth_user_password);
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            Debugger::log(curl_error($ch), Debugger::ERROR);
        }

        curl_close($ch);
    }
}