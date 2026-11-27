<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OpenApiSpecService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class OpenApiController extends Controller
{
    public function __construct(
        private readonly OpenApiSpecService $openApiSpecService
    ) {}

    public function spec(): JsonResponse
    {
        return response()->json($this->openApiSpecService->generate());
    }

    public function docs(): Response
    {
        $specUrl = url('/api/openapi.json');
        $title = 'RESET Store API Docs — Swagger UI';

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
    <style>
        body { margin: 0; background: #FAF9F5; }
        .topbar { background-color: #111111 !important; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script>
        window.onload = function () {
            window.ui = SwaggerUIBundle({
                url: "{$specUrl}",
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIBundle.SwaggerUIStandalonePreset
                ],
                layout: "BaseLayout"
            });
        };
    </script>
</body>
</html>
HTML;

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
