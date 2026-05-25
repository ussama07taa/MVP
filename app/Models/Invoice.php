<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToTenant;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Invoice extends Model
{
    use SoftDeletes, BelongsToTenant, HasFactory, LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'expiry_date' => 'date',
        'validated_at' => 'datetime',
        'stock_deducted' => 'boolean',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    /**
     * Check if a quote has expired.
     */
    public function isExpired(): bool
    {
        if ($this->type !== 'quote') return false;
        if (!$this->expiry_date) return false;
        if (in_array($this->status, ['accepted', 'paid', 'cancelled', 'refused'])) return false;
        return $this->expiry_date->isPast();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "La facture {$this->invoice_number} a été {$eventName}");
    }

    // Relationships
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helpers
    public function isPaid(): bool
    {
        return (float) $this->amount_paid >= (float) $this->total;
    }

    public function remainingBalance(): float
    {
        return max(0, (float) $this->total - (float) $this->amount_paid);
    }

    /**
     * Generate next invoice number for tenant.
     * Format: FAC-0001 for invoices, DEV-0001 for quotes.
     */
    public static function generateNumber(int $tenantId, string $type = 'invoice'): string
    {
        $prefix = $type === 'quote' ? 'DEV' : 'FAC';

        $lastNumber = static::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('type', $type)
            ->orderByDesc('id')
            ->value('invoice_number');

        if ($lastNumber) {
            $sequence = (int) substr($lastNumber, strlen($prefix) + 1);
            $next = $sequence + 1;
        } else {
            $next = 1;
        }

        return $prefix . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
