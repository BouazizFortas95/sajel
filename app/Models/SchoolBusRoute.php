<?php

namespace App\Models;

use App\Enums\BusRouteStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolBusRoute extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'passenger_capacity' => 'integer',
        'subscribed_students_count' => 'integer',
        'status' => BusRouteStatus::class,
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(StudentBusSubscription::class, 'bus_route_id');
    }

    public function activeSubscriptions(): HasMany
    {
        return $this->hasMany(StudentBusSubscription::class, 'bus_route_id')
            ->where('status', 'active');
    }
}
