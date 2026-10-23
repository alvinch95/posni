<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebLinksTable extends Migration
{
    public function up()
    {
        Schema::create('web_links', function (Blueprint $table) {
            $table->id();
            $table->string('group', 20); // marketplace | social
            $table->string('platform', 20); // shopee | tokopedia | toco | instagram | tiktok | other
            $table->string('label');
            $table->string('url');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
        });
    }

    public function down()
    {
        Schema::dropIfExists('web_links');
    }
}
