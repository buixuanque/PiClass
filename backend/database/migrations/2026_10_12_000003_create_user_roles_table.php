<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamp('valid_from');
            $table->timestamp('valid_to')->nullable();
            $table->foreignUuid('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('ended_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('end_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
