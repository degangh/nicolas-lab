<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PieMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $options)
    {
        $this->data = $data;
        $this->options = $options;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $email = $this->from($this->options['from'], $this->options['fromScreenName'])->subject($this->options['subject'])
                    ->view($this->options['emailTemplate'])
                    ->with('data', $this->data);

        if ($this->options['attachment']) $email->attach(storage_path($this->options['attachment']));
    }

    public function configure()
    {
        // Specify the configuration set name
        return $this->withSwiftMessage(function ($message) {
            $message->getHeaders()->addTextHeader('X-SES-CONFIGURATION-SET', 'my-first-configuration-set');
        });
    }
}
