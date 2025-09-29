<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct(private XenditService $xenditService) {}

    public function createXenditInvoice(Order $order): array
    {
        try {
            $invoiceData = [
                'external_id' => $order->order_number,
                'amount' => $order->total_amount,
                'payer_email' => $order->user->email,
                'description' => 'Payment for Order #'.$order->order_number,
                'success_url' => url('/api/payments/success'),
                'failure_url' => url('/api/payments/failed'),
                'currency' => 'IDR',
            ];

            $result = $this->xenditService->createInvoice($invoiceData);

            if (! $result['success']) {
                return $result;
            }

            $xenditResponse = $result['data'];

            // Create payment record
            Payment::create([
                'order_id' => $order->id,
                'external_id' => $xenditResponse['external_id'],
                'payment_id' => $xenditResponse['id'],
                'amount' => $xenditResponse['amount'],
                'status' => $xenditResponse['status'],
                'payment_url' => $xenditResponse['invoice_url'],
                'expiry_date' => $xenditResponse['expiry_date'],
            ]);

            return [
                'success' => true,
                'payment_url' => $xenditResponse['invoice_url'],
            ];

        } catch (\Exception $e) {
            Log::error('Payment Service Error: '.$e->getMessage());

            return [
                'success' => false,
                'error' => 'Failed to create payment invoice',
            ];
        }
    }

    public function handleWebhook(array $webhookData): bool
    {
        try {
            $payment = Payment::where('payment_id', $webhookData['id'])->first();

            if (! $payment) {
                Log::error('Payment not found for webhook: '.$webhookData['id']);

                return false;
            }

            // Update payment status
            $payment->update([
                'status' => $webhookData['status'],
                'paid_at' => $webhookData['status'] === 'PAID' ? now() : null,
            ]);

            // Update order status if paid (sesuai soal: webhook untuk update status pesanan)
            if ($webhookData['status'] === 'PAID') {
                $payment->order->markAsPaid();
                Log::info("Order {$payment->order->order_number} marked as paid via webhook");
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Webhook handling error: '.$e->getMessage());

            return false;
        }
    }
}
