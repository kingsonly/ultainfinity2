<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramGroupSubscription extends Model
{
    use  HasFactory;
    
    protected $table = "telegram_group_subscription";
    public $approved = 1;
    public $banned = 2;
    
    public function group(){
        return $this->hasOne(TelegramGroup::class,"id","group_id")->orderBy('id', 'DESC');
    }

}

