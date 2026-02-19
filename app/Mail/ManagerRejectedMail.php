<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class ManagerRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $reason;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, $reason = "Criteria not met")
    {
        $this->user = $user;
        $this->reason = $reason;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        // Path corrected to align with your View/emails folder structure
        return $this->subject('Update regarding your Manager Application')
                    ->markdown('emails.rejected');
    }
}