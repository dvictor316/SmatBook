<?php

namespace App\Mail;

use App\Models\DemoRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the applicant when their demo request is approved.
 * Carries login credentials and expiry details.
 */
class DemoApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public DemoRequest $demoRequest;
    public string $plainPassword;
    public string $loginUrl;

    public function __construct(DemoRequest $demoRequest, string $plainPassword, string $loginUrl)
    {
        $this->demoRequest   = $demoRequest;
        $this->plainPassword = $plainPassword;
        $this->loginUrl      = $loginUrl;
    }

    public function build(): self
    {
        return $this->subject('Your SmartProbook Demo is Ready!')
                    ->markdown('emails.demo-approved');
    }
}
