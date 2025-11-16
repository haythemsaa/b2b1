<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Services\DocumentService;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentController extends Controller
{
    protected DocumentService $documentService;

    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    public function index(Request $request)
    {
        $vendor = Auth::user();

        $documents = $this->documentService->getDocumentsForVendor(
            $vendor,
            $request->document_type,
            $request->boolean('archived', false)
        );

        return response()->json([
            'status' => 'success',
            'data' => $documents,
        ]);
    }

    public function upload(Request $request)
    {
        $vendor = Auth::user();

        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'document_type' => 'required|in:invoice,contract,certificate,tax_document,shipping_document,product_specification,quality_certificate,compliance_document,insurance_document,other',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'document_number' => 'nullable|string|max:100',
            'document_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:today',
            'visibility' => 'in:private,vendor_only,shared',
            'is_confidential' => 'boolean',
        ]);

        $document = $this->documentService->upload(
            $vendor,
            $vendor,
            $request->file('file'),
            $request->document_type,
            $request->name,
            null,
            $request->description,
            $request->document_number,
            $request->document_date ? \Carbon\Carbon::parse($request->document_date) : null,
            $request->expiry_date ? \Carbon\Carbon::parse($request->expiry_date) : null,
            $request->metadata,
            $request->visibility ?? 'vendor_only',
            $request->boolean('requires_approval', false),
            $request->boolean('is_confidential', false)
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Document uploaded successfully',
            'data' => $document,
        ], 201);
    }

    public function show(Document $document)
    {
        $user = Auth::user();

        if (!$document->canAccess($user)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => $document,
        ]);
    }

    public function download(Document $document)
    {
        $user = Auth::user();

        try {
            $filePath = $this->documentService->download($document, $user);

            return response()->download($filePath, $document->file_name);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    public function share(Request $request, Document $document)
    {
        $user = Auth::user();

        $request->validate([
            'shared_with' => 'required|integer|exists:users,id',
            'permission' => 'required|in:view,download,edit',
            'expires_in_days' => 'nullable|integer|min:1|max:365',
            'message' => 'nullable|string|max:500',
        ]);

        $sharedWith = User::findOrFail($request->shared_with);
        $expiresAt = $request->expires_in_days
            ? now()->addDays($request->expires_in_days)
            : null;

        $share = $this->documentService->share(
            $document,
            $user,
            $sharedWith,
            $request->permission,
            $expiresAt,
            $request->message
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Document shared successfully',
            'data' => $share,
        ]);
    }

    public function archive(Document $document)
    {
        $vendor = Auth::user();

        if ($document->vendor_id !== $vendor->id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $this->documentService->archive($document);

        return response()->json([
            'status' => 'success',
            'message' => 'Document archived successfully',
        ]);
    }

    public function delete(Document $document)
    {
        $vendor = Auth::user();

        if ($document->vendor_id !== $vendor->id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $this->documentService->delete($document);

        return response()->json([
            'status' => 'success',
            'message' => 'Document deleted successfully',
        ]);
    }

    public function expiring(Request $request)
    {
        $vendor = Auth::user();

        $documents = $this->documentService->getExpiringDocuments($vendor, $request->days ?? 30);

        return response()->json([
            'status' => 'success',
            'data' => $documents,
        ]);
    }

    public function stats(Request $request)
    {
        $vendor = Auth::user();

        $stats = $this->documentService->getDocumentStats($vendor);

        return response()->json([
            'status' => 'success',
            'data' => $stats,
        ]);
    }
}
