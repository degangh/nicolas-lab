<?php

namespace App\Services;

use Aws\Ses\SesClient;
use Aws\Exception\AwsException;

class MailService
{
    protected $sesClient;

    public function __construct()
    {
        $this->sesClient = new SesClient([
            'version' => 'latest',
            'region'  => env('AWS_REGION'),
            'credentials' => [
                'key'    => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);
    }

    public function sendEmail($toAddress, $subject, $body, $fromName, $fromAddress, $replyToAddress = null, $attachmentPath = null)
    {
        try {
            $boundary = md5(uniqid(time()));

            // MIME message with the attachment
            $rawMessage = "From: {$fromName} <{$fromAddress}>\r\n";
            $rawMessage .= "To: {$toAddress}\r\n";
            $rawMessage .= "Subject: {$subject}\r\n";
            $rawMessage .= "MIME-Version: 1.0\r\n";
            $rawMessage .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n\r\n";

            // Email body
            $rawMessage .= "--{$boundary}\r\n";
            $rawMessage .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
            $rawMessage .= "{$body}\r\n\r\n";

            // Add attachment if provided
            if ($attachmentPath && file_exists($attachmentPath)) {
                $fileName = basename($attachmentPath);
                $fileData = file_get_contents($attachmentPath);
                $fileEncoded = base64_encode($fileData);

                $rawMessage .= "--{$boundary}\r\n";
                $rawMessage .= "Content-Type: application/octet-stream; name=\"{$fileName}\"\r\n";
                $rawMessage .= "Content-Disposition: attachment; filename=\"{$fileName}\"\r\n";
                $rawMessage .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $rawMessage .= chunk_split($fileEncoded) . "\r\n\r\n";
            }

            // End MIME message
            $rawMessage .= "--{$boundary}--";

            $params = [
                'RawMessage' => [
                    'Data' => base64_encode($rawMessage),
                ],
                'Source' => "{$fromName} <{$fromAddress}>", // Sender's name and verified email
                'Destinations' => [$toAddress],
            ];

            if ($replyToAddress) {
                $params['ReplyToAddresses'] = [$replyToAddress];
            }

            $result = $this->sesClient->sendRawEmail($params);

            return $result;

        } catch (AwsException $e) {
            return $e->getMessage();
        }
    }
}
