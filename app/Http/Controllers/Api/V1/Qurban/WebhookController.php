<?php

namespace App\Http\Controllers\Api\V1\Qurban;

use App\Http\Controllers\Controller;
use App\Services\Qurban\QurbanTransactionService;
use App\Services\TransactionService;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

#[Group('Qurban - Webhook')]
class WebhookController extends Controller
{
    use ApiResponse;

    public function __construct(
        private QurbanTransactionService $qurbanTransactionService,
        private TransactionService $generalTransactionService
    ) {}

    /**
     * Handle PaKasir webhook callback.
     *
     * This endpoint receives payment notifications from PaKasir.
     * No auth required — validation done via double-check API.
     */
    public function pakasir(Request $request): JsonResponse
    {
        Log::info('PaKasir webhook received', $request->all());

        $payload = $request->only([
            'amount', 'order_id', 'project', 'status', 'payment_method', 'completed_at',
        ]);

        if (empty($payload['order_id']) || empty($payload['status'])) {
            return $this->errorResponse('Invalid webhook payload.', 400);
        }

        try {
            if (Str::startsWith($payload['order_id'], 'QRB-') || Str::startsWith($payload['order_id'], 'TUNAI-')) {
                $this->qurbanTransactionService->handleWebhook($payload);
            } else {
                $this->generalTransactionService->handleWebhook($payload);
            }
        } catch (\Exception $e) {
            Log::error('PaKasir webhook processing error', [
                'order_id' => $payload['order_id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            // Return 200 anyway to prevent PaKasir from retrying
            return $this->successResponse(null, 'Webhook received (with error).');
        }

        return $this->successResponse(null, 'Webhook processed successfully.');
    }
}
