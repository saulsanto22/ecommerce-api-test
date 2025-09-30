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

    /**
     * @OA\Get(
     *     path="/api/payments/create/{orderId}",
     *     summary="Create payment invoice",
     *     tags={"Payments"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *         name="orderId",
     *         in="path",
     *         required=true,
     *         description="Order ID",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Payment invoice created successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment invoice created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="payment_url", type="string", example="https://checkout.xendit.co/web/abc123")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Order not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized access to order"
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/payments/success",
     *     summary="Payment success callback",
     *     tags={"Payments"},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Payment completed successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment completed successfully")
     *         )
     *     )
     * )
     */
    public function paymentSuccess(): JsonResponse
    {
        return $this->success(null, 'Payment completed successfully');
    }

    /**
     * @OA\Get(
     *     path="/api/payments/failed",
     *     summary="Payment failed callback",
     *     tags={"Payments"},
     *
     *     @OA\Response(
     *         response=400,
     *         description="Payment failed or cancelled",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Payment failed or was cancelled")
     *         )
     *     )
     * )
     */
    public function paymentFailed(): JsonResponse
    {
        return $this->error('Payment failed or was cancelled', 400);
    }
}
