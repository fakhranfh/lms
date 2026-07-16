<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tier_features', function (Blueprint $table) {
            $table->string('label')->nullable()->after('feature_key');
        });

        // Populate labels from feature_key
        $features = DB::table('tier_features')->get();
        foreach ($features as $feature) {
            $label = $this->generateLabel($feature->feature_key);
            DB::table('tier_features')
                ->where('id', $feature->id)
                ->update(['label' => $label]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tier_features', function (Blueprint $table) {
            $table->dropColumn('label');
        });
    }

    private function generateLabel(string $featureKey): string
    {
        $label = Str::of($featureKey)
            ->replace('_', ' ')
            ->title();

        // Replace common acronyms with uppercase
        return preg_replace_callback('/\b(Sso|Api)\b/i', function ($matches) {
            return strtoupper($matches[0]);
        }, $label);
    }
};
