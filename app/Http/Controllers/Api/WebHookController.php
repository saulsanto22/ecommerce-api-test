<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentWebhookRequest;
use App\Models\Payment;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    use ApiResponse;

    /**
     * @OA\Post(
     *     path="/api/webhooks/payment",
     *     summary="Process payment webhook from Xendit",
     *     tags={"Webhooks"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"id","external_id","status","amount"},
     *
     *             @OA\Property(property="id", type="string", example="inv_test_123"),
     *             @OA\Property(property="external_id", type="string", example="ORD-1705312800-1234"),
     *             @OA\Property(property="status", type="string", enum={"PENDING", "PAID", "EXPIRED", "FAILED"}, example="PAID"),
     *             @OA\Property(property="amount", type="number", format="float", example=15000000.00),
     *             @OA\Property(property="payer_email", type="string", format="email", example="customer@example.com"),
     *             @OA\Property(property="payment_method", type="string", example="BANK_TRANSFER"),
     *             @OA\Property(property="paid_at", type="string", format="date-time", example="2024-01-15T10:30:00.000Z")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Webhook processed successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Webhook processed successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="processed", type="boolean", example=true),
     *                 @OA\Property(property="payment_id", type="string", example="inv_test_123"),
     *                 @OA\Property(property="status", type="string", example="PAID"),
     *                 @OA\Property(property="order_status", type="string", example="paid")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Invalid webhook data"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment not found"
     *     )
     * )
     */
    public function handlePaymentWebhook(PaymentWebhookRequest $request): JsonResponse
    {
        Log::info('Payment Webhook Received', $request->all());

        try {
            DB::beginTransaction();

            $validated = $request->validated();

            // cari payment berdasarkan payment_id dari Xendit
            $payment = Payment::where('payment_id', $validated['id'])->first();
            // dd($payment);

            if (! $payment) {
                Log::error(' Payment not found for webhook', ['payment_id' => $validated['id']]);
                DB::rollBack();

                return $this->error('Payment not found', 404);
            }

            // Update payment status
            $payment->update([
                'status' => $validated['status'],
                'paid_at' => $validated['status'] === 'PAID' ? now() : null,
                'payment_method' => $validated['payment_method'] ?? null,
            ]);

            
            if ($validated['status'] === 'PAID') {
                $payment->order->update(['status' => 'paid']);

                Log::info(' Payment successful, order updated', [
                    'order_number' => $payment->order->order_number,
                    'payment_id' => $validated['id'],
                    'amount' => $validated['amount'],
                ]);
            } elseif ($validated['status'] === 'EXPIRED') {
              
                $payment->order->update(['status' => 'cancelled']);
                Log::info(' Payment expired, order cancelled', [
                    'order_number' => $payment->order->order_number,
                ]);
            }

            DB::commit();

            Log::info('🎉 Webhook processed successfully', [
                'payment_id' => $validated['id'],
                'status' => $validated['status'],
                'order_status' => $payment->order->status,
            ]);

            return $this->success([
                'processed' => true,
                'payment_id' => $validated['id'],
                'status' => $validated['status'],
                'order_status' => $payment->order->status,
            ], 'Webhook processed successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('💥 Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'webhook_data' => $request->all(),
            ]);

            return $this->error('Webhook processing failed: '.$e->getMessage(), 500);
        }
    }
}
