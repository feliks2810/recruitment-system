<?php

namespace App\Http\Controllers;

use App\Models\Vacancy;
use App\Models\VacancyDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VacancyDocumentController extends Controller
{
    /**
     * Show upload form
     */
    public function showUploadForm(Vacancy $vacancy)
    {
        return view('vacancy-documents.upload', [
            'vacancy' => $vacancy->load('department', 'vacancyDocuments.uploadedByUser'),
        ]);
    }

    /**
     * Upload a document for a vacancy
     */
    public function upload(Request $request, Vacancy $vacancy)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Check if user can upload document
        if (!$user->can('upload-vacancy-document')) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        // Allow team_hc, team_hc_2 to upload for any department.
        // For 'kepala departemen', they must be from the same department as the vacancy.
        if (!$user->hasAnyRole(['team_hc', 'team_hc_2'])) {
            if (!$user->hasRole('kepala departemen') || $user->department_id !== $vacancy->department_id) {
                if ($request->wantsJson()) {
                    return response()->json(['error' => 'You are not authorized to upload documents for this vacancy.'], 403);
                }
                abort(403);
            }
        }

        $validated = $request->validate([
            'document' => 'required|file|mimes:pdf,doc,docx,xls,xlsx|max:10240',
            'document_type' => 'required|in:A1,B1',
        ]);

        // Validate document type matches vacancy status
        $requiredType = $vacancy->vacancy_status === 'OSPKWT' ? VacancyDocument::TYPE_A1 : VacancyDocument::TYPE_B1;
        if ($validated['document_type'] !== $requiredType) {
            $message = "Document type {$validated['document_type']} is not allowed for this vacancy. Required: {$requiredType}";
            if ($request->wantsJson()) {
                return response()->json(['error' => $message], 422);
            }
            return back()->withErrors(['document' => $message]);
        }

        // Check if document already exists for this vacancy and type
        $existingDoc = $vacancy->vacancyDocuments()
            ->where('document_type', $validated['document_type'])
            ->whereNull('deleted_at')
            ->first();

        if ($existingDoc) {
            $message = 'A document of this type already exists for this vacancy';
            if ($request->wantsJson()) {
                return response()->json(['error' => $message], 422);
            }
            return back()->withErrors(['document' => $message]);
        }

        // Store the file
        $file = $request->file('document');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('vacancy-documents', $filename, 'private');

        // Create document record
        $document = $vacancy->vacancyDocuments()->create([
            'uploaded_by_user_id' => $user->id,
            'document_type' => $validated['document_type'],
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'status' => VacancyDocument::STATUS_PENDING,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Document uploaded successfully',
                'document' => $this->formatDocument($document),
            ], 201);
        }

        return redirect()->route('mpp-submissions.index')->with('success', 'Dokumen berhasil diupload dan menunggu review');
    }

    /**
     * Download a document
     */
    public function download(VacancyDocument $document)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Check permissions
        if (!$user->can('download-vacancy-document')) {
            abort(403);
        }

        // Allow department head to download their own documents or team HC to download any
        if ($user->hasRole('kepala departemen')) {
            if ($document->uploaded_by_user_id !== $user->id && $document->vacancy->department_id !== $user->department_id) {
                abort(403);
            }
        }

        if (!Storage::disk('private')->exists($document->file_path)) {
            abort(404, 'File not found');
        }

        return Storage::disk('private')->download($document->file_path, $document->original_filename);
    }

    /**
     * Preview a document (inline view instead of download)
     */
    public function preview(Vacancy $vacancy, VacancyDocument $document)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Check permissions
        if (!$user->can('download-vacancy-document')) {
            abort(403);
        }

        // Allow team_hc, team_hc_2 to view any document.
        // For 'kepala departemen', they can only view documents for their department.
        if (!$user->hasAnyRole(['team_hc', 'team_hc_2'])) {
            if ($user->hasRole('kepala departemen') && $document->vacancy->department_id !== $user->department_id) {
                abort(403);
            }
        }

        if (!Storage::disk('private')->exists($document->file_path)) {
            abort(404, 'File not found');
        }

        // Return file inline (for preview in browser)
        return Storage::disk('private')->response($document->file_path);
    }

    /**
     * Approve a document
     */
    public function approve(Request $request, Vacancy $vacancy, VacancyDocument $document)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->can('approve-vacancy-document')) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        if ($document->status !== VacancyDocument::STATUS_PENDING) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Only pending documents can be approved'], 422);
            }
            return back()->withErrors(['error' => 'Only pending documents can be approved']);
        }

        $validated = $request->validate([
            'review_notes' => 'nullable|string',
        ]);

        $document->approve($user, $validated['review_notes'] ?? null);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Document approved successfully',
                'document' => $this->formatDocument($document),
            ]);
        }

        return back()->with('success', 'Dokumen berhasil disetujui');
    }

    /**
     * Reject a document
     */
    public function reject(Request $request, Vacancy $vacancy, VacancyDocument $document)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->can('reject-vacancy-document')) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        if ($document->status !== VacancyDocument::STATUS_PENDING) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Only pending documents can be rejected'], 422);
            }
            return back()->withErrors(['error' => 'Only pending documents can be rejected']);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        $document->reject($user, $validated['rejection_reason']);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Document rejected successfully',
                'document' => $this->formatDocument($document),
            ]);
        }

        return back()->with('success', 'Dokumen berhasil ditolak');
    }

    /**
     * Delete a document
     */
    public function destroy(Request $request, Vacancy $vacancy, VacancyDocument $document)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->can('delete-vacancy-document')) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        // Only allow deletion if document is pending or uploaded by the user
        if ($document->status !== VacancyDocument::STATUS_PENDING && $document->uploaded_by_user_id !== $user->id) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Cannot delete this document'], 403);
            }
            abort(403);
        }

        // Delete the file
        if (Storage::disk('private')->exists($document->file_path)) {
            Storage::disk('private')->delete($document->file_path);
        }

        $document->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Document deleted successfully']);
        }

        return back()->with('success', 'Dokumen berhasil dihapus');
    }

    /**
     * Format document data for JSON response
     */
    private function formatDocument(VacancyDocument $document): array
    {
        $document->load(['uploadedByUser', 'reviewedByUser']);

        return [
            'id' => $document->id,
            'vacancy_id' => $document->vacancy_id,
            'document_type' => $document->document_type,
            'original_filename' => $document->original_filename,
            'status' => $document->status,
            'uploaded_by' => $document->uploadedByUser?->name,
            'uploaded_at' => $document->created_at?->format('Y-m-d H:i:s'),
            'reviewed_by' => $document->reviewedByUser?->name,
            'reviewed_at' => $document->reviewed_at?->format('Y-m-d H:i:s'),
            'review_notes' => $document->review_notes,
        ];
    }
}
