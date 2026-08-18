<?php

namespace App\Console\Commands;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Illuminate\Console\Command;

class ConfigureR2Cors extends Command
{
    protected $signature = 'r2:configure-cors {--origin=* : Additional allowed origins besides APP_URL}';

    protected $description = 'Configure CORS rules on the R2 bucket so browsers can upload directly via presigned URLs';

    public function handle(): int
    {
        $accountId = config('services.r2.account_id');
        $bucket = config('services.r2.bucket');

        if (! $accountId || ! $bucket) {
            $this->error('R2 is not configured. Check services.r2.account_id and services.r2.bucket.');

            return self::FAILURE;
        }

        $origins = array_values(array_unique(array_filter([
            config('app.url'),
            ...$this->option('origin'),
        ])));

        // Allow both http and https for each origin so CORS doesn't silently
        // break when the dev/prod environment's scheme doesn't match APP_URL's.
        $origins = array_values(array_unique(array_merge(
            $origins,
            array_map(fn (string $origin) => str_starts_with($origin, 'https://')
                ? 'http://'.substr($origin, 8)
                : (str_starts_with($origin, 'http://') ? 'https://'.substr($origin, 7) : $origin), $origins)
        )));

        if (empty($origins)) {
            $this->error('No origins to allow. Set APP_URL or pass --origin=https://example.com');

            return self::FAILURE;
        }

        $s3Client = new S3Client([
            'version' => 'latest',
            'region' => 'auto',
            'endpoint' => "https://{$accountId}.r2.cloudflarestorage.com",
            'credentials' => [
                'key' => config('services.r2.access_key_id'),
                'secret' => config('services.r2.secret_access_key'),
            ],
            'use_path_style_endpoint' => true,
        ]);

        try {
            $s3Client->putBucketCors([
                'Bucket' => $bucket,
                'CORSConfiguration' => [
                    'CORSRules' => [
                        [
                            'AllowedOrigins' => $origins,
                            'AllowedMethods' => ['GET', 'PUT', 'HEAD'],
                            'AllowedHeaders' => ['*'],
                            'ExposeHeaders' => ['ETag'],
                            'MaxAgeSeconds' => 3600,
                        ],
                    ],
                ],
            ]);
        } catch (AwsException $e) {
            $this->error("Failed to set CORS policy: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info("CORS configured on bucket [{$bucket}] for origins:");
        foreach ($origins as $origin) {
            $this->line("  - {$origin}");
        }

        return self::SUCCESS;
    }
}
