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
        Schema::table('customers', function (Blueprint $table) {

            $table->string('full_name');
            $table->string('phone')->unique();
            $table->string('email')->nullable();

            $table->string('city')->nullable();
            $table->string('country')->default('Saudi Arabia');

            $table->string('service')->nullable();
            $table->string('source')->nullable();

            $table->decimal('price', 10, 2)->default(0);

            $table->enum('status', [
                'new',
                'contacted',
                'working',
                'completed',
                'cancelled'
            ])->default('new');

            $table->text('notes')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {

            $table->dropColumn([
                'full_name',
                'phone',
                'email',
                'city',
                'country',
                'service',
                'source',
                'price',
                'status',
                'notes'
            ]);

        });
    }
};

