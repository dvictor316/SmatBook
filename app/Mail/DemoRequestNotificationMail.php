<?php

namespace App\Mail;

use App\Models\DemoRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the super-admin when a new demo request comes in.
 */
class DemoRequestNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public DemoRequest $demoRequest;

    public function __construct(DemoRequest $demoRequest)
    {
        $this->demoRequest = $demoRequest;
    }

    public function build(): self
    {
        return $this->subject('Demo Auto-Approved: ' . $this->demoRequest->company_name)
                    ->markdown('emails.demo-request-notification');
    }
}
