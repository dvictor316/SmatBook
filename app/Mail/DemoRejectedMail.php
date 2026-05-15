<?php

namespace App\Mail;

use App\Models\DemoRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the applicant when their demo request is rejected.
 */
class DemoRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public DemoRequest $demoRequest;

    public function __construct(DemoRequest $demoRequest)
    {
        $this->demoRequest = $demoRequest;
    }

    public function build(): self
    {
        return $this->subject('Update on Your SmartProbook Demo Request')
                    ->markdown('emails.demo-rejected');
    }
}
