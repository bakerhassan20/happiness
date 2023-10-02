<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyCodeMail extends Mailable
{
    use Queueable, SerializesModels;

  
    public function __construct($code)
    {
        $this->code = $code;
    }



    public function build()
    {
        return $this->view('emails.verifyCode')
                ->subject('Verify your email address')
                ->with([
                    'code' => $this->code,
                ]);
    }
}
