<?php

namespace App\Repositories\PaymentGateway;

use App\Models\PaymentGateway;

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

    public function findByGatewayType(int $gatewayTypeId)
    {
        return $this->query()
            ->where('gateway_type_id', $gatewayTypeId)
            ->first();
    }

    public function getEnabled(array $with = [])
    {
        return PaymentGateway::where('is_enabled', true)
            ->with($with)
            ->get();
    }

    public function findEnabledByGatewayName(string $gatewayName)
    {
        return PaymentGateway::where('is_enabled', true)
            ->whereHas('paymentGatewayType', fn ($q) => $q->where('name', $gatewayName))
            ->first();
    }

    public function findFirstEnabled()
    {
        return PaymentGateway::where('is_enabled', true)->first();
    }
}
