<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SalesTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'target_type',
        'target_value',
        'order_scope',
        'reward_type',
        'reward_value',
        'reward_note',
        'start_date',
        'end_date',
        'priority',
        'status',
        'created_by',
    ];

    protected $casts = [
        'target_value' => 'float',
        'reward_value' => 'float',
        'start_date' => 'date',
        'end_date' => 'date',
        'priority' => 'integer',
    ];

    public function participants()
    {
        return $this->hasMany(SalesTargetParticipant::class, 'sales_target_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public function scopeActiveNow($query, ?Carbon $at = null)
    {
        $at = $at ?: now();

        return $query->active()
            ->where(function ($q) use ($at) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $at->toDateString());
            })
            ->where(function ($q) use ($at) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $at->toDateString());
            });
    }

    public function getProgressForUser(int $userId): array
    {
        $target = (float) $this->target_value;
        $achieved = $this->calculateAchievedForUser($userId);
        $remaining = max($target - $achieved, 0);
        $completed = $target > 0 ? $achieved >= $target : false;
        $progressPercent = $target > 0 ? min(100, round(($achieved / $target) * 100, 2)) : 0;

        return [
            'target' => $target,
            'achieved' => $achieved,
            'remaining' => $remaining,
            'completed' => $completed,
            'progress_percent' => $progressPercent,
        ];
    }

    public function calculateAchievedForUser(int $userId): float
    {
        if ($this->target_type === 'quantity') {
            $query = DB::table('orderproducts')
                ->join('orders', 'orders.id', '=', 'orderproducts.order_id')
                ->where('orders.user_id', $userId);

            $this->applyOrderScopeForJoin($query);

            return (float) $query->sum('orderproducts.quantity');
        }

        $ordersQuery = Order::query()->where('user_id', $userId);
        $this->applyOrderScopeForOrderModel($ordersQuery);

        $subTotal = (float) $ordersQuery->sum('subTotal');
        $paymentAmount = (float) $ordersQuery->sum('paymentAmount');
        $deliveryCharge = (float) $ordersQuery->sum('deliveryCharge');

        return max($subTotal + $paymentAmount - $deliveryCharge, 0);
    }

    protected function applyOrderScopeForOrderModel($query): void
    {
        if ($this->order_scope === 'delivered') {
            $query->where('status', 'Delivered');
            return;
        }

        $query->where('status', '!=', 'Canceled');
    }

    protected function applyOrderScopeForJoin($query): void
    {
        if ($this->order_scope === 'delivered') {
            $query->where('orders.status', 'Delivered');
            return;
        }

        $query->where('orders.status', '!=', 'Canceled');
    }
}
