<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'user_id',
        'status',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'discount_amount',
        'total_amount',
        'currency',
        'payment_method',
        'payment_status',
        'shipping_method',
        'shipping_address',
        'billing_address',
        'notes',
        'tracking_number',
        'shipped_at',
        'delivered_at'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    /**
     * Get the user that owns the order
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the products for the order
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'order_items')
                    ->withPivot('quantity', 'price', 'total')
                    ->withTimestamps();
    }

    /**
     * Get the order items for the order
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the payments for the order
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the shipping for the order
     */
    public function shipping(): HasMany
    {
        return $this->hasMany(Shipping::class);
    }

    /**
     * Scope to get orders by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get orders by payment status
     */
    public function scopeByPaymentStatus($query, $paymentStatus)
    {
        return $query->where('payment_status', $paymentStatus);
    }

    /**
     * Scope to get orders by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get recent orders
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to get pending orders
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'processing']);
    }

    /**
     * Scope to get completed orders
     */
    public function scopeCompleted($query)
    {
        return $query->whereIn('status', ['completed', 'delivered']);
    }

    /**
     * Scope to get cancelled orders
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Get the status badge color
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'pending' => 'warning',
            'processing' => 'info',
            'shipped' => 'primary',
            'delivered' => 'success',
            'completed' => 'success',
            'cancelled' => 'danger',
            'refunded' => 'secondary',
            default => 'secondary'
        };
    }

    /**
     * Get the payment status badge color
     */
    public function getPaymentStatusColorAttribute()
    {
        return match($this->payment_status) {
            'pending' => 'warning',
            'paid' => 'success',
            'failed' => 'danger',
            'refunded' => 'secondary',
            'partially_refunded' => 'info',
            default => 'secondary'
        };
    }

    /**
     * Get the formatted total amount
     */
    public function getFormattedTotalAttribute()
    {
        return '$' . number_format($this->total_amount, 2);
    }

    /**
     * Get the formatted subtotal
     */
    public function getFormattedSubtotalAttribute()
    {
        return '$' . number_format($this->subtotal, 2);
    }

    /**
     * Get the formatted tax amount
     */
    public function getFormattedTaxAmountAttribute()
    {
        return '$' . number_format($this->tax_amount, 2);
    }

    /**
     * Get the formatted shipping amount
     */
    public function getFormattedShippingAmountAttribute()
    {
        return '$' . number_format($this->shipping_amount, 2);
    }

    /**
     * Get the formatted discount amount
     */
    public function getFormattedDiscountAmountAttribute()
    {
        return '$' . number_format($this->discount_amount, 2);
    }

    /**
     * Check if order is paid
     */
    public function getIsPaidAttribute()
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Check if order is pending payment
     */
    public function getIsPendingPaymentAttribute()
    {
        return $this->payment_status === 'pending';
    }

    /**
     * Check if order is cancelled
     */
    public function getIsCancelledAttribute()
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if order is completed
     */
    public function getIsCompletedAttribute()
    {
        return in_array($this->status, ['completed', 'delivered']);
    }

    /**
     * Check if order is shipped
     */
    public function getIsShippedAttribute()
    {
        return in_array($this->status, ['shipped', 'delivered', 'completed']);
    }

    /**
     * Get the items count
     */
    public function getItemsCountAttribute()
    {
        return $this->orderItems()->sum('quantity');
    }

    /**
     * Get the shipping address formatted
     */
    public function getShippingAddressFormattedAttribute()
    {
        if (!$this->shipping_address) {
            return 'No shipping address';
        }

        $address = $this->shipping_address;
        return implode(', ', array_filter([
            $address['address_line_1'] ?? '',
            $address['address_line_2'] ?? '',
            $address['city'] ?? '',
            $address['state'] ?? '',
            $address['postal_code'] ?? '',
            $address['country'] ?? ''
        ]));
    }

    /**
     * Get the billing address formatted
     */
    public function getBillingAddressFormattedAttribute()
    {
        if (!$this->billing_address) {
            return 'No billing address';
        }

        $address = $this->billing_address;
        return implode(', ', array_filter([
            $address['address_line_1'] ?? '',
            $address['address_line_2'] ?? '',
            $address['city'] ?? '',
            $address['state'] ?? '',
            $address['postal_code'] ?? '',
            $address['country'] ?? ''
        ]));
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Generate order number when creating
        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'ORD-' . strtoupper(uniqid());
            }
        });
    }
}
