<?php

declare(strict_types=1);

namespace App\Services;

class OpenApiSpecService
{
    /**
     * Generate OpenAPI 3.1.0 specification synchronized with contracts/api.ts.
     *
     * @return array<string, mixed>
     */
    public function generate(): array
    {
        return [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'RESET Store REST API',
                'version' => '1.0.0',
                'description' => 'Contract-first API for RESET Store (Unified Website Project backend serving Blade, Filament v3 Admin, and Mobile Project Flutter Client). Monetary *_cents fields store base Cambodian Riel (KHR ៛) integers.',
            ],
            'servers' => [
                [
                    'url' => rtrim((string) config('app.url', 'http://127.0.0.1:8000'), '/'),
                    'description' => 'Unified Laravel Backend Server',
                ],
            ],
            'tags' => [
                ['name' => 'Bootstrap & Home'],
                ['name' => 'Catalog & Search'],
                ['name' => 'Reviews & Wishlist'],
                ['name' => 'Cart & Discounts'],
                ['name' => 'Checkout & Bakong KHQR'],
                ['name' => 'Authentication'],
                ['name' => 'Customer Account'],
                ['name' => 'Pages & Support'],
            ],
            'components' => [
                'securitySchemes' => [
                    'BearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'description' => 'SHA-256 hashed API Bearer token issued by /api/v1/auth/login or /api/v1/auth/register.',
                    ],
                    'CartToken' => [
                        'type' => 'apiKey',
                        'in' => 'header',
                        'name' => 'X-Cart-Token',
                        'description' => 'Stateless guest cart token mapped to carts.session_id.',
                    ],
                    'OrderToken' => [
                        'type' => 'apiKey',
                        'in' => 'header',
                        'name' => 'X-Order-Token',
                        'description' => 'Stateless HMAC-SHA256 guest order token issued by POST /api/v1/checkout.',
                    ],
                ],
                'parameters' => [
                    'LocaleHeader' => [
                        'name' => 'X-Locale',
                        'in' => 'header',
                        'required' => false,
                        'schema' => ['type' => 'string', 'enum' => ['en', 'km'], 'default' => 'en'],
                    ],
                    'CurrencyHeader' => [
                        'name' => 'X-Currency',
                        'in' => 'header',
                        'required' => false,
                        'schema' => ['type' => 'string', 'enum' => ['KHR', 'USD'], 'default' => 'KHR'],
                    ],
                ],
                'schemas' => [
                    'ApiErrorResponse' => [
                        'type' => 'object',
                        'required' => ['success', 'message'],
                        'properties' => [
                            'success' => ['type' => 'boolean', 'enum' => [false]],
                            'message' => ['type' => 'string'],
                            'code' => ['type' => 'string'],
                            'errors' => [
                                'type' => 'object',
                                'additionalProperties' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                ],
                            ],
                        ],
                    ],
                    'UserDto' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'name' => ['type' => 'string'],
                            'email' => ['type' => 'string', 'format' => 'email'],
                            'phone' => ['type' => ['string', 'null']],
                            'role' => ['type' => 'string', 'enum' => ['admin', 'staff', 'customer']],
                            'email_verified_at' => ['type' => ['string', 'null'], 'format' => 'date-time'],
                            'created_at' => ['type' => 'string', 'format' => 'date-time'],
                        ],
                    ],
                    'CartPayloadDto' => [
                        'type' => 'object',
                        'properties' => [
                            'cart_token' => ['type' => ['string', 'null']],
                            'currency' => ['type' => 'string', 'enum' => ['KHR', 'USD']],
                            'exchange_rate' => ['type' => 'integer'],
                            'province' => ['type' => 'string'],
                            'shipping_method' => ['type' => 'string', 'enum' => ['standard', 'express']],
                            'subtotal_cents' => ['type' => 'integer'],
                            'discount_cents' => ['type' => 'integer'],
                            'shipping_fee_cents' => ['type' => 'integer'],
                            'total_cents' => ['type' => 'integer'],
                            'item_count' => ['type' => 'integer'],
                            'items' => ['type' => 'array', 'items' => ['type' => 'object']],
                        ],
                    ],
                    'PaymentDto' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'order_id' => ['type' => 'integer'],
                            'order_number' => ['type' => 'string'],
                            'provider' => ['type' => 'string', 'enum' => ['bakong_khqr']],
                            'md5' => ['type' => 'string'],
                            'qr_string' => ['type' => 'string'],
                            'account_id' => ['type' => 'string'],
                            'merchant_name' => ['type' => 'string'],
                            'amount' => ['type' => 'number'],
                            'currency' => ['type' => 'string', 'enum' => ['KHR', 'USD']],
                            'status' => ['type' => 'string', 'enum' => ['pending', 'paid', 'expired', 'failed']],
                            'expires_at' => ['type' => 'string', 'format' => 'date-time'],
                            'time_remaining_seconds' => ['type' => 'integer'],
                            'bakong_mock' => ['type' => 'boolean'],
                            'deeplink_url' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
            'paths' => $this->buildPaths(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPaths(): array
    {
        $contextParams = [
            ['$ref' => '#/components/parameters/LocaleHeader'],
            ['$ref' => '#/components/parameters/CurrencyHeader'],
        ];

        return [
            '/api/v1/bootstrap' => [
                'get' => $this->op('Bootstrap & Home', 'Store bootstrap settings, categories, size guide & optional cart', $contextParams, [['BearerAuth' => []], ['CartToken' => []], []]),
            ],
            '/api/v1/home' => [
                'get' => $this->op('Bootstrap & Home', 'Home storefront collections and product showcases', $contextParams),
            ],
            '/api/v1/products' => [
                'get' => $this->op('Catalog & Search', 'Filter, search, sort & paginate published products', array_merge($contextParams, [
                    $this->queryParam('q', 'string'),
                    $this->queryParam('category', 'string'),
                    $this->queryParam('size', 'string'),
                    $this->queryParam('color', 'string'),
                    $this->queryParam('min_price', 'number'),
                    $this->queryParam('max_price', 'number'),
                    $this->queryParam('in_stock', 'boolean'),
                    $this->queryParam('sort', 'string', ['featured', 'newest', 'price_low', 'price_high']),
                    $this->queryParam('page', 'integer'),
                    $this->queryParam('per_page', 'integer'),
                ])),
            ],
            '/api/v1/search/suggest' => [
                'get' => $this->op('Catalog & Search', 'Predictive multi-term search autocomplete suggestions', array_merge($contextParams, [
                    $this->queryParam('q', 'string'),
                ])),
            ],
            '/api/v1/products/{slug}' => [
                'get' => $this->op('Catalog & Search', 'Product detail with variants, 5 localized tabs, reviews & related products', array_merge($contextParams, [
                    $this->pathParam('slug'),
                ]), [['BearerAuth' => []], []]),
            ],
            '/api/v1/products/{slug}/reviews' => [
                'post' => $this->op('Reviews & Wishlist', 'Create or update authenticated customer review', array_merge($contextParams, [
                    $this->pathParam('slug'),
                ]), [['BearerAuth' => []]], $this->jsonBody(['rating' => ['type' => 'integer'], 'title' => ['type' => 'string'], 'body' => ['type' => 'string']], ['rating', 'body']), 201),
            ],
            '/api/v1/wishlist' => [
                'get' => $this->op('Reviews & Wishlist', 'List authenticated user wishlist products', $contextParams, [['BearerAuth' => []]]),
            ],
            '/api/v1/wishlist/toggle' => [
                'post' => $this->op('Reviews & Wishlist', 'Toggle a product in authenticated user wishlist', $contextParams, [['BearerAuth' => []]], $this->jsonBody(['product_id' => ['type' => 'integer']], ['product_id'])),
            ],
            '/api/v1/cart' => [
                'get' => $this->op('Cart & Discounts', 'Fetch active cart and shipping/discount totals', array_merge($contextParams, [
                    $this->queryParam('province', 'string'),
                    $this->queryParam('shipping_method', 'string', ['standard', 'express']),
                ]), [['BearerAuth' => []], ['CartToken' => []]]),
            ],
            '/api/v1/cart/items' => [
                'post' => $this->op('Cart & Discounts', 'Add product variant to cart', $contextParams, [['BearerAuth' => []], ['CartToken' => []]], $this->jsonBody([
                    'product_variant_id' => ['type' => 'integer'],
                    'quantity' => ['type' => 'integer'],
                    'province' => ['type' => 'string'],
                    'shipping_method' => ['type' => 'string', 'enum' => ['standard', 'express']],
                ], ['product_variant_id', 'quantity'])),
            ],
            '/api/v1/cart/items/{item}' => [
                'patch' => $this->op('Cart & Discounts', 'Update cart item quantity (0 removes item)', array_merge($contextParams, [
                    $this->pathParam('item', 'integer'),
                ]), [['BearerAuth' => []], ['CartToken' => []]], $this->jsonBody([
                    'quantity' => ['type' => 'integer'],
                    'province' => ['type' => 'string'],
                    'shipping_method' => ['type' => 'string', 'enum' => ['standard', 'express']],
                ], ['quantity'])),
                'delete' => $this->op('Cart & Discounts', 'Remove item from cart', array_merge($contextParams, [
                    $this->pathParam('item', 'integer'),
                    $this->queryParam('province', 'string'),
                    $this->queryParam('shipping_method', 'string', ['standard', 'express']),
                ]), [['BearerAuth' => []], ['CartToken' => []]]),
            ],
            '/api/v1/cart/discount' => [
                'post' => $this->op('Cart & Discounts', 'Apply promo discount code to cart', $contextParams, [['BearerAuth' => []], ['CartToken' => []]], $this->jsonBody([
                    'code' => ['type' => 'string'],
                    'province' => ['type' => 'string'],
                    'shipping_method' => ['type' => 'string', 'enum' => ['standard', 'express']],
                ], ['code'])),
                'delete' => $this->op('Cart & Discounts', 'Remove active discount code from cart', array_merge($contextParams, [
                    $this->queryParam('province', 'string'),
                    $this->queryParam('shipping_method', 'string', ['standard', 'express']),
                ]), [['BearerAuth' => []], ['CartToken' => []]]),
            ],
            '/api/v1/checkout' => [
                'post' => $this->op('Checkout & Bakong KHQR', 'Create order, reserve inventory & generate dynamic Bakong KHQR', $contextParams, [['BearerAuth' => []], ['CartToken' => []]], $this->jsonBody([
                    'name' => ['type' => 'string'],
                    'email' => ['type' => 'string', 'format' => 'email'],
                    'phone' => ['type' => 'string'],
                    'province' => ['type' => 'string'],
                    'district' => ['type' => 'string'],
                    'commune' => ['type' => 'string'],
                    'street_address' => ['type' => 'string'],
                    'shipping_method' => ['type' => 'string', 'enum' => ['standard', 'express']],
                    'notes' => ['type' => 'string'],
                ], ['email', 'phone', 'province', 'street_address', 'shipping_method']), 201),
            ],
            '/api/v1/orders/{orderNumber}/payment' => [
                'get' => $this->op('Checkout & Bakong KHQR', 'Get order and active Bakong KHQR payment payload', array_merge($contextParams, [
                    $this->pathParam('orderNumber'),
                ]), [['BearerAuth' => []], ['OrderToken' => []]]),
            ],
            '/api/v1/payments/{payment}/status' => [
                'get' => $this->op('Checkout & Bakong KHQR', 'Poll Bakong KHQR payment status and auto-finalize paid orders', array_merge($contextParams, [
                    $this->pathParam('payment', 'integer'),
                ]), [['BearerAuth' => []], ['OrderToken' => []]]),
            ],
            '/api/v1/orders/{orderNumber}/regenerate-qr' => [
                'post' => $this->op('Checkout & Bakong KHQR', 'Regenerate fresh Bakong KHQR for expired pending order', array_merge($contextParams, [
                    $this->pathParam('orderNumber'),
                ]), [['BearerAuth' => []], ['OrderToken' => []]]),
            ],
            '/api/v1/payments/{payment}/simulate-pay' => [
                'post' => $this->op('Checkout & Bakong KHQR', 'Simulate instant payment (strictly non-production BAKONG_MOCK=true only)', array_merge($contextParams, [
                    $this->pathParam('payment', 'integer'),
                ]), [['BearerAuth' => []], ['OrderToken' => []]]),
            ],
            '/api/v1/orders/{orderNumber}/invoice' => [
                'get' => $this->op('Checkout & Bakong KHQR', 'Fetch structured Tax Invoice JSON for an order', array_merge($contextParams, [
                    $this->pathParam('orderNumber'),
                ]), [['BearerAuth' => []], ['OrderToken' => []]]),
            ],
            '/api/v1/auth/register' => [
                'post' => $this->op('Authentication', 'Register customer account, merge X-Cart-Token & issue Bearer token', $contextParams, [['CartToken' => []], []], $this->jsonBody([
                    'name' => ['type' => 'string'],
                    'email' => ['type' => 'string', 'format' => 'email'],
                    'phone' => ['type' => 'string'],
                    'password' => ['type' => 'string'],
                    'password_confirmation' => ['type' => 'string'],
                    'device_name' => ['type' => 'string'],
                ], ['name', 'email', 'phone', 'password', 'password_confirmation']), 201),
            ],
            '/api/v1/auth/login' => [
                'post' => $this->op('Authentication', 'Login customer, merge X-Cart-Token & issue Bearer token', $contextParams, [['CartToken' => []], []], $this->jsonBody([
                    'email' => ['type' => 'string', 'format' => 'email'],
                    'password' => ['type' => 'string'],
                    'device_name' => ['type' => 'string'],
                ], ['email', 'password'])),
            ],
            '/api/v1/auth/logout' => [
                'post' => $this->op('Authentication', 'Revoke current Bearer API token', $contextParams, [['BearerAuth' => []]]),
            ],
            '/api/v1/auth/forgot-password' => [
                'post' => $this->op('Authentication', 'Send password reset email link', $contextParams, [], $this->jsonBody([
                    'email' => ['type' => 'string', 'format' => 'email'],
                ], ['email'])),
            ],
            '/api/v1/auth/me' => [
                'get' => $this->op('Authentication', 'Fetch current authenticated user profile', $contextParams, [['BearerAuth' => []]]),
            ],
            '/api/v1/auth/password' => [
                'put' => $this->op('Authentication', 'Update authenticated user password', $contextParams, [['BearerAuth' => []]], $this->jsonBody([
                    'current_password' => ['type' => 'string'],
                    'password' => ['type' => 'string'],
                    'password_confirmation' => ['type' => 'string'],
                ], ['current_password', 'password', 'password_confirmation'])),
            ],
            '/api/v1/account/profile' => [
                'get' => $this->op('Customer Account', 'Fetch customer profile and default shipping address', $contextParams, [['BearerAuth' => []]]),
                'patch' => $this->op('Customer Account', 'Update customer name and phone', $contextParams, [['BearerAuth' => []]], $this->jsonBody([
                    'name' => ['type' => 'string'],
                    'phone' => ['type' => 'string'],
                ], ['name'])),
            ],
            '/api/v1/account/orders' => [
                'get' => $this->op('Customer Account', 'Paginated authenticated customer orders', array_merge($contextParams, [
                    $this->queryParam('page', 'integer'),
                    $this->queryParam('per_page', 'integer'),
                ]), [['BearerAuth' => []]]),
            ],
            '/api/v1/account/orders/{orderNumber}' => [
                'get' => $this->op('Customer Account', 'Fetch customer order detail with 5-step timeline & order_access_token', array_merge($contextParams, [
                    $this->pathParam('orderNumber'),
                ]), [['BearerAuth' => []]]),
            ],
            '/api/v1/account/addresses' => [
                'get' => $this->op('Customer Account', 'List saved delivery addresses and 25 Cambodian provinces', $contextParams, [['BearerAuth' => []]]),
                'post' => $this->op('Customer Account', 'Create saved delivery address', $contextParams, [['BearerAuth' => []]], $this->jsonBody([
                    'name' => ['type' => 'string'],
                    'phone' => ['type' => 'string'],
                    'province' => ['type' => 'string'],
                    'district' => ['type' => 'string'],
                    'commune' => ['type' => 'string'],
                    'street_address' => ['type' => 'string'],
                    'is_default' => ['type' => 'boolean'],
                ], ['phone', 'province', 'street_address']), 201),
            ],
            '/api/v1/account/addresses/{address}' => [
                'put' => $this->op('Customer Account', 'Update saved delivery address', array_merge($contextParams, [
                    $this->pathParam('address', 'integer'),
                ]), [['BearerAuth' => []]], $this->jsonBody([
                    'name' => ['type' => 'string'],
                    'phone' => ['type' => 'string'],
                    'province' => ['type' => 'string'],
                    'street_address' => ['type' => 'string'],
                    'is_default' => ['type' => 'boolean'],
                ], ['phone', 'province', 'street_address'])),
                'delete' => $this->op('Customer Account', 'Delete saved delivery address', array_merge($contextParams, [
                    $this->pathParam('address', 'integer'),
                ]), [['BearerAuth' => []]]),
            ],
            '/api/v1/pages/{slug}' => [
                'get' => $this->op('Pages & Support', 'Fetch localized structured brand or policy page', array_merge($contextParams, [
                    $this->pathParam('slug'),
                ])),
            ],
            '/api/v1/contact' => [
                'post' => $this->op('Pages & Support', 'Submit customer support contact message', $contextParams, [], $this->jsonBody([
                    'name' => ['type' => 'string'],
                    'email' => ['type' => 'string', 'format' => 'email'],
                    'subject' => ['type' => 'string'],
                    'message' => ['type' => 'string'],
                ], ['name', 'email', 'message']), 201),
            ],
            '/api/v1/newsletter' => [
                'post' => $this->op('Pages & Support', 'Subscribe email to RESET newsletter', $contextParams, [], $this->jsonBody([
                    'email' => ['type' => 'string', 'format' => 'email'],
                ], ['email'])),
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $parameters
     * @param  array<int, array<string, mixed>>  $security
     * @param  array<string, mixed>|null  $requestBody
     * @return array<string, mixed>
     */
    private function op(
        string $tag,
        string $summary,
        array $parameters = [],
        array $security = [],
        ?array $requestBody = null,
        int $successStatus = 200
    ): array {
        $operation = [
            'tags' => [$tag],
            'summary' => $summary,
            'parameters' => $parameters,
            'responses' => [
                (string) $successStatus => [
                    'description' => 'Successful response',
                ],
                '422' => [
                    'description' => 'Validation or domain rule error',
                    'content' => [
                        'application/json' => [
                            'schema' => ['$ref' => '#/components/schemas/ApiErrorResponse'],
                        ],
                    ],
                ],
            ],
        ];

        if ($security !== []) {
            $operation['security'] = $security;
        }

        if ($requestBody !== null) {
            $operation['requestBody'] = $requestBody;
        }

        return $operation;
    }

    /**
     * @return array<string, mixed>
     */
    private function pathParam(string $name, string $type = 'string'): array
    {
        return [
            'name' => $name,
            'in' => 'path',
            'required' => true,
            'schema' => ['type' => $type],
        ];
    }

    /**
     * @param  array<int, string>|null  $enum
     * @return array<string, mixed>
     */
    private function queryParam(string $name, string $type, ?array $enum = null): array
    {
        $schema = ['type' => $type];
        if ($enum !== null) {
            $schema['enum'] = $enum;
        }

        return [
            'name' => $name,
            'in' => 'query',
            'required' => false,
            'schema' => $schema,
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $properties
     * @param  array<int, string>  $required
     * @return array<string, mixed>
     */
    private function jsonBody(array $properties, array $required = []): array
    {
        return [
            'required' => true,
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'required' => $required,
                        'properties' => $properties,
                    ],
                ],
            ],
        ];
    }
}
