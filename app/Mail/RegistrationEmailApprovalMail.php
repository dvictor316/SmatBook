<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RegistrationEmailApprovalMail extends Mailable
{
    use SerializesModels;

    public function __construct(public readonly string $code, public readonly string $verificationUrl)
    {
    }

    public function build(): self
    {
        return $this->subject('Confirm your SmartProbook registration')
            ->view('emails.registration-email-approval');
    }
}
