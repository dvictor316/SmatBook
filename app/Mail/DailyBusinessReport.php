<?php

namespace App\Mail;

use App\Models\Sale;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyBusinessReport extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
   public $data;

public function __construct($data)
{
    $this->data = $data;
}

public function build()
{
    return $this->subject('Daily Business Performance Summary - ' . date('d M Y'))
                ->view('emails.daily_summary');
}

    /**
     * Get the message envelope (Subject line).
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Daily Business Intelligence Summary - ' . now()->format('d M, Y'),
        );
    }

    /**
     * Get the message content definition (The View and the Data).
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.daily_report',
            with: [
                'revenue'  => Sale::whereDate('created_at', today())->sum('total'),
                'lowStock' => Product::where('stock', '<', 5)->count(),
                'pending'  => Sale::where('payment_status', '!=', 'paid')->sum('total'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }

    
}