<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    use ApiResponse;

    /**
     * @OA\Get(
     *     path="/api/products",
     *     summary="Get all active products",
     *     tags={"Products"},
     *     security={
     *         {"apiKey": {}},
     *         {"secretKey": {}}
     *     },
     *
     *     @OA\Response(
     *         response=200,
     *         description="Products retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Products retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="products", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Laptop ASUS ROG"),
     *                     @OA\Property(property="description", type="string", example="Gaming laptop dengan processor Intel i7"),
     *                     @OA\Property(property="price", type="number", format="float", example=15000000.00),
     *                     @OA\Property(property="stock", type="integer", example=10),
     *                     @OA\Property(property="is_active", type="boolean", example=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 ))
     *             )
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/products/{id}",
     *     summary="Get product details",
     *     tags={"Products"},
     *     security={
     *         {"apiKey": {}},
     *         {"secretKey": {}}
     *     },
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Product ID",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Product retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="product", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Laptop ASUS ROG"),
     *                     @OA\Property(property="description", type="string", example="Gaming laptop dengan processor Intel i7"),
     *                     @OA\Property(property="price", type="number", format="float", example=15000000.00),
     *                     @OA\Property(property="stock", type="integer", example=10),
     *                     @OA\Property(property="is_active", type="boolean", example=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Product not found")
     *         )
     *     )
     * )
     */
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
