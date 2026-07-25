<?php

namespace App\Repositories\SchoolPaymentGateway;

interface SchoolPaymentGatewayRepositoryInterface
{
    public function query(array $filters = []);

    public function get(array $filters = [], array $with = []);

    public function getAll();

    public function find($id);

    public function create(array $data);

    public function update($id, array $data);

    public function delete($id);

    public function findBySchoolAndGatewayType(string $schoolId, int $gatewayTypeId);

    /**
     * Enabled gateways for a school, eager-loaded with type + credentials.
     */
    public function getEnabledForSchool(string $schoolId, array $with = []);

    /**
     * The first enabled gateway for a school whose type has the given name.
     */
    public function findEnabledForSchoolByGatewayName(string $schoolId, string $gatewayName);

    /**
     * The first enabled gateway for a school, regardless of type.
     */
    public function findFirstEnabledForSchool(string $schoolId);
}
