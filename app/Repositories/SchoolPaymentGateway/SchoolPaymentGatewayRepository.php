<?php

namespace App\Repositories\SchoolPaymentGateway;

use App\Models\SchoolPaymentGateway;
use Illuminate\Support\Str;

class SchoolPaymentGatewayRepository implements SchoolPaymentGatewayRepositoryInterface
{
    public function query(array $filters = [])
    {
        $query = SchoolPaymentGateway::query();

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
        return SchoolPaymentGateway::all();
    }

    public function find($id)
    {
        return SchoolPaymentGateway::find($id);
    }

    public function create(array $data)
    {
        return SchoolPaymentGateway::create($data);
    }

    public function update($id, array $data)
    {
        $model = SchoolPaymentGateway::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete($id)
    {
        return SchoolPaymentGateway::destroy($id);
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
        return SchoolPaymentGateway::where('school_id', $schoolId)
            ->where('is_enabled', true)
            ->with($with)
            ->get();
    }

    public function findEnabledForSchoolByGatewayName(string $schoolId, string $gatewayName)
    {
        return SchoolPaymentGateway::where('school_id', $schoolId)
            ->where('is_enabled', true)
            ->whereHas('paymentGatewayType', fn ($q) => $q->where('name', $gatewayName))
            ->first();
    }

    public function findFirstEnabledForSchool(string $schoolId)
    {
        return SchoolPaymentGateway::where('school_id', $schoolId)
            ->where('is_enabled', true)
            ->first();
    }
}
