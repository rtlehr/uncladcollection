<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AdvertisingPayment extends Model {protected $fillable=['uuid','advertising_invoice_id','type','status','provider','amount_cents','currency','provider_reference','stripe_checkout_session_id','stripe_payment_intent_id','notes','metadata','processed_at']; protected $casts=['metadata'=>'array','processed_at'=>'datetime']; public function invoice(): BelongsTo{return $this->belongsTo(AdvertisingInvoice::class,'advertising_invoice_id');}}
