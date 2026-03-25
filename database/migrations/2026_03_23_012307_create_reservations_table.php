<?php

use App\Models\Reservation;
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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->enum('status', array_keys(Reservation::STATUS))->default('pending');
            $table->foreignId('paseador_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('district_id')->constrained();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('client_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
