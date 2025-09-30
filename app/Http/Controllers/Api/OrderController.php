<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Models\Order;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    use ApiResponse;

    /**
     * @OA\Post(
     *     path="/api/checkout",
     *     summary="Create new order",
     *     tags={"Orders"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"items","shipping_address"},
     *
     *             @OA\Property(property="items", type="array", @OA\Items(
     *                 @OA\Property(property="product_id", type="integer", example=1),
     *                 @OA\Property(property="quantity", type="integer", example=2, minimum=1, maximum=10)
     *             )),
     *             @OA\Property(property="shipping_address", type="string", example="Jl. Contoh Alamat No. 123, Jakarta Selatan, 12345", minLength=10, maxLength=500)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Order created successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order berhasil dibuat"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="order", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="user_id", type="integer", example=1),
     *                     @OA\Property(property="order_number", type="string", example="ORD-1705312800-1234"),
     *                     @OA\Property(property="total_amount", type="number", format="float", example=30000000.00),
     *                     @OA\Property(property="status", type="string", example="pending"),
     *                     @OA\Property(property="shipping_address", type="string"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="items", type="array", @OA\Items(
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="product_id", type="integer"),
     *                         @OA\Property(property="quantity", type="integer"),
     *                         @OA\Property(property="price", type="number", format="float"),
     *                         @OA\Property(property="subtotal", type="number", format="float"),
     *                         @OA\Property(property="product", type="object")
     *                     ))
     *                 ),
     *                 @OA\Property(property="payment_url", type="string", example="https://your-app.railway.app/api/payments/create/1")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Checkout failed"
     *     )
     * )
     */
    public function checkout(CheckoutRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            DB::beginTransaction();

            $totalAmount = 0;
            $orderItems = [];

            foreach ($validated['items'] as $item) {
                $product = Product::active()->find($item['product_id']);

                if (! $product) {
                    throw new \Exception("Product not found: {$item['product_id']}");
                }

                if ($product->stock < $item['quantity']) {
                    throw new \Exception("Insufficient stock for product: {$product->name}. Available: {$product->stock}, Requested: {$item['quantity']}");
                }

                $subtotal = $product->price * $item['quantity'];
                $totalAmount += $subtotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                    'subtotal' => $subtotal,
                ];

                $product->decrement('stock', $item['quantity']);
            }

            $order = Order::create([
                'user_id' => $request->user()->id,
                'order_number' => 'ORD-'.time().'-'.rand(1000, 9999),
                'total_amount' => $totalAmount,
                'status' => Order::STATUS_PENDING,
                'shipping_address' => $validated['shipping_address'],
            ]);

            $order->items()->createMany($orderItems);

            DB::commit();

            Log::info('Order created successfully', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'total_amount' => $totalAmount,
            ]);

            return $this->created([
                'order' => $order->load('items.product'),
                'payment_url' => url("/api/payments/create/{$order->id}"),
            ], 'Order berhasil dibuat');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Checkout failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error('Checkout gagal: '.$e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/orders",
     *     summary="Get user's order history",
     *     tags={"Orders"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *
     *     @OA\Response(
     *         response=200,
     *         description="Orders retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Daftar order berhasil diambil"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="orders", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="order_number", type="string"),
     *                     @OA\Property(property="total_amount", type="number", format="float"),
     *                     @OA\Property(property="status", type="string", enum={"pending", "paid", "processing", "shipped", "delivered", "cancelled"}),
     *                     @OA\Property(property="shipping_address", type="string"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="items", type="array", @OA\Items()),
     *                     @OA\Property(property="payment", type="object", nullable=true)
     *                 ))
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $orders = $request->user()
                ->orders()
                ->with(['items.product', 'payment'])
                ->latest()
                ->paginate(10);

            return $this->success([
                'orders' => $orders,
            ], 'Daftar order berhasil diambil');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve orders', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error('Gagal mengambil daftar order: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/orders/{id}",
     *     summary="Get order details",
     *     tags={"Orders"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Order ID",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Order details retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Detail order berhasil diambil"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="order", type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="order_number", type="string"),
     *                     @OA\Property(property="total_amount", type="number", format="float"),
     *                     @OA\Property(property="status", type="string"),
     *                     @OA\Property(property="shipping_address", type="string"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="items", type="array", @OA\Items()),
     *                     @OA\Property(property="payment", type="object", nullable=true)
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Order not found"
     *     )
     * )
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $order = $request->user()
                ->orders()
                ->with(['items.product', 'payment'])
                ->find($id);

            if (! $order) {
                return $this->notFound('Order tidak ditemukan');
            }

            return $this->success([
                'order' => $order,
            ], 'Detail order berhasil diambil');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve order', [
                'user_id' => $request->user()->id,
                'order_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->error('Gagal mengambil detail order: '.$e->getMessage(), 500);
        }
    }
}
