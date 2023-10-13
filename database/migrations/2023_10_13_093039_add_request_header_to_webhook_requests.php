<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRequestHeaderToWebhookRequests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('webhook_requests', function (Blueprint $table) {
            $table->text('request_header')->nullable()->after('id');
            $table->boolean('is_processed')->nullable()->default(false)->after('source_app');
            $table->string('document_type')->nullable()->after('is_processed');
            $table->string('document_number')->nullable()->after('document_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('webhook_requests', function (Blueprint $table) {
            $table->dropColumn('request_header');
            $table->dropColumn('is_processed');
            $table->dropColumn('document_number');
            $table->dropColumn('document_type');
        });
    }
}
