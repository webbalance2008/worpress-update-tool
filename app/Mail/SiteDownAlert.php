<?php

namespace App\Mail;

use App\Models\HealthCheck;
use App\Models\Site;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SiteDownAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Site $site,
        public HealthCheck $healthCheck,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[WP Update Manager] ALERT: {$this->site->name} may be down",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.site-down',
        );
    }
}
