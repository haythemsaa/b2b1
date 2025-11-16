<?php

namespace App\Services;

use App\Models\Rfq;
use App\Models\RfqItem;
use App\Models\RfqQuote;
use App\Models\RfqNegotiation;
use App\Models\User;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RfqService
{
    /**
     * Create new RFQ for vendor
     */
    public function createRfq(
        User $vendor,
        string $title,
        array $items,
        ?string $description = null,
        ?float $targetBudget = null,
        ?string $requiredDeliveryDate = null,
        string $priority = 'medium',
        ?int $expiresInDays = 30
    ): Rfq {
        return DB::transaction(function () use (
            $vendor,
            $title,
            $items,
            $description,
            $targetBudget,
            $requiredDeliveryDate,
            $priority,
            $expiresInDays
        ) {
            // Create RFQ
            $rfq = Rfq::create([
                'vendor_id' => $vendor->id,
                'title' => $title,
                'description' => $description,
                'target_budget' => $targetBudget,
                'required_delivery_date' => $requiredDeliveryDate,
                'priority' => $priority,
                'status' => 'draft',
                'expires_at' => $expiresInDays ? now()->addDays($expiresInDays) : null,
            ]);

            // Create RFQ items
            foreach ($items as $item) {
                $rfq->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'product_sku' => $item['sku'] ?? null,
                    'product_name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'quantity_requested' => $item['quantity'],
                    'unit' => $item['unit'] ?? 'pcs',
                    'specifications' => $item['specifications'] ?? null,
                ]);
            }

            Log::info('RFQ created', [
                'rfq_id' => $rfq->id,
                'vendor_id' => $vendor->id,
                'items_count' => count($items),
            ]);

            return $rfq->load('items');
        });
    }

    /**
     * Update RFQ (only in draft status)
     */
    public function updateRfq(
        Rfq $rfq,
        array $data
    ): Rfq {
        if (!$rfq->canBeEdited()) {
            throw new \Exception('RFQ cannot be edited in current status');
        }

        return DB::transaction(function () use ($rfq, $data) {
            $rfq->update([
                'title' => $data['title'] ?? $rfq->title,
                'description' => $data['description'] ?? $rfq->description,
                'target_budget' => $data['target_budget'] ?? $rfq->target_budget,
                'required_delivery_date' => $data['required_delivery_date'] ?? $rfq->required_delivery_date,
                'priority' => $data['priority'] ?? $rfq->priority,
            ]);

            // Update items if provided
            if (isset($data['items'])) {
                // Delete existing items
                $rfq->items()->delete();

                // Create new items
                foreach ($data['items'] as $item) {
                    $rfq->items()->create([
                        'product_id' => $item['product_id'] ?? null,
                        'product_sku' => $item['sku'] ?? null,
                        'product_name' => $item['name'],
                        'description' => $item['description'] ?? null,
                        'quantity_requested' => $item['quantity'],
                        'unit' => $item['unit'] ?? 'pcs',
                        'specifications' => $item['specifications'] ?? null,
                    ]);
                }
            }

            Log::info('RFQ updated', ['rfq_id' => $rfq->id]);

            return $rfq->load('items');
        });
    }

    /**
     * Submit RFQ to admin
     */
    public function submitRfq(Rfq $rfq): void
    {
        if ($rfq->status !== 'draft') {
            throw new \Exception('Only draft RFQs can be submitted');
        }

        if ($rfq->items()->count() === 0) {
            throw new \Exception('RFQ must have at least one item');
        }

        $rfq->submit();

        Log::info('RFQ submitted', ['rfq_id' => $rfq->id]);
    }

    /**
     * Create quote for RFQ (admin function)
     */
    public function createQuote(
        Rfq $rfq,
        User $admin,
        array $items,
        string $paymentTerms = 'net_30',
        ?int $deliveryDays = null,
        ?int $validForDays = 30,
        ?string $termsAndConditions = null,
        ?string $notes = null
    ): RfqQuote {
        if (!$rfq->canBeQuoted()) {
            throw new \Exception('RFQ cannot be quoted in current status or is expired');
        }

        return DB::transaction(function () use (
            $rfq,
            $admin,
            $items,
            $paymentTerms,
            $deliveryDays,
            $validForDays,
            $termsAndConditions,
            $notes
        ) {
            // Update item prices
            $subtotal = 0;
            foreach ($items as $itemData) {
                $item = RfqItem::find($itemData['id']);
                if ($item && $item->rfq_id === $rfq->id) {
                    $item->setQuote(
                        $itemData['unit_price'],
                        $itemData['notes'] ?? null
                    );
                    $subtotal += $item->quoted_subtotal;
                }
            }

            // Calculate tax (19% Tunisia)
            $tax = $subtotal * 0.19;
            $total = $subtotal + $tax;

            // Create quote
            $quote = RfqQuote::create([
                'rfq_id' => $rfq->id,
                'quoted_by' => $admin->id,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'payment_terms' => $paymentTerms,
                'delivery_days' => $deliveryDays,
                'terms_and_conditions' => $termsAndConditions,
                'notes' => $notes,
                'valid_until' => $validForDays ? now()->addDays($validForDays) : null,
                'status' => 'draft',
            ]);

            Log::info('Quote created', [
                'quote_id' => $quote->id,
                'rfq_id' => $rfq->id,
                'total' => $total,
            ]);

            return $quote;
        });
    }

    /**
     * Send quote to vendor
     */
    public function sendQuote(RfqQuote $quote): void
    {
        if ($quote->status !== 'draft') {
            throw new \Exception('Only draft quotes can be sent');
        }

        $quote->send();

        Log::info('Quote sent', [
            'quote_id' => $quote->id,
            'rfq_id' => $quote->rfq_id,
        ]);
    }

    /**
     * Accept quote (vendor function)
     */
    public function acceptQuote(Rfq $rfq, RfqQuote $quote, User $vendor): void
    {
        if ($rfq->vendor_id !== $vendor->id) {
            throw new \Exception('Unauthorized');
        }

        if (!$quote->canBeAccepted()) {
            throw new \Exception('Quote cannot be accepted');
        }

        DB::transaction(function () use ($rfq, $quote) {
            $rfq->accept($quote);

            Log::info('Quote accepted', [
                'quote_id' => $quote->id,
                'rfq_id' => $rfq->id,
            ]);
        });
    }

    /**
     * Reject quote (vendor function)
     */
    public function rejectQuote(
        Rfq $rfq,
        RfqQuote $quote,
        User $vendor,
        ?string $reason = null
    ): void {
        if ($rfq->vendor_id !== $vendor->id) {
            throw new \Exception('Unauthorized');
        }

        if (!$quote->canBeRejected()) {
            throw new \Exception('Quote cannot be rejected');
        }

        DB::transaction(function () use ($rfq, $quote, $reason) {
            $rfq->reject($quote, $reason);

            Log::info('Quote rejected', [
                'quote_id' => $quote->id,
                'rfq_id' => $rfq->id,
                'reason' => $reason,
            ]);
        });
    }

    /**
     * Add negotiation message
     */
    public function addNegotiation(
        Rfq $rfq,
        User $user,
        string $message,
        bool $isCounterOffer = false,
        ?float $proposedPrice = null,
        ?string $proposedTerms = null,
        ?int $proposedDeliveryDays = null
    ): RfqNegotiation {
        $negotiation = $rfq->negotiations()->create([
            'user_id' => $user->id,
            'message' => $message,
            'is_counter_offer' => $isCounterOffer,
            'proposed_price' => $proposedPrice,
            'proposed_terms' => $proposedTerms,
            'proposed_delivery_days' => $proposedDeliveryDays,
        ]);

        Log::info('Negotiation message added', [
            'negotiation_id' => $negotiation->id,
            'rfq_id' => $rfq->id,
            'is_counter_offer' => $isCounterOffer,
        ]);

        return $negotiation;
    }

    /**
     * Get unread negotiations for user
     */
    public function getUnreadNegotiations(Rfq $rfq, User $user)
    {
        return $rfq->negotiations()
            ->byOthers($user->id)
            ->unread()
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Mark negotiations as read
     */
    public function markNegotiationsAsRead(Rfq $rfq, User $user): int
    {
        $negotiations = $rfq->negotiations()
            ->byOthers($user->id)
            ->unread()
            ->get();

        foreach ($negotiations as $negotiation) {
            $negotiation->markAsRead();
        }

        return $negotiations->count();
    }

    /**
     * Convert accepted RFQ to order
     */
    public function convertToOrder(Rfq $rfq): Order
    {
        if ($rfq->status !== 'accepted') {
            throw new \Exception('Only accepted RFQs can be converted to orders');
        }

        return DB::transaction(function () use ($rfq) {
            $order = $rfq->convertToOrder();

            Log::info('RFQ converted to order', [
                'rfq_id' => $rfq->id,
                'order_id' => $order->id,
            ]);

            return $order;
        });
    }

    /**
     * Cancel RFQ
     */
    public function cancelRfq(Rfq $rfq, User $user, ?string $reason = null): void
    {
        if (!$rfq->canBeCancelled()) {
            throw new \Exception('RFQ cannot be cancelled in current status');
        }

        // Only vendor can cancel their own RFQ
        if ($rfq->vendor_id !== $user->id && !in_array($user->role, ['admin', 'super_admin'])) {
            throw new \Exception('Unauthorized');
        }

        $rfq->update(['status' => 'rejected']);

        // Add cancellation note
        if ($reason) {
            $this->addNegotiation($rfq, $user, "RFQ cancelled: " . $reason);
        }

        Log::info('RFQ cancelled', [
            'rfq_id' => $rfq->id,
            'cancelled_by' => $user->id,
        ]);
    }

    /**
     * Mark expired RFQs
     */
    public function markExpiredRfqs(): int
    {
        $count = 0;

        Rfq::expired()->chunk(100, function ($rfqs) use (&$count) {
            foreach ($rfqs as $rfq) {
                $rfq->update(['status' => 'expired']);
                $count++;
            }
        });

        // Also mark expired quotes
        RfqQuote::expired()->chunk(100, function ($quotes) {
            foreach ($quotes as $quote) {
                $quote->update(['status' => 'expired']);
            }
        });

        Log::info('Expired RFQs marked', ['count' => $count]);

        return $count;
    }

    /**
     * Get RFQ statistics for vendor
     */
    public function getVendorStats(User $vendor): array
    {
        $rfqs = Rfq::forVendor($vendor->id);

        return [
            'total' => $rfqs->count(),
            'draft' => $rfqs->draft()->count(),
            'submitted' => $rfqs->submitted()->count(),
            'quoted' => $rfqs->quoted()->count(),
            'negotiating' => $rfqs->negotiating()->count(),
            'accepted' => $rfqs->accepted()->count(),
            'active' => $rfqs->active()->count(),
        ];
    }

    /**
     * Get RFQ statistics for admin
     */
    public function getAdminStats(): array
    {
        return [
            'total' => Rfq::count(),
            'pending_review' => Rfq::submitted()->count(),
            'quoted' => Rfq::quoted()->count(),
            'negotiating' => Rfq::negotiating()->count(),
            'accepted' => Rfq::accepted()->count(),
            'active' => Rfq::active()->count(),
            'high_priority' => Rfq::where('priority', 'urgent')->active()->count(),
        ];
    }
}
