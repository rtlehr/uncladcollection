<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AdvertisingInvoiceItem extends Model {protected $fillable=['advertising_invoice_id','description','billing_model','quantity','unit_amount_cents','line_total_cents','metadata']; protected $casts=['metadata'=>'array']; public function invoice(): BelongsTo{return $this->belongsTo(AdvertisingInvoice::class,'advertising_invoice_id');}}
