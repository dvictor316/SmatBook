<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LowStockReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $pdfContent;

    public function __construct($pdfContent)
    {
        $this->pdfContent = $pdfContent;
    }

    public function build()
    {
        return $this->subject('Critical Alert: Low Stock Report - ' . date('d M Y'))
                    ->view('emails.low-stock-alert')
                    ->attachData($this->pdfContent, 'Low_Stock_Report.pdf', [
                        'mime' => 'application/pdf',
                    ]);
    }
}