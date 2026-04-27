<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('darejer_alerts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->string('level', 16);
            // Translatable bag — Spatie\Translatable JSON column shape:
            // {"en":"…","ar":"…","ckb":"…"}
            $table->json('message');
            $table->string('link')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('darejer_alerts');
    }
};
