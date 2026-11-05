<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailVerificationCodeMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public readonly string $code,
        public readonly string $expiresAt
    ) {
    }

    public function build(): self
    {
        return $this->subject('Verify your SmartProbook email')
            ->view('emails.email-verification-code');
    }
}
