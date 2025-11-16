<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DocumentService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Upload document
     */
    public function upload(
        User $vendor,
        User $uploader,
        UploadedFile $file,
        string $documentType,
        string $name,
        ?Model $documentable = null,
        ?string $description = null,
        ?string $documentNumber = null,
        ?Carbon $documentDate = null,
        ?Carbon $expiryDate = null,
        ?array $metadata = null,
        string $visibility = 'vendor_only',
        bool $requiresApproval = false,
        bool $isConfidential = false
    ): Document {
        // Generate file name
        $fileName = $this->generateFileName($file);

        // Store file
        $path = $file->storeAs('documents/' . $vendor->id, $fileName, 'private');

        // Calculate file hash
        $fileHash = hash_file('sha256', $file->getRealPath());

        // Create document
        $document = Document::create([
            'vendor_id' => $vendor->id,
            'uploaded_by' => $uploader->id,
            'documentable_id' => $documentable?->id,
            'documentable_type' => $documentable ? get_class($documentable) : null,
            'name' => $name,
            'description' => $description,
            'document_type' => $documentType,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'file_hash' => $fileHash,
            'document_number' => $documentNumber,
            'document_date' => $documentDate,
            'expiry_date' => $expiryDate,
            'metadata' => $metadata,
            'visibility' => $visibility,
            'requires_approval' => $requiresApproval,
            'approval_status' => $requiresApproval ? 'pending' : 'approved',
            'is_confidential' => $isConfidential,
        ]);

        // Notify if requires approval
        if ($requiresApproval) {
            // Notify admins for approval
            $this->notifyForApproval($document);
        }

        return $document;
    }

    /**
     * Update document
     */
    public function update(
        Document $document,
        array $data
    ): Document {
        // Handle file replacement if provided
        if (isset($data['file'])) {
            $this->replaceFile($document, $data['file']);
            unset($data['file']);
        }

        $document->update($data);
        return $document->fresh();
    }

    /**
     * Replace document file
     */
    public function replaceFile(Document $document, UploadedFile $file): void
    {
        // Delete old file
        $document->deleteFile();

        // Generate new file name
        $fileName = $this->generateFileName($file);

        // Store new file
        $path = $file->storeAs('documents/' . $document->vendor_id, $fileName, 'private');

        // Calculate new file hash
        $fileHash = hash_file('sha256', $file->getRealPath());

        // Update document
        $document->update([
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'file_hash' => $fileHash,
        ]);
    }

    /**
     * Download document
     */
    public function download(Document $document, User $user): string
    {
        if (!$document->canAccess($user)) {
            throw new \Exception('You do not have permission to access this document');
        }

        // Increment download count
        $document->incrementDownloadCount();

        // Return file path for download
        return Storage::disk('private')->path($document->file_path);
    }

    /**
     * Share document
     */
    public function share(
        Document $document,
        User $sharedBy,
        User $sharedWith,
        string $permission = 'view',
        ?Carbon $expiresAt = null,
        ?string $message = null
    ): DocumentShare {
        $share = DocumentShare::create([
            'document_id' => $document->id,
            'shared_by' => $sharedBy->id,
            'shared_with' => $sharedWith->id,
            'permission' => $permission,
            'expires_at' => $expiresAt,
            'share_message' => $message,
        ]);

        // Notify recipient
        $this->notificationService->notify(
            $sharedWith->id,
            'document_shared',
            'Document Shared With You',
            "{$sharedBy->name} shared a document with you: {$document->name}",
            $document->vendor_id,
            $document,
            ['document_id' => $document->id],
            null,
            'medium'
        );

        return $share;
    }

    /**
     * Revoke share
     */
    public function revokeShare(DocumentShare $share): void
    {
        $share->delete();
    }

    /**
     * Approve document
     */
    public function approve(Document $document, User $approver): void
    {
        $document->approve($approver->id);

        // Notify uploader
        $this->notificationService->notify(
            $document->uploaded_by,
            'document_approved',
            'Document Approved',
            "Your document '{$document->name}' has been approved",
            $document->vendor_id,
            $document
        );
    }

    /**
     * Reject document
     */
    public function reject(Document $document, User $approver, ?string $reason = null): void
    {
        $document->reject($approver->id);

        // Notify uploader
        $this->notificationService->notify(
            $document->uploaded_by,
            'document_rejected',
            'Document Rejected',
            "Your document '{$document->name}' has been rejected" . ($reason ? ": {$reason}" : ''),
            $document->vendor_id,
            $document,
            ['reason' => $reason]
        );
    }

    /**
     * Archive document
     */
    public function archive(Document $document): void
    {
        $document->archive();
    }

    /**
     * Unarchive document
     */
    public function unarchive(Document $document): void
    {
        $document->unarchive();
    }

    /**
     * Delete document
     */
    public function delete(Document $document): void
    {
        // Delete file from storage
        $document->deleteFile();

        // Soft delete document
        $document->delete();
    }

    /**
     * Get documents for vendor
     */
    public function getDocumentsForVendor(
        User $vendor,
        ?string $documentType = null,
        ?bool $archived = false,
        ?Model $documentable = null
    ) {
        $query = Document::forVendor($vendor->id)
            ->with(['uploader', 'documentable'])
            ->orderBy('created_at', 'desc');

        if ($documentType) {
            $query->byType($documentType);
        }

        if (!$archived) {
            $query->notArchived();
        }

        if ($documentable) {
            $query->where('documentable_type', get_class($documentable))
                  ->where('documentable_id', $documentable->id);
        }

        return $query->get();
    }

    /**
     * Get shared documents for user
     */
    public function getSharedDocuments(User $user)
    {
        return DocumentShare::forUser($user->id)
            ->active()
            ->with(['document', 'sharer'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get expiring documents
     */
    public function getExpiringDocuments(User $vendor, int $days = 30)
    {
        return Document::forVendor($vendor->id)
            ->expiringSoon($days)
            ->notArchived()
            ->orderBy('expiry_date', 'asc')
            ->get();
    }

    /**
     * Get expired documents
     */
    public function getExpiredDocuments(User $vendor)
    {
        return Document::forVendor($vendor->id)
            ->expired()
            ->notArchived()
            ->orderBy('expiry_date', 'desc')
            ->get();
    }

    /**
     * Get pending approval documents
     */
    public function getPendingApprovalDocuments()
    {
        return Document::pendingApproval()
            ->with(['vendor', 'uploader'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Process expiring documents notifications
     */
    public function processExpiringDocuments(): int
    {
        $documents = Document::expiringSoon(30)->get();

        foreach ($documents as $document) {
            // Notify vendor
            $this->notificationService->notify(
                $document->vendor_id,
                'document_expiring',
                'Document Expiring Soon',
                "Document '{$document->name}' will expire on {$document->expiry_date->format('Y-m-d')}",
                $document->vendor_id,
                $document,
                ['document_id' => $document->id],
                null,
                'medium'
            );
        }

        return $documents->count();
    }

    /**
     * Get document statistics
     */
    public function getDocumentStats(User $vendor): array
    {
        $documents = Document::forVendor($vendor->id)->get();

        return [
            'total_documents' => $documents->count(),
            'by_type' => $documents->groupBy('document_type')->map->count(),
            'total_size' => $documents->sum('file_size'),
            'total_size_human' => $this->formatBytes($documents->sum('file_size')),
            'archived' => $documents->where('is_archived', true)->count(),
            'confidential' => $documents->where('is_confidential', true)->count(),
            'pending_approval' => $documents->where('approval_status', 'pending')->count(),
            'expiring_soon' => Document::forVendor($vendor->id)->expiringSoon(30)->count(),
            'expired' => Document::forVendor($vendor->id)->expired()->count(),
            'total_downloads' => $documents->sum('download_count'),
        ];
    }

    /**
     * Generate unique file name
     */
    protected function generateFileName(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        return Str::random(40) . '.' . $extension;
    }

    /**
     * Notify for approval
     */
    protected function notifyForApproval(Document $document): void
    {
        // Get admin users
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            $this->notificationService->notify(
                $admin->id,
                'document_requires_approval',
                'Document Requires Approval',
                "A new document '{$document->name}' requires approval",
                $document->vendor_id,
                $document,
                ['document_id' => $document->id],
                null,
                'medium'
            );
        }
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
