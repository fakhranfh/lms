<?php

namespace App\Repositories\PaymentGateway;

use App\Models\PaymentGateway;
use Illuminate\Support\Str;

class PaymentGatewayRepository implements PaymentGatewayRepositoryInterface
{
    public function query(array $filters = [])
    {
        $query = PaymentGateway::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query;
    }

    public function get(array $filters = [], array $with = [])
    {
        $query = $this->query($filters);

        return $query->with($with)->get();
    }

    public function getAll()
    {
        return PaymentGateway::all();
    }

    public function find($id)
    {
        return PaymentGateway::find($id);
    }

    public function create(array $data)
    {
        return PaymentGateway::create($data);
    }

    public function update($id, array $data)
    {
        $model = PaymentGateway::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete($id)
    {
        return PaymentGateway::destroy($id);
    }

    public function findBySchoolAndGatewayType(string $schoolId, int $gatewayTypeId)
    {
        if (! Str::isUuid($schoolId)) {
            return null;
        }

        return $this->query()
            ->where('school_id', $schoolId)
            ->where('gateway_type_id', $gatewayTypeId)
            ->first();
    }

    public function getEnabledForSchool(string $schoolId, array $with = [])
    {
        return PaymentGateway::where('school_id', $schoolId)
            ->where('is_enabled', true)
            ->with($with)
            ->get();
    }

    public function findEnabledForSchoolByGatewayName(string $schoolId, string $gatewayName)
    {
        return PaymentGateway::where('school_id', $schoolId)
            ->where('is_enabled', true)
            ->whereHas('paymentGatewayType', fn ($q) => $q->where('name', $gatewayName))
            ->first();
    }

    public function findFirstEnabledForSchool(string $schoolId)
    {
        return PaymentGateway::where('school_id', $schoolId)
            ->where('is_enabled', true)
            ->first();
    }
}
