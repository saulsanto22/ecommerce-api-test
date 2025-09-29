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

    public function handlePaymentWebhook(PaymentWebhookRequest $request): JsonResponse
    {
        Log::info('🔔 Payment Webhook Received', $request->all());

        try {
            DB::beginTransaction();

            $validated = $request->validated();

            // Cari payment berdasarkan payment_id dari Xendit
            $payment = Payment::where('payment_id', $validated['id'])->first();
            // dd($payment);

            if (! $payment) {
                Log::error('❌ Payment not found for webhook', ['payment_id' => $validated['id']]);
                DB::rollBack();

                return $this->error('Payment not found', 404);
            }

            // Update payment status
            $payment->update([
                'status' => $validated['status'],
                'paid_at' => $validated['status'] === 'PAID' ? now() : null,
                'payment_method' => $validated['payment_method'] ?? null,
            ]);

            // SESUAI SOAL: Webhook untuk update status pesanan ketika pembayaran berhasil
            if ($validated['status'] === 'PAID') {
                $payment->order->update(['status' => 'paid']);

                Log::info('✅ Payment successful, order updated', [
                    'order_number' => $payment->order->order_number,
                    'payment_id' => $validated['id'],
                    'amount' => $validated['amount'],
                ]);
            } elseif ($validated['status'] === 'EXPIRED') {
                // Optional: Kalau payment expired, bisa update order status
                $payment->order->update(['status' => 'cancelled']);
                Log::info('❌ Payment expired, order cancelled', [
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
