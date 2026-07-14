<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\SchoolFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'domain'])]
class School extends Model
{
    /** @use HasFactory<SchoolFactory> */
    use HasFactory, HasUuid;

    protected $table = 'schools';

    /**
     * Get the users belonging to the school.
     *
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the payment gateways.
     *
     * @return HasMany<SchoolPaymentGateway, $this>
     */
    public function paymentGateways(): HasMany
    {
        return $this->hasMany(SchoolPaymentGateway::class);
    }
}
