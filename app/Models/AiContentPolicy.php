<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AiContentPolicy extends Model
{
    protected $fillable=['key','name','description','instructions','applies_to','version','is_enabled'];
    protected $casts=['is_enabled'=>'boolean'];
}
