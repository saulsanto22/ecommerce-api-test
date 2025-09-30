<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="E-Commerce API",
 *     version="1.0.0",
 *     description="Complete E-Commerce API with Xendit Payment Integration and Automated Webhook Processing",
 *
 *     @OA\Contact(
 *         email="support@ecommerce.com",
 *         name="API Support"
 *     )
 * )
 *
 * @OA\Server(
 *     url="https://ecommerce-api-test-production.up.railway.app",
 *     description="Production Server"
 * )
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Local Development Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="apiKey",
 *     type="apiKey",
 *     in="header",
 *     name="X-ACCESS-KEY",
 *     description="API Access Key"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="secretKey",
 *     type="apiKey",
 *     in="header",
 *     name="X-SECRET-KEY",
 *     description="API Secret Key"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter JWT token"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="API Endpoints for user authentication"
 * )
 * @OA\Tag(
 *     name="Products",
 *     description="API Endpoints for product management"
 * )
 * @OA\Tag(
 *     name="Orders",
 *     description="API Endpoints for order management"
 * )
 * @OA\Tag(
 *     name="Payments",
 *     description="API Endpoints for payment processing"
 * )
 * @OA\Tag(
 *     name="Webhooks",
 *     description="API Endpoints for webhook processing"
 * )
 * @OA\Tag(
 *     name="System",
 *     description="System endpoints"
 * )
 */
abstract class Controller
{
    //
}
