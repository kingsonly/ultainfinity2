<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramUsers extends Model
{
    use  HasFactory;
    
    protected $table = "telegram_users";

    public function groups(){
        return $this->hasMany(TelegramGroupSubscription::class,"user_id","id")->orderBy('id', 'DESC');
    }

    public function messages(){
        return $this->hasMany(TelegramMessage::class,"user_id","id")->orderBy('id', 'DESC');
    }

}
