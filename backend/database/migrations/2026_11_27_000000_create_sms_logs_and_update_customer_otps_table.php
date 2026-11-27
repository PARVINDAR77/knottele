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
        // 1. Create or enhance sms_logs table for audit & tracking
        if (!Schema::hasTable('sms_logs')) {
            Schema::create('sms_logs', function (Blueprint $table) {
                $table->id();
                $table->string('to', 25)->index();
                $table->string('from', 25)->nullable()->default('TXXTOO');
                $table->text('message')->nullable();
                $table->string('otp_code', 10)->nullable();
                $table->string('purpose', 50)->default('login')->index();
                $table->string('status', 50)->default('queued')->index();
                $table->string('message_id', 150)->nullable()->index();
                $table->integer('http_code')->nullable();
                $table->decimal('duration_ms', 8, 2)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('error_message')->nullable();
                $table->longText('response_payload')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('sent_at')->nullable()->useCurrent();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();

                $table->index(['created_at', 'status']);
            });
        } else {
            Schema::table('sms_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('sms_logs', 'otp_code')) {
                    $table->string('otp_code', 10)->nullable()->after('status');
                }
                if (!Schema::hasColumn('sms_logs', 'purpose')) {
                    $table->string('purpose', 50)->default('login')->after('otp_code')->index();
                }
                if (!Schema::hasColumn('sms_logs', 'http_code')) {
                    $table->integer('http_code')->nullable()->after('purpose');
                }
                if (!Schema::hasColumn('sms_logs', 'duration_ms')) {
                    $table->decimal('duration_ms', 8, 2)->nullable()->after('http_code');
                }
                if (!Schema::hasColumn('sms_logs', 'ip_address')) {
                    $table->string('ip_address', 45)->nullable()->after('duration_ms');
                }
                if (!Schema::hasColumn('sms_logs', 'error_message')) {
                    $table->text('error_message')->nullable()->after('ip_address');
                }
                if (!Schema::hasColumn('sms_logs', 'response_payload')) {
                    $table->longText('response_payload')->nullable()->after('error_message');
                }
                if (!Schema::hasColumn('sms_logs', 'verified_at')) {
                    $table->timestamp('verified_at')->nullable()->after('delivered_at');
                }
                // Allow from and message to be nullable if required
                if (Schema::hasColumn('sms_logs', 'from')) {
                    $table->string('from', 25)->nullable()->change();
                }
                if (Schema::hasColumn('sms_logs', 'message')) {
                    $table->text('message')->nullable()->change();
                }
                if (Schema::hasColumn('sms_logs', 'status')) {
                    $table->string('status', 50)->default('queued')->change();
                }
            });
        }

        // 2. Enhance customer_otps table to support mobile number verification
        if (Schema::hasTable('customer_otps')) {
            Schema::table('customer_otps', function (Blueprint $table) {
                if (!Schema::hasColumn('customer_otps', 'mobile')) {
                    $table->string('mobile', 25)->nullable()->after('id')->index();
                }
                if (Schema::hasColumn('customer_otps', 'email')) {
                    $table->string('email')->nullable()->change();
                }
            });
        }

        // 3. Make customers password and email nullable if not already, to smoothly support passwordless OTP logins
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (Schema::hasColumn('customers', 'password')) {
                    $table->string('password')->nullable()->change();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('customer_otps')) {
            Schema::table('customer_otps', function (Blueprint $table) {
                if (Schema::hasColumn('customer_otps', 'mobile')) {
                    $table->dropColumn('mobile');
                }
            });
        }
    }
};
