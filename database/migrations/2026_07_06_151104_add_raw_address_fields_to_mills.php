<?php

use App\Enums\PublicationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mills', function (Blueprint $table) {
            // raw address fields hold originally imported addresses before validation and normalization
            $table->string('raw_physical_address')
                ->after('year')
                ->nullable(true)
                ->default(null);

            $table->string('raw_mailing_address')
                ->after('physical_zip')
                ->nullable(true)
                ->default(null);
            
            // let's see if reapplying the Enum to an enum field has the desired result
            // indeed it does, once we also apply the change() method :-D
            // however, this is a serious pain in the ass since we now have to do this for all models that use PublicationStatus
            // Gemini suggested using strings in the DB but casting to PublicationStatus via the model's $casts properties.
            // Let's see if changing to a string preserves the values.
            // It does!!!
            // $table->enum('status', PublicationStatus::cases())
            //     ->default(PublicationStatus::Approved)
            //     ->change();
            $table->string('status')
                ->default(PublicationStatus::Approved)
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mills', function (Blueprint $table) {
            //
            $table->dropColumn([
                'raw_physical_address',
                'raw_mailing_address',
            ]);

            // however, this won't produce a true rollback, but whatever...
            $table->enum('status', PublicationStatus::cases())
                ->default(PublicationStatus::Approved)
                ->change();

        });
    }
};
