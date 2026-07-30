<?php

use App\Models\School;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * The demo school used to live on the root domain, which collides with
     * the marketing landing page route. Move it to the "school" subdomain.
     */
    public function up(): void
    {
        $rootDomain = parse_url(config('app.url') ?? 'http://localhost')['host'] ?? 'localhost';

        School::where('domain', $rootDomain)
            ->where('name', 'School Demo')
            ->update(['domain' => "school.{$rootDomain}"]);
    }

    public function down(): void
    {
        $rootDomain = parse_url(config('app.url') ?? 'http://localhost')['host'] ?? 'localhost';

        School::where('domain', "school.{$rootDomain}")
            ->where('name', 'School Demo')
            ->update(['domain' => $rootDomain]);
    }
};
