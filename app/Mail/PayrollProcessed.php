<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\PayrollEntry;

class PayrollProcessed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PayrollEntry $entry,
        public string $pdfContents,
    )
    {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payslip - ' . $this->entry->payrollPeriod->period_code,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.payslip-submitted',
            with: [
                'employeeName' => $this->entry->employeeProfile->first_name . ' ' . $this->entry->employeeProfile->last_name,
                'periodCode' => $this->entry->payrollPeriod->period_code,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => $this->pdfContents,
                'payslip-' . $this->entry->employeeProfile->employee_number . '-' . $this->entry->payrollPeriod->period_code . '.pdf',
            )->withMime('application/pdf'),
        ];
    }
}
