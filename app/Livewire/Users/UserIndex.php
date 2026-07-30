<?php

namespace App\Livewire\Users;

use App\Enums\RoleName;
use App\Repositories\Role\RoleRepositoryInterface;
use App\Services\UserService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;

class UserIndex extends Component
{
    public ?string $search = null;

    public ?string $filterRole = null;

    public string $sort = 'name';

    public string $direction = 'asc';

    public int $perPage = 15;

    public ?string $successMessage = null;

    public function mount(): void
    {
        $this->successMessage = session('success');
    }

    public function updating(string $property): void
    {
        if (in_array($property, ['search', 'filterRole'])) {
            $this->sort = 'name';
            $this->direction = 'asc';
        }
    }

    public function sortBy(string $field): void
    {
        if ($this->sort === $field) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $field;
            $this->direction = 'asc';
        }
    }

    #[Computed]
    public function users(): LengthAwarePaginator
    {
        return resolve(UserService::class)->paginate(
            filters: [
                'search' => $this->search,
                'role_id' => $this->filterRole,
                'sort' => $this->sort,
                'direction' => $this->direction,
            ],
            with: ['roles'],
            perPage: $this->perPage,
        );
    }

    #[Computed]
    public function availableRoles(): Collection
    {
        return resolve(RoleRepositoryInterface::class)->getAll();
    }

    public function render()
    {
        $isAdminUser = auth()->user()->hasRole(RoleName::Admin);

        return view('livewire.users.user-index', [
            'users' => $this->users,
        ])
            ->extends($isAdminUser ? 'layouts.admin' : 'layouts.app', ['topbarTitle' => 'Users'])
            ->section($isAdminUser ? 'admin-content' : 'app-content');
    }
}
