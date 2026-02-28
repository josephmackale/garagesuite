<?php

namespace App\Support;

use App\Models\Invoice;
use App\Models\Job;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DocumentPath
{
    /**
     * Resolve current garage_id safely.
     */
    public static function garageId($garageId = null)
    {
        if ($garageId) {
            return (int) $garageId;
        }

        $user = Auth::user();
        if (!$user || !$user->garage_id) {
            abort(403, 'No garage context found.');
        }

        return (int) $user->garage_id;
    }

    /**
     * Root folder for a garage.
     */
    public static function garageRoot($garageId = null)
    {
        $gid = self::garageId($garageId);
        return "garages/{$gid}";
    }

    /**
     * BRANDING
     */
    public static function logoPath($garageId = null, $ext = 'png')
    {
        $gid = self::garageId($garageId);
        $ext = ltrim(strtolower($ext), '.');

        return "garages/{$gid}/branding/logo.{$ext}";
    }

    /**
     * JOB CARD PDF
     */
    public static function jobCardPdfPath($job, $garageId = null)
    {
        $jobId = $job instanceof Job ? $job->id : (int) $job;

        if ($job instanceof Job) {
            $gid = self::garageId($garageId);
            if ((int) $job->garage_id !== $gid) {
                abort(403, 'Job does not belong to this garage.');
            }
        }

        return self::garageRoot($garageId) . "/jobs/job-card-{$jobId}.pdf";
    }

    /**
     * INVOICE PDF
     */
    public static function invoicePdfPath($invoice, $garageId = null)
    {
        $invoiceId = $invoice instanceof Invoice ? $invoice->id : (int) $invoice;

        if ($invoice instanceof Invoice) {
            $gid = self::garageId($garageId);
            if ((int) $invoice->garage_id !== $gid) {
                abort(403, 'Invoice does not belong to this garage.');
            }

        }

        return self::garageRoot($garageId) . "/invoices/invoice-{$invoiceId}.pdf";
    }

    /**
     * RECEIPTS (future)
     */
    public static function receiptPdfPath($receiptId, $garageId = null)
    {
        return self::garageRoot($garageId) . "/receipts/receipt-{$receiptId}.pdf";
    }

    /**
     * Slug-safe helper
     */
    public static function safeName($value, $max = 80)
    {
        return Str::limit(Str::slug($value, '-'), $max, '');
    }
}
