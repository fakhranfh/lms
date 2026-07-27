<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\RoleName;
use App\Models\PaymentTransaction;
use App\Models\PricingTier;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Repositories\PaymentTransaction\PaymentTransactionRepositoryInterface;
use App\Repositories\PricingTier\PricingTierRepositoryInterface;
use App\Repositories\Role\RoleRepositoryInterface;
use App\Repositories\School\SchoolRepositoryInterface;
use App\Repositories\SchoolTier\SchoolTierRepositoryInterface;
use App\Repositories\TierChange\TierChangeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SchoolService
{
    public function __construct(
        protected SchoolRepositoryInterface $schoolRepository,
        protected PricingTierRepositoryInterface $pricingTierRepository,
        protected RoleRepositoryInterface $roleRepository,
        protected SchoolTierRepositoryInterface $schoolTierRepository,
        protected TierChangeRepositoryInterface $tierChangeRepository,
        protected PaymentTransactionRepositoryInterface $paymentTransactionRepository,
        protected R2StorageService $r2Storage,
        protected RoleService $roleService,
    ) {}

    /**
     * Get paginated schools with filters.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function paginate(array $filters = [], array $with = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->schoolRepository->paginate($filters, $with, $perPage);
    }

    /**
     * Get all schools.
     */
    public function getAll(): Collection
    {
        return $this->schoolRepository->getAll();
    }

    /**
     * Find a school by ID.
     */
    public function find(string $id)
    {
        return $this->schoolRepository->find($id);
    }

    /**
     * Find a school by its domain.
     */
    public function findByDomain(string $domain): ?School
    {
        return $this->schoolRepository->findByDomain($domain);
    }

    /**
     * Find a school by ID with eager-loaded relationships.
     *
     * @param  array<string>  $with
     */
    public function findWith(string $id, array $with = [])
    {
        return $this->schoolRepository->findWith($id, $with);
    }

    /**
     * Create a new school.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): School
    {
        if (empty($data['tier_id'])) {
            $data['tier_id'] = $this->pricingTierRepository->get(['slug' => 'basic'])->firstOrFail()->id;
        }

        if (($data['logo'] ?? null) instanceof UploadedFile) {
            $data['logo_path'] = $this->r2Storage->uploadPublicFile($data['logo'], 'school-logos');
        }
        unset($data['logo']);

        $school = $this->schoolRepository->create($data);
        $this->assignDefaultTier($school);
        $this->roleService->createDefaultRolesForSchool($school->id);

        return $school;
    }

    /**
     * Record a pending payment transaction for a paid-tier school registration,
     * deferring the actual school creation until the payment is confirmed.
     *
     * @param  array<string, mixed>  $data
     */
    public function createRegistrationTransaction(User $user, PricingTier $tier, array $data): PaymentTransaction
    {
        if (($data['logo'] ?? null) instanceof UploadedFile) {
            $data['logo_path'] = $this->r2Storage->uploadPublicFile($data['logo'], 'school-logos');
        }
        unset($data['logo']);

        $data['tier_id'] = $tier->id;

        $subtotal = (float) $tier->price;
        $vatRate = (float) config('billing.vat_rate');
        $adminFeeRate = (float) config('billing.admin_fee_rate');
        $vatAmount = $subtotal * $vatRate;
        $adminFeeAmount = $subtotal * $adminFeeRate;

        return $this->paymentTransactionRepository->create([
            'initiated_by' => $user->id,
            'transaction_id' => (string) Str::uuid(),
            'amount' => $subtotal + $vatAmount + $adminFeeAmount,
            'currency' => 'IDR',
            'status' => PaymentStatus::Pending,
            'registration_data' => $data,
            'metadata' => [
                'tier_name' => $tier->name,
                'billing_period' => strtolower($tier->billing_period->label()),
                'subtotal' => $subtotal,
                'vat_rate' => $vatRate,
                'vat_amount' => $vatAmount,
                'admin_fee_rate' => $adminFeeRate,
                'admin_fee_amount' => $adminFeeAmount,
            ],
        ]);
    }

    /**
     * Finalize a paid-tier registration once its payment transaction has been
     * confirmed: creates the school from the stored registration data and
     * attaches the initiating user as its admin.
     */
    public function completeRegistrationTransaction(PaymentTransaction $transaction): School
    {
        if ($transaction->status === PaymentStatus::Completed && $transaction->school) {
            return $transaction->school;
        }

        return DB::transaction(function () use ($transaction): School {
            $school = $this->create($transaction->registration_data);
            $this->attachAdmin($school, $transaction->initiatedBy);

            $this->paymentTransactionRepository->update($transaction->id, [
                'school_id' => $school->id,
                'status' => PaymentStatus::Completed,
            ]);

            return $school;
        });
    }

    /**
     * Attach a School Admin to a school: creates the school_admins pivot row
     * and grants that school's School Admin role.
     */
    public function attachAdmin(School $school, User $user): void
    {
        $this->schoolRepository->attachAdmin($school, $user->id);

        $schoolAdminRole = $this->roleRepository->get([
            'school_id' => $school->id,
            'name' => RoleName::SchoolAdmin->value,
        ])->first();

        if (! $schoolAdminRole) {
            throw (new ModelNotFoundException)->setModel(Role::class);
        }

        $user->assignRole($schoolAdminRole);
    }

    /**
     * Determine whether the given user administers the given school
     * (i.e. has a school_admins pivot row for it).
     */
    public function administers(User $user, School $school): bool
    {
        return $this->schoolRepository->administers($school, $user->id);
    }

    /**
     * Assign default tier to a school.
     */
    public function assignDefaultTier(School $school): void
    {
        $basicTier = $school->tier;

        DB::transaction(function () use ($school, $basicTier): void {
            $schoolTier = $this->schoolTierRepository->create([
                'school_id' => $school->id,
                'tier_id' => $basicTier->id,
                'status' => 'active',
                'started_at' => now(),
                'expires_at' => null,
            ]);

            $this->tierChangeRepository->create([
                'school_tier_id' => $schoolTier->id,
                'from_tier_id' => null,
                'to_tier_id' => $basicTier->id,
                'change_type' => 'initial',
                'prorated_amount' => 0,
                'changed_at' => now(),
            ]);
        });
    }

    /**
     * Delete a school.
     */
    public function delete(string $id): int
    {
        $school = $this->schoolRepository->find($id);

        if ($school && $school->domain === config('app.domain')) {
            throw ValidationException::withMessages([
                'school' => __('The root domain school cannot be deleted.'),
            ]);
        }

        if ($school && $school->logo_path) {
            $this->r2Storage->delete($school->logo_path);
        }

        return $this->schoolRepository->delete($id);
    }
}
