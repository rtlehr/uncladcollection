<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MessageBoxSubmission extends Model { protected $fillable=['message_box_id','user_id','visitor_token','data']; protected $casts=['data'=>'array']; }
