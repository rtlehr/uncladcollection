<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MessageBoxView extends Model { protected $fillable=['message_box_id','user_id','visitor_token','seen_at','dismissed_at']; protected $casts=['seen_at'=>'datetime','dismissed_at'=>'datetime']; }
