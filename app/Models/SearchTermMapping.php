<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SearchTermMapping extends Model
{
    public const STATUS_PENDING='pending'; public const STATUS_APPROVED='approved'; public const STATUS_REJECTED='rejected';
    protected $fillable=['search_term_id','suggested_canonical_term','approved_canonical_term','suggested_synonyms','approved_synonyms','intent_category','confidence','ai_explanation','status','source','provider','model','reviewed_by_user_id','reviewed_at','analyzed_at'];
    protected function casts(): array { return ['suggested_synonyms'=>'array','approved_synonyms'=>'array','confidence'=>'float','reviewed_at'=>'datetime','analyzed_at'=>'datetime']; }
    public function searchTerm(): BelongsTo { return $this->belongsTo(SearchTerm::class); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class,'reviewed_by_user_id'); }
}
