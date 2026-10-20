<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday'); // 0 = Saturday ... 6 = Friday
            $table->string('start_time', 5);
            $table->string('end_time', 5);
            $table->timestamps();

            $table->index(['staff_id', 'weekday']);
            $table->index(['tenant_id', 'weekday']);
        });

        Schema::create('time_offs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['staff_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_offs');
        Schema::dropIfExists('staff_schedules');
    }
};
