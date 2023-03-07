<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TelegramUserSubscriptionRequest extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('telegram_user_subscription_request', function(Blueprint $table) {
            $table->id();
            $table->string('group_id', 50);
            $table->string('user_id', 50);
            $table->string('message');
            $table->string('telegram_message_id');
            $table->integer('approval_status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('telegram_user_subscription_request');
    }
}
