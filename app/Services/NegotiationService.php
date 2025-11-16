<?php

namespace App\Services;

use App\Models\PriceNegotiation;
use App\Models\NegotiationMessage;
use App\Models\Rfq;
use App\Models\User;
use App\Models\AnalyticsEvent;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NegotiationService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Initiate price negotiation
     */
    public function initiate(
        Rfq $rfq,
        User $initiator,
        string $negotiationType,
        float $originalPrice,
        float $proposedPrice,
        ?array $terms = null,
        int $maxRounds = 5,
        ?Carbon $expiresAt = null
    ): PriceNegotiation {
        return DB::transaction(function () use (
            $rfq,
            $initiator,
            $negotiationType,
            $originalPrice,
            $proposedPrice,
            $terms,
            $maxRounds,
            $expiresAt
        ) {
            // Create negotiation
            $negotiation = PriceNegotiation::create([
                'rfq_id' => $rfq->id,
                'vendor_id' => $rfq->vendor_id,
                'initiated_by' => $initiator->id,
                'negotiation_type' => $negotiationType,
                'original_price' => $originalPrice,
                'proposed_price' => $proposedPrice,
                'terms' => $terms,
                'status' => 'active',
                'round_number' => 1,
                'max_rounds' => $maxRounds,
                'expires_at' => $expiresAt ?? now()->addDays(7),
            ]);

            // Create initial message
            $this->addMessage(
                $negotiation,
                $initiator,
                'offer',
                "Initial offer: " . number_format($proposedPrice, 2),
                $proposedPrice,
                $terms
            );

            // Track analytics event
            AnalyticsEvent::track(
                'negotiation_initiated',
                $rfq->vendor_id,
                $initiator->id,
                'negotiation',
                'initiated',
                "Negotiation #{$negotiation->id}",
                [
                    'rfq_id' => $rfq->id,
                    'negotiation_id' => $negotiation->id,
                    'original_price' => $originalPrice,
                    'proposed_price' => $proposedPrice,
                ],
                $proposedPrice
            );

            // Notify the other party
            $recipientId = $initiator->id === $rfq->vendor_id ? null : $rfq->vendor_id;
            if ($recipientId) {
                $this->notificationService->notify(
                    $recipientId,
                    'negotiation_received',
                    'New Negotiation Received',
                    "You have received a new price negotiation for RFQ #{$rfq->id}",
                    $rfq->vendor_id,
                    $negotiation,
                    ['negotiation_id' => $negotiation->id],
                    null,
                    'high'
                );
            }

            return $negotiation;
        });
    }

    /**
     * Counter offer
     */
    public function counter(
        PriceNegotiation $negotiation,
        User $user,
        float $counterPrice,
        ?array $counterTerms = null,
        ?string $message = null
    ): PriceNegotiation {
        if (!$negotiation->canCounter()) {
            throw new \Exception('Cannot make counter offer - max rounds reached or negotiation expired');
        }

        return DB::transaction(function () use ($negotiation, $user, $counterPrice, $counterTerms, $message) {
            // Update negotiation
            $negotiation->counter($counterPrice, $counterTerms);

            // Add message
            $this->addMessage(
                $negotiation,
                $user,
                'counter_offer',
                $message ?? "Counter offer: " . number_format($counterPrice, 2),
                $counterPrice,
                $counterTerms
            );

            // Track analytics event
            AnalyticsEvent::track(
                'negotiation_countered',
                $negotiation->vendor_id,
                $user->id,
                'negotiation',
                'countered',
                "Negotiation #{$negotiation->id}",
                [
                    'negotiation_id' => $negotiation->id,
                    'counter_price' => $counterPrice,
                    'round' => $negotiation->round_number,
                ],
                $counterPrice
            );

            // Notify the other party
            $recipientId = $user->id === $negotiation->vendor_id
                ? $negotiation->initiated_by
                : $negotiation->vendor_id;

            $this->notificationService->notify(
                $recipientId,
                'negotiation_countered',
                'Counter Offer Received',
                "A counter offer has been made for negotiation #{$negotiation->id}",
                $negotiation->vendor_id,
                $negotiation,
                ['negotiation_id' => $negotiation->id]
            );

            return $negotiation->fresh();
        });
    }

    /**
     * Accept negotiation
     */
    public function accept(
        PriceNegotiation $negotiation,
        User $user,
        ?string $reason = null
    ): PriceNegotiation {
        if (!$negotiation->isActive()) {
            throw new \Exception('Negotiation is not active');
        }

        return DB::transaction(function () use ($negotiation, $user, $reason) {
            // Accept negotiation
            $negotiation->accept($user->id, $reason);

            // Add message
            $this->addMessage(
                $negotiation,
                $user,
                'acceptance',
                "Accepted at price: " . number_format($negotiation->final_price, 2)
            );

            // Track analytics event
            AnalyticsEvent::track(
                'negotiation_accepted',
                $negotiation->vendor_id,
                $user->id,
                'negotiation',
                'accepted',
                "Negotiation #{$negotiation->id}",
                [
                    'negotiation_id' => $negotiation->id,
                    'final_price' => $negotiation->final_price,
                    'rounds' => $negotiation->round_number,
                ],
                $negotiation->final_price
            );

            // Notify the other party
            $recipientId = $user->id === $negotiation->vendor_id
                ? $negotiation->initiated_by
                : $negotiation->vendor_id;

            $this->notificationService->notify(
                $recipientId,
                'negotiation_accepted',
                'Negotiation Accepted',
                "Your negotiation has been accepted at " . number_format($negotiation->final_price, 2),
                $negotiation->vendor_id,
                $negotiation,
                ['negotiation_id' => $negotiation->id],
                null,
                'high'
            );

            return $negotiation->fresh();
        });
    }

    /**
     * Reject negotiation
     */
    public function reject(
        PriceNegotiation $negotiation,
        User $user,
        ?string $reason = null
    ): PriceNegotiation {
        if (!$negotiation->isActive()) {
            throw new \Exception('Negotiation is not active');
        }

        return DB::transaction(function () use ($negotiation, $user, $reason) {
            // Reject negotiation
            $negotiation->reject($user->id, $reason);

            // Add message
            $this->addMessage(
                $negotiation,
                $user,
                'rejection',
                $reason ?? 'Negotiation rejected'
            );

            // Track analytics event
            AnalyticsEvent::track(
                'negotiation_rejected',
                $negotiation->vendor_id,
                $user->id,
                'negotiation',
                'rejected',
                "Negotiation #{$negotiation->id}",
                ['negotiation_id' => $negotiation->id]
            );

            // Notify the other party
            $recipientId = $user->id === $negotiation->vendor_id
                ? $negotiation->initiated_by
                : $negotiation->vendor_id;

            $this->notificationService->notify(
                $recipientId,
                'negotiation_rejected',
                'Negotiation Rejected',
                "Your negotiation has been rejected" . ($reason ? ": {$reason}" : ''),
                $negotiation->vendor_id,
                $negotiation,
                ['negotiation_id' => $negotiation->id],
                null,
                'high'
            );

            return $negotiation->fresh();
        });
    }

    /**
     * Withdraw negotiation
     */
    public function withdraw(PriceNegotiation $negotiation, User $user): void
    {
        $negotiation->withdraw();

        // Add message
        $this->addMessage($negotiation, $user, 'update', 'Negotiation withdrawn');

        // Notify the other party
        $recipientId = $user->id === $negotiation->vendor_id
            ? $negotiation->initiated_by
            : $negotiation->vendor_id;

        $this->notificationService->notify(
            $recipientId,
            'negotiation_withdrawn',
            'Negotiation Withdrawn',
            "A negotiation has been withdrawn",
            $negotiation->vendor_id,
            $negotiation
        );
    }

    /**
     * Add message to negotiation
     */
    public function addMessage(
        PriceNegotiation $negotiation,
        User $sender,
        string $messageType,
        string $message,
        ?float $offeredPrice = null,
        ?array $offeredTerms = null
    ): NegotiationMessage {
        return NegotiationMessage::create([
            'negotiation_id' => $negotiation->id,
            'sender_id' => $sender->id,
            'message_type' => $messageType,
            'message' => $message,
            'offered_price' => $offeredPrice,
            'offered_terms' => $offeredTerms,
            'is_admin_message' => $sender->role === 'admin',
        ]);
    }

    /**
     * Get negotiations for vendor
     */
    public function getNegotiationsForVendor(
        User $vendor,
        ?string $status = null
    ) {
        $query = PriceNegotiation::forVendor($vendor->id)
            ->with(['rfq', 'initiator', 'decider', 'messages'])
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    /**
     * Get negotiations for RFQ
     */
    public function getNegotiationsForRfq(Rfq $rfq)
    {
        return PriceNegotiation::forRfq($rfq->id)
            ->with(['initiator', 'decider', 'messages'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get negotiation statistics
     */
    public function getNegotiationStats(User $vendor, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $negotiations = PriceNegotiation::forVendor($vendor->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $accepted = $negotiations->where('status', 'accepted');

        return [
            'total_negotiations' => $negotiations->count(),
            'active' => $negotiations->where('status', 'active')->count(),
            'accepted' => $accepted->count(),
            'rejected' => $negotiations->where('status', 'rejected')->count(),
            'expired' => $negotiations->where('status', 'expired')->count(),
            'withdrawn' => $negotiations->where('status', 'withdrawn')->count(),
            'acceptance_rate' => $negotiations->count() > 0
                ? ($accepted->count() / $negotiations->count() * 100)
                : 0,
            'average_discount' => $accepted->count() > 0
                ? $accepted->avg('discount_percentage')
                : 0,
            'total_savings' => $accepted->sum('discount_amount'),
            'average_rounds' => $accepted->avg('round_number'),
            'by_type' => $negotiations->groupBy('negotiation_type')->map->count(),
        ];
    }

    /**
     * Process expired negotiations
     */
    public function processExpiredNegotiations(): int
    {
        $expiredNegotiations = PriceNegotiation::expired()->get();

        foreach ($expiredNegotiations as $negotiation) {
            $negotiation->markAsExpired();

            // Notify parties
            $this->notificationService->notify(
                $negotiation->vendor_id,
                'negotiation_expired',
                'Negotiation Expired',
                "Negotiation for RFQ #{$negotiation->rfq_id} has expired",
                $negotiation->vendor_id,
                $negotiation
            );

            $this->notificationService->notify(
                $negotiation->initiated_by,
                'negotiation_expired',
                'Negotiation Expired',
                "Negotiation for RFQ #{$negotiation->rfq_id} has expired",
                $negotiation->vendor_id,
                $negotiation
            );
        }

        return $expiredNegotiations->count();
    }

    /**
     * Mark messages as read
     */
    public function markMessagesAsRead(PriceNegotiation $negotiation, User $user): void
    {
        $negotiation->messages()
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->each(fn($message) => $message->markAsRead());
    }

    /**
     * Get unread message count
     */
    public function getUnreadCount(PriceNegotiation $negotiation, User $user): int
    {
        return $negotiation->messages()
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->count();
    }
}
