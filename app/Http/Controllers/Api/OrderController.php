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
