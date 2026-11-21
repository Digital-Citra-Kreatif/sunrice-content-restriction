<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** An entry's or term's own access, set in its editor's Access card (none: its collection's or taxonomy's). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_restrictions', function (Blueprint $table): void {
            $table->id();
            $table->string('restrictable_type', 16);
            $table->unsignedBigInteger('restrictable_id');
            $table->string('access', 16);
            $table->json('roles')->nullable();
            $table->timestamps();
            $table->unique(['restrictable_type', 'restrictable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_restrictions');
    }
};
