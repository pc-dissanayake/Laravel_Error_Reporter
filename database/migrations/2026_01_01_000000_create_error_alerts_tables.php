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
        $tableName = config('error-alerts.database.table', 'error_alerts');

        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->string('fingerprint', 64)->index();
                $table->string('exception_class');
                $table->text('message');
                $table->string('file');
                $table->unsignedInteger('line');
                $table->string('code')->nullable();
                $table->text('url')->nullable();
                $table->string('method', 10)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->string('user_id')->nullable()->index();
                $table->string('user_email')->nullable();
                $table->string('context_type', 20)->default('http')->index(); // http, livewire, inertia, queue, console
                $table->json('context_data')->nullable();
                $table->longText('stack_trace')->nullable();
                $table->unsignedInteger('occurrences_count')->default(1);
                $table->timestamp('last_seen_at')->nullable()->index();
                $table->string('status', 20)->default('unresolved')->index(); // unresolved, resolved, ignored
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'last_seen_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('error-alerts.database.table', 'error_alerts');
        Schema::dropIfExists($tableName);
    }
};
