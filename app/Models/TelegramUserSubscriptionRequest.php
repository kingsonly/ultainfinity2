<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramUserSubscriptionRequest extends Model
{
    use  HasFactory;
    public static $pending = 0;
    public static $approved = 1;
    public static $banned = 2;

    
    
    protected $table = "telegram_user_subscription_request";

}
