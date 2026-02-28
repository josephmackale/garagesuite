<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    /**
     * All documents (with optional filters: q, type)
     * GET /documents
     */
    public function index(Request $request)
    {
        return $this->listDocuments($request, null, 'All Documents');
    }

    /**
     * Invoices
     * GET /documents/invoices
     */
    public function invoices(Request $request)
    {
        return $this->listDocuments($request, 'invoice_pdf', 'Invoice Documents');
    }

    /**
     * Job cards
     * GET /documents/job-cards
     */
    public function jobCards(Request $request)
    {
        return $this->listDocuments($request, 'job_card_pdf', 'Job Card Documents');
    }

    /**
     * Receipts
     * GET /documents/receipts
     */
    public function receipts(Request $request)
    {
        return $this->listDocuments($request, 'receipt_pdf', 'Receipt Documents');
    }

    /**
     * Other
     * GET /documents/other
     */
    public function other(Request $request)
    {
        // "other" can be literal document_type=other, but also anything not in the known types
        // If you want STRICT other only, change the query below to ->where('document_type', 'other')
        return $this->listDocuments($request, '__OTHER__', 'Other Documents');
    }

    /**
     * Secure inline view (browser preview)
     * GET /documents/{document}/view
     */
    public function view(Document $document)
    {
        $this->authorizeGarage($document);

        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);

        $stream = Storage::disk($document->disk)->readStream($document->path);
        abort_unless($stream, 404);

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type'        => $document->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . ($document->file_name ?? basename($document->path)) . '"',
        ]);
    }

    /**
     * Secure download
     * GET /documents/{document}/download
     */
    public function download(Document $document)
    {
        $this->authorizeGarage($document);

        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);

        return Storage::disk($document->disk)->download(
            $document->path,
            $document->file_name ?? basename($document->path)
        );
    }

    /**
     * Soft delete from archive
     * DELETE /documents/{document}
     */
    public function destroy(Document $document)
    {
        $this->authorizeGarage($document);

        $document->delete();

        return back()->with('success', 'Document removed from archive.');
    }

    /**
     * Shared listing logic (pagination + search + scoping)
     */
      protected function listDocuments(Request $request, ?string $type = null, string $title = 'All Documents')
      {
          $garageId = Auth::user()->garage_id;

          $q = trim((string) $request->query('q', ''));

          $query = Document::query()
              ->where('garage_id', $garageId)
              ->orderByDesc('updated_at');

          if ($type === '__OTHER__') {
              $known = ['invoice_pdf', 'job_card_pdf', 'receipt_pdf'];
              $query->whereNotIn('document_type', $known);
          } elseif (!is_null($type)) {
              $query->where('document_type', $type);
          }

          // ✅ Server-side search
          if ($q !== '') {
              $query->where(function ($qq) use ($q) {
                  $qq->where('name', 'like', "%{$q}%")
                     ->orWhere('file_name', 'like', "%{$q}%");
              });
          }

          $documents = $query->paginate(30)->withQueryString();

          return view('documents.index', [
              'documents' => $documents,
              'pageTitle' => $title,
              'q'         => $q,
          ]);
      }


    /**
     * Garage authorization (multi-tenant lock)
     */
    private function authorizeGarage(Document $document): void
    {
        $garageId = Auth::user()->garage_id;
        abort_if((int) $document->garage_id !== (int) $garageId, 403);
    }
  
  	public function bulkDownload(Request $request)
    {
        $garageId = Auth::user()->garage_id;

        $ids = $request->input('ids', []);
        if (!is_array($ids) || count($ids) === 0) {
            return back()->with('error', 'Select at least one document.');
        }

        // Only allow docs from this garage
        $docs = Document::query()
            ->where('garage_id', $garageId)
            ->whereIn('id', $ids)
            ->get();

        if ($docs->isEmpty()) {
            return back()->with('error', 'No valid documents selected.');
        }

        // Build a temp zip
        $zipName = 'documents-' . now()->format('Ymd-His') . '.zip';
        $tmpPath = storage_path('app/tmp');
        if (!is_dir($tmpPath)) mkdir($tmpPath, 0755, true);

        $zipFile = $tmpPath . '/' . Str::uuid() . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE) !== true) {
            return back()->with('error', 'Failed to create ZIP.');
        }

        foreach ($docs as $doc) {
            if (!Storage::disk($doc->disk)->exists($doc->path)) {
                continue;
            }

            // Safer filename
            $name = $doc->file_name ?: basename($doc->path);
            $name = preg_replace('/[^\w\-. ]+/u', '-', $name);

            // If duplicates, prefix with id
            $zipEntry = $doc->id . '-' . $name;

            $zip->addFromString($zipEntry, Storage::disk($doc->disk)->get($doc->path));
        }

        $zip->close();

        return response()->download($zipFile, $zipName)->deleteFileAfterSend(true);
    }

    public function bulkDelete(Request $request)
    {
        $garageId = Auth::user()->garage_id;

        $ids = $request->input('ids', []);
        if (!is_array($ids) || count($ids) === 0) {
            return back()->with('error', 'Select at least one document.');
        }

        Document::query()
            ->where('garage_id', $garageId)
            ->whereIn('id', $ids)
            ->delete(); // soft delete (if model uses SoftDeletes)

        return back()->with('success', 'Selected documents removed from archive.');
    }
}
