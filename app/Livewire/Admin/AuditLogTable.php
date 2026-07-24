<?php

namespace App\Livewire\Admin;

use App\Enums\RoleName;
use App\Models\AuditLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Response;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogTable extends Component
{
    use WithPagination;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public ?string $userId = null;

    public ?string $modelType = null;

    public ?string $event = null;

    public ?string $search = null;

    public ?string $selectedAuditLogId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole(RoleName::Admin), 403);
    }

    public function updating(string $property): void
    {
        if (in_array($property, ['dateFrom', 'dateTo', 'userId', 'modelType', 'event', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function show(string $auditLogId): void
    {
        $this->selectedAuditLogId = $auditLogId;
    }

    public function closeModal(): void
    {
        $this->selectedAuditLogId = null;
    }

    public function exportCsv(): StreamedResponse
    {
        abort_unless(auth()->user()->hasRole(RoleName::Admin), 403);

        $logs = $this->query()->get();

        return Response::streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Timestamp', 'User', 'Event', 'Model', 'Model ID', 'Description', 'IP Address']);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->created_at?->toDateTimeString(),
                    $log->user?->email ?? 'system',
                    $log->event,
                    class_basename($log->auditable_type),
                    $log->auditable_id,
                    $log->description,
                    $log->ip_address,
                ]);
            }

            fclose($handle);
        }, 'audit-logs-'.now()->format('Y-m-d').'.csv');
    }

    private function query()
    {
        return AuditLog::query()
            ->with('user')
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->when($this->userId, fn ($q) => $q->where('user_id', $this->userId))
            ->when($this->modelType, fn ($q) => $q->where('auditable_type', $this->modelType))
            ->when($this->event, fn ($q) => $q->where('event', $this->event))
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('description', 'like', "%{$this->search}%")
                        ->orWhere('auditable_id', 'like', "%{$this->search}%")
                        ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$this->search}%"));
                });
            })
            ->latest('created_at');
    }

    public function render()
    {
        /** @var LengthAwarePaginator $auditLogs */
        $auditLogs = $this->query()->paginate(100);

        return view('livewire.admin.audit-log-table', [
            'auditLogs' => $auditLogs,
            'selectedAuditLog' => $this->selectedAuditLogId ? AuditLog::with('user')->find($this->selectedAuditLogId) : null,
        ])
            ->extends('layouts.admin', ['topbarTitle' => 'Audit Logs'])
            ->section('admin-content');
    }
}
