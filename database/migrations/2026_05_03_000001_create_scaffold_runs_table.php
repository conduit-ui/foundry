<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scaffold_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->json('manifest');
            $table->string('status')->default('queued');
            $table->text('output')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scaffold_runs');
    }
};
