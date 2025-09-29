<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    use ApiResponse;

    public function __construct(private PaymentService $paymentService) {}

    public function createPayment($orderId): JsonResponse
    {
        $order = Order::with('user')->find($orderId);

        if (! $order) {
            return $this->notFound('Order not found');
        }

        // Verify order ownership
        if ($order->user_id !== auth()->id()) {
            return $this->unauthorized('Anda tidak memiliki akses ke order ini');
        }

        $result = $this->paymentService->createXenditInvoice($order);

        if (! $result['success']) {
            return $this->error($result['error'], $result['code'] ?? 400);
        }

        return $this->success([
            'payment_url' => $result['payment_url'],
        ], 'Payment invoice created successfully');
    }

    public function paymentSuccess(): JsonResponse
    {
        return $this->success(null, 'Payment completed successfully');
    }

    public function paymentFailed(): JsonResponse
    {
        return $this->error('Payment failed or was cancelled', 400);
    }
}
