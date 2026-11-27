<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\OpenApiSpecService;
use Illuminate\Console\Command;

class ExportOpenApiCommand extends Command
{
    protected $signature = 'app:export-openapi';

    protected $description = 'Export OpenAPI 3.1.0 specification to openapi.json and public/openapi.json';

    public function handle(OpenApiSpecService $openApiSpecService): int
    {
        $json = (string) json_encode(
            $openApiSpecService->generate(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ).PHP_EOL;

        $rootPath = base_path('openapi.json');
        $publicPath = public_path('openapi.json');

        file_put_contents($rootPath, $json);
        file_put_contents($publicPath, $json);

        $this->info("Exported OpenAPI spec to {$rootPath} and {$publicPath}");

        return self::SUCCESS;
    }
}
