<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * latitude/longitude were strings, so every distance query had to CAST them and the API had to (float) them.
 * DOUBLE keeps every digit we store today (up to 15 decimal places), unlike DECIMAL(10,7).
 */
return new class extends Migration
{
    private const array TABLES = ['mills', 'states', 'counties'];

    private const array COLUMNS = ['latitude', 'longitude'];

    /**
     * A plain decimal number, optionally signed, with optional surrounding spaces.
     * POSIX classes so the same pattern works on MySQL (ICU) and MariaDB (PCRE).
     */
    private const string NUMERIC_PATTERN = '^[[:space:]]*[-+]?([0-9]+([.][0-9]*)?|[.][0-9]+)[[:space:]]*$';

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            foreach (self::COLUMNS as $column) {
                // the type change fails on anything that isn't a number, so blank out what can't be converted
                $cleared = DB::table($table)
                    ->whereNotNull($column)
                    ->whereRaw("{$column} NOT REGEXP ?", [self::NUMERIC_PATTERN])
                    ->update([$column => null]);

                if ($cleared > 0) {
                    Log::warning("make_coordinates_doubles: cleared {$cleared} non-numeric {$table}.{$column} values");
                }

                DB::table($table)->whereNotNull($column)->update([$column => DB::raw("TRIM({$column})")]);
            }

            Schema::table($table, function (Blueprint $blueprint) {
                foreach (self::COLUMNS as $column) {
                    $blueprint->double($column)->nullable()->default(null)->change();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                foreach (self::COLUMNS as $column) {
                    $blueprint->string($column)->nullable()->default(null)->change();
                }
            });
        }
    }
};
