<?php

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
        Schema::table('messages', function (Blueprint $table) {
            //
            $table->foreignId('parent_id')->nullable()->constrained('messages'); // To store the parent message if it's a reply
            $table->timestamp('deleted_at')->nullable(); // To track when a message is deleted
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {

            $table->dropColumn('parent_id');
            $table->dropColumn('deleted_at');
        });
    }
};
