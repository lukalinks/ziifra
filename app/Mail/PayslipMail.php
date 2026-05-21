<?php

namespace App\Mail;

use App\Models\PayrollItem;
use App\Models\User;
use App\Services\PayslipPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PayslipMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PayrollItem $item,
        public ?User $sentBy = null,
    ) {}

    public function envelope(): Envelope
    {
        $this->item->loadMissing('payrollRun', 'organization');

        $organization = $this->item->organization;
        $run = $this->item->payrollRun;
        $locale = $organization->locale ?? config('app.locale');

        $replyTo = $organization->notificationReplyTo();

        return new Envelope(
            subject: __('payroll.email_subject', [
                'period' => $run->periodLabel(),
                'organization' => $organization->name,
            ], $locale),
            replyTo: $replyTo ? [new Address($replyTo)] : [],
        );
    }

    public function content(): Content
    {
        $this->item->loadMissing('payrollRun', 'organization');

        $organization = $this->item->organization;
        $run = $this->item->payrollRun;
        $locale = $organization->locale ?? config('app.locale');

        return new Content(
            markdown: 'emails.payslip',
            with: [
                'locale' => $locale,
                'employeeName' => $this->item->employeeName(),
                'periodLabel' => $run->periodLabel(),
                'organizationName' => $organization->name,
                'sentByLine' => $this->sentBy !== null
                    ? __('payroll.email_sent_by', ['name' => $this->sentBy->name], $locale)
                    : null,
                'intro' => __('payroll.email_intro', [
                    'employee' => $this->item->employeeName(),
                    'period' => $run->periodLabel(),
                    'organization' => $organization->displayName(),
                ], $locale),
                'attachmentNote' => __('payroll.email_attachment_note', [], $locale),
                'footerNotice' => __('payroll.email_footer_notice', [], $locale),
            ],
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $this->item->loadMissing('payrollRun', 'organization');

        $pdfService = app(PayslipPdfService::class);

        $binary = $pdfService->makePdf(
            $this->item->organization,
            $this->item->payrollRun,
            $this->item,
        )->output();

        $filename = $pdfService->suggestedFilename($this->item->payrollRun, $this->item);

        return [
            Attachment::fromData(fn () => $binary, $filename)
                ->withMime('application/pdf'),
        ];
    }
}
