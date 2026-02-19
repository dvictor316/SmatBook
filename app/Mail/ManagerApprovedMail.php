<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class ManagerApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function build()
    {
        // Aligned with path: resources/views/emails/approved.blade.php
        return $this->subject('Account Verified: Welcome to the Platform')
                    ->markdown('emails.approved');
    }
}

// --- REJECTION MAILABLE ---

class ManagerRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $reason;

    public function __construct(User $user, $reason)
    {
        $this->user = $user;
        $this->reason = $reason;
    }

    public function build()
    {
        // Aligned with path: resources/views/emails/rejected.blade.php
        return $this->subject('Application Update - Account Status')
                    ->markdown('emails.rejected');
    }
}