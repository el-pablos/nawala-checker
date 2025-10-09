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
        // Tags table
        Schema::create('nc_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#3B82F6');
            $table->timestamps();
            
            $table->index('slug');
        });

        // Groups table
        Schema::create('nc_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('color', 7)->nullable()->default('#3B82F6'); // UI color (nullable for flexibility)
            $table->unsignedInteger('check_interval')->default(300); // seconds
            $table->unsignedInteger('default_check_interval')->nullable(); // default for targets in this group
            $table->unsignedInteger('jitter_percent')->default(15); // 15%
            $table->boolean('notifications_enabled')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('slug');
            $table->index('created_by');
            $table->index('color');
        });

        // Resolvers table (DNS servers)
        Schema::create('nc_resolvers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('dns'); // dns, doh, dot
            $table->string('address'); // IP or URL
            $table->unsignedInteger('port')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('priority')->default(100);
            $table->unsignedInteger('weight')->default(100);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['is_active', 'priority']);
        });

        // Vantage Nodes table (optional multi-location checking)
        Schema::create('nc_vantage_nodes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location'); // ISP/Region
            $table->string('endpoint_url');
            $table->string('api_key')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('weight')->default(100);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('is_active');
        });

        // Targets table (domains/URLs to monitor)
        Schema::create('nc_targets', function (Blueprint $table) {
            $table->id();
            $table->string('domain_or_url');
            $table->string('type')->default('domain'); // domain, url
            $table->foreignId('group_id')->nullable()->constrained('nc_groups')->nullOnDelete();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('check_interval')->nullable(); // override group interval
            $table->string('current_status')->default('UNKNOWN'); // OK, DNS_FILTERED, HTTP_BLOCKPAGE, etc.
            $table->string('last_status')->nullable(); // Cache of last check status
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_status_change_at')->nullable();
            $table->unsignedInteger('consecutive_failures')->default(0);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            // Per-target Telegram notification settings
            $table->boolean('telegram_enabled')->default(false);
            $table->string('telegram_bot_token')->nullable();
            $table->string('telegram_chat_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['domain_or_url', 'owner_id']);
            $table->index(['owner_id', 'enabled']);
            $table->index(['group_id', 'enabled']);
            $table->index('current_status');
            $table->index('last_status');
            $table->index('last_checked_at');
            $table->index('telegram_enabled');
        });

        // Target Tags pivot table
        Schema::create('nc_target_tag', function (Blueprint $table) {
            $table->foreignId('target_id')->constrained('nc_targets')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('nc_tags')->cascadeOnDelete();
            
            $table->primary(['target_id', 'tag_id']);
        });

        // Check Results table
        Schema::create('nc_check_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('target_id')->constrained('nc_targets')->cascadeOnDelete();
            $table->foreignId('resolver_id')->nullable()->constrained('nc_resolvers')->nullOnDelete();
            $table->foreignId('vantage_node_id')->nullable()->constrained('nc_vantage_nodes')->nullOnDelete();
            $table->string('status'); // OK, DNS_FILTERED, HTTP_BLOCKPAGE, HTTPS_SNI_BLOCK, TIMEOUT, RST, INCONCLUSIVE
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->string('resolved_ip')->nullable();
            $table->unsignedInteger('http_status_code')->nullable();
            $table->text('error_message')->nullable();
            $table->json('raw_response')->nullable();
            $table->json('resolver_results')->nullable(); // Results from multiple resolvers
            $table->unsignedInteger('confidence')->default(100); // 0-100
            $table->timestamp('checked_at');

            $table->index(['target_id', 'checked_at']);
            $table->index(['target_id', 'status']);
            $table->index('checked_at');
        });

        // Shortlink Groups table
        Schema::create('nc_shortlink_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('rotation_threshold')->default(3); // consecutive failures before rotation
            $table->unsignedInteger('cooldown_seconds')->default(300); // wait before rotating again
            $table->unsignedInteger('rotation_cooldown')->default(300); // alias for cooldown_seconds
            $table->unsignedInteger('min_confidence')->default(80); // minimum confidence to trigger rotation
            $table->boolean('auto_rollback')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('slug');
            $table->index('rotation_cooldown');
        });

        // Shortlinks table (without foreign keys to nc_shortlink_targets - added later)
        Schema::create('nc_shortlinks', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->foreignId('group_id')->nullable()->constrained('nc_shortlink_groups')->nullOnDelete();
            $table->unsignedBigInteger('current_target_id')->nullable();
            $table->unsignedBigInteger('original_target_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_rotated_at')->nullable();
            $table->unsignedInteger('rotation_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('slug');
            $table->index(['group_id', 'is_active']);
        });

        // Shortlink Targets table (candidate destinations)
        Schema::create('nc_shortlink_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shortlink_id')->constrained('nc_shortlinks')->cascadeOnDelete();
            $table->string('url');
            $table->unsignedInteger('priority')->default(100); // lower = higher priority
            $table->unsignedInteger('weight')->default(100);
            $table->boolean('is_active')->default(true);
            $table->string('current_status')->default('UNKNOWN');
            $table->timestamp('last_checked_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['shortlink_id', 'is_active', 'priority']);
            $table->index('current_status');
        });

        // Add foreign key constraints from nc_shortlinks to nc_shortlink_targets
        // (now that nc_shortlink_targets table exists)
        Schema::table('nc_shortlinks', function (Blueprint $table) {
            $table->foreign('current_target_id')
                  ->references('id')
                  ->on('nc_shortlink_targets')
                  ->nullOnDelete();

            $table->foreign('original_target_id')
                  ->references('id')
                  ->on('nc_shortlink_targets')
                  ->nullOnDelete();
        });

        // Notification Channels table (Telegram)
        Schema::create('nc_notification_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('telegram'); // telegram, slack, email, webhook
            $table->string('chat_id'); // Telegram chat_id
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('nc_groups')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->boolean('notify_on_block')->default(true);
            $table->boolean('notify_on_recover')->default(true);
            $table->boolean('notify_on_rotation')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'is_active']);
            $table->index(['group_id', 'is_active']);
        });

        // Rotation History table
        Schema::create('nc_rotation_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shortlink_id')->constrained('nc_shortlinks')->cascadeOnDelete();
            $table->foreignId('from_target_id')->nullable()->constrained('nc_shortlink_targets')->nullOnDelete();
            $table->foreignId('to_target_id')->nullable()->constrained('nc_shortlink_targets')->nullOnDelete();
            $table->string('reason'); // auto_rotation, manual, rollback
            $table->text('notes')->nullable();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rotated_at');
            
            $table->index(['shortlink_id', 'rotated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys from nc_shortlinks to nc_shortlink_targets first
        Schema::table('nc_shortlinks', function (Blueprint $table) {
            $table->dropForeign(['current_target_id']);
            $table->dropForeign(['original_target_id']);
        });

        Schema::dropIfExists('nc_rotation_history');
        Schema::dropIfExists('nc_notification_channels');
        Schema::dropIfExists('nc_shortlink_targets');
        Schema::dropIfExists('nc_shortlinks');
        Schema::dropIfExists('nc_shortlink_groups');
        Schema::dropIfExists('nc_check_results');
        Schema::dropIfExists('nc_target_tag');
        Schema::dropIfExists('nc_targets');
        Schema::dropIfExists('nc_vantage_nodes');
        Schema::dropIfExists('nc_resolvers');
        Schema::dropIfExists('nc_groups');
        Schema::dropIfExists('nc_tags');
    }
};

