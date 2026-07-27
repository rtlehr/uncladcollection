<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SearchTermVariant extends Model
{
    protected $fillable = ['search_term_id','raw_term','raw_term_hash','normalized_raw_term','search_count','last_searched_at'];
    protected function casts(): array { return ['search_count'=>'integer','last_searched_at'=>'datetime']; }
    public function searchTerm(): BelongsTo { return $this->belongsTo(SearchTerm::class); }
}
