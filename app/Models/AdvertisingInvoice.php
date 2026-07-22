<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class AdvertisingInvoice extends Model {
    use HasFactory;
    public const STATUSES=['draft','issued','partially_paid','paid','overdue','void','refunded'];
    protected $fillable=['uuid','invoice_number','advertiser_id','advertising_campaign_id','status','currency','subtotal_cents','discount_cents','tax_cents','total_cents','paid_cents','refunded_cents','balance_cents','issued_at','due_at','paid_at','voided_at','notes'];
    protected $casts=['issued_at'=>'date','due_at'=>'date','paid_at'=>'datetime','voided_at'=>'datetime'];
    public function advertiser(): BelongsTo{return $this->belongsTo(Advertiser::class);} public function campaign(): BelongsTo{return $this->belongsTo(AdvertisingCampaign::class,'advertising_campaign_id');}
    public function items(): HasMany{return $this->hasMany(AdvertisingInvoiceItem::class);} public function payments(): HasMany{return $this->hasMany(AdvertisingPayment::class);}
}
