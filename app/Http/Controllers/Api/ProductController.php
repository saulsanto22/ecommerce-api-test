<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        try {
            $products = Product::active()->get();

            return $this->success([
                'products' => $products,
            ], 'Products retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve products: '.$e->getMessage(), 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $product = Product::active()->find($id);

            if (! $product) {
                return $this->notFound('Product not found');
            }

            return $this->success([
                'product' => $product,
            ], 'Product retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve product: '.$e->getMessage(), 500);
        }
    }
}
