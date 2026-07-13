<?php

namespace App\Console\Commands\Stubs;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use App\Console\Commands\Helpers\SchemaHelper;

class BladeCreateEditStubGenerator
{
    public function generate(string $name, string $label): string
    {
        $labelKebab = Str::kebab($label);
        $columns = SchemaHelper::getTableColumns($name);
        $exclude = ['id', 'created_at', 'updated_at', 'deleted_at', 'remember_token', 'password'];
        $inputs = '';

        $modelClass = "App\\Models\\$name";
        $foreignKeys = [];
        if (class_exists($modelClass)) {
            $model = new $modelClass;
            $table = $model->getTable();
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $doctrineTable = $sm->introspectTable($table);
            foreach ($doctrineTable->getForeignKeys() as $fk) {
                foreach ($fk->getLocalColumns() as $localCol) {
                    $foreignKeys[$localCol] = [
                        'table' => $fk->getForeignTableName(),
                        'column' => $fk->getForeignColumns()[0],
                    ];
                }
            }
        }

        $firstCol = null;
        foreach (array_keys(array_diff_key($columns, $foreignKeys)) as $colName) {
            if ($colName !== 'id') {
                $firstCol = $colName;
                break;
            }
        }
        if (!$firstCol) {
            $firstCol = 'id';
        }

        foreach ($columns as $col => $type) {
            if (in_array($col, $exclude)) {
                continue;
            }

            $colLabel = Str::title(str_replace('_', ' ', $col));
            $field = '';

            if (isset($foreignKeys[$col])) {
                $relatedTable = $foreignKeys[$col]['table'];
                $relatedModel = Str::studly(Str::singular($relatedTable));
                $optionsVar = "\${$relatedTable}";
                $displayCol = 'name';

                if (class_exists("App\\Models\\$relatedModel")) {
                    $connection = Schema::getConnection();
                    $relatedCols = Schema::getColumnListing($relatedTable);
                    $displayCol = 'id';
                    foreach ($relatedCols as $rc) {
                        if ($rc !== 'id') {
                            $displayCol = $rc;
                            break;
                        }
                    }
                }

                $field = <<<HTML
<select class="form-select" id="$col" name="$col" required @if(isset(\$isView)) disabled @endif>
  <option value="">-- @lang('Select') --</option>
  @foreach($optionsVar as \$opt)
    <option value="{{ \$opt->id }}"
      {{ (isset(\$item) && \$item->$col == \$opt->id) || old('$col') == \$opt->id ? 'selected' : '' }}>
      {{ \$opt->$displayCol }}
    </option>
  @endforeach
</select>
HTML;
            } else {
                $field = match ($type) {
                    'boolean' => $this->generateBooleanField($col),
                    'text' => $this->generateTextareaField($col),
                    'date' => $this->generateDateField($col),
                    'datetime', 'timestamp' => $this->generateDatetimeField($col),
                    'integer', 'bigint', 'smallint', 'tinyint' => $this->generateNumberField($col),
                    'float', 'double', 'decimal' => $this->generateDecimalField($col),
                    default => $this->generateTextField($col),
                };
            }

            $inputs .= <<<HTML
          <div class="mb-3">
            <label for="$col" class="form-label">@lang('$colLabel')</label>
            $field
            @error('$col')
              <div class="invalid-feedback d-block">{{ \$message }}</div>
            @enderror
          </div>

HTML;
        }

        $template = <<<BLADE
@extends('siakad/layouts/contentNavbarLayout')

@section('title', isset(\$item) ? (isset(\$isView) ? __('View') : __('Edit')) : __('Create'))

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="/{$labelKebab}">@lang('{$label}')</a>
      </li>
      @if(isset(\$item))
        <li class="breadcrumb-item">
          <a href="/{$labelKebab}/{{ \$item->id }}/view">{{ \$item->{$firstCol} ?? \$item->id }}</a>
        </li>
      @endif
      <li class="breadcrumb-item active">
        @lang(isset(\$item) ? (isset(\$isView) ? 'View' : 'Edit') : 'Create')
      </li>
    </ol>
  </nav>

  <!-- Header -->
  <div class="mb-4">
    <h4 class="mb-0">
      @lang(isset(\$item) ? (isset(\$isView) ? 'View' : 'Edit') : 'Create')
      @lang('{$label}')
    </h4>
    <small class="text-muted">@lang('Fill in the form below')</small>
  </div>

  <!-- Alerts -->
  @include('siakad.components.error-alert')
  @include('siakad.components.success-alert')

  <!-- Form Card -->
  <div class="row">
    <div class="col-xl-8">
      <div class="card mb-4">
        <div class="card-body">
          <form method="POST" class="needs-validation" novalidate>
            @csrf

            <!-- Fields -->
            <div class="row">
{$inputs}            </div>

            <!-- Buttons -->
            @if(!isset(\$isView))
              <div class="mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-primary me-2">
                  <i class="bx bx-check me-2"></i>
                  @lang(isset(\$item) ? 'Save Changes' : 'Create')
                </button>
                <a href="/{$labelKebab}" class="btn btn-outline-secondary">
                  @lang('Cancel')
                </a>
              </div>
            @else
              <div class="mt-4 pt-3 border-top">
                <a href="/{$labelKebab}/{{ \$item->id }}/edit" class="btn btn-primary me-2">
                  <i class="bx bx-edit me-2"></i>
                  @lang('Edit')
                </a>
                <a href="/{$labelKebab}" class="btn btn-outline-secondary">
                  @lang('Back')
                </a>
              </div>
            @endif
          </form>
        </div>
      </div>
    </div>

    <!-- Sidebar -->
    <div class="col-xl-4">
      <!-- Info Card -->
      <div class="card mb-4">
        <div class="card-body">
          <h6 class="card-title mb-3">@lang('Information')</h6>
          @if(isset(\$item))
            <div class="mb-3">
              <small class="text-muted">@lang('Created')</small>
              <p class="mb-0">{{ \$item->created_at?->format('d M Y H:i') }}</p>
            </div>
            <div class="mb-3">
              <small class="text-muted">@lang('Updated')</small>
              <p class="mb-0">{{ \$item->updated_at?->format('d M Y H:i') }}</p>
            </div>
            <hr>
            <button type="button" class="btn btn-sm btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#deleteModal">
              <i class="bx bx-trash me-1"></i>@lang('Delete')
            </button>
          @else
            <p class="text-muted small">@lang('Fill in all required fields and click Create to add a new record.')</p>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Delete Modal -->
@if(isset(\$item))
  <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">@lang('Confirm Delete')</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>@lang('Are you sure you want to delete this record? This action cannot be undone.')</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('Cancel')</button>
          <form method="POST" action="/{$labelKebab}/{{ \$item->id }}" class="d-inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">@lang('Delete')</button>
          </form>
        </div>
      </div>
    </div>
  </div>
@endif

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
      form.addEventListener('submit', function(e) {
        if (!form.checkValidity()) {
          e.preventDefault();
          e.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });
  });
</script>
@endpush
@endsection
BLADE;

        return $template;
    }

    private function generateBooleanField(string $col): string
    {
        return <<<HTML
<div class="form-check form-check-inline">
  <input class="form-check-input" type="radio" id="{$col}_1" name="$col" value="1"
    @if((isset(\$item) && \$item->$col == 1) || old('$col') == 1) checked @endif>
  <label class="form-check-label" for="{$col}_1">@lang('Yes')</label>
</div>
<div class="form-check form-check-inline">
  <input class="form-check-input" type="radio" id="{$col}_0" name="$col" value="0"
    @if((isset(\$item) && \$item->$col == 0) || old('$col') == 0) checked @endif>
  <label class="form-check-label" for="{$col}_0">@lang('No')</label>
</div>
HTML;
    }

    private function generateTextareaField(string $col): string
    {
        return <<<HTML
<textarea class="form-control" id="$col" name="$col" rows="4" required @if(isset(\$isView)) disabled @endif>{{ isset(\$item) ? \$item->$col : old('$col') }}</textarea>
HTML;
    }

    private function generateDateField(string $col): string
    {
        return <<<HTML
<input type="date" class="form-control" id="$col" name="$col" value="{{ isset(\$item) ? \$item->$col : old('$col') }}" required @if(isset(\$isView)) disabled @endif />
HTML;
    }

    private function generateDatetimeField(string $col): string
    {
        return <<<HTML
<input type="datetime-local" class="form-control" id="$col" name="$col" value="{{ isset(\$item) ? \$item->$col : old('$col') }}" required @if(isset(\$isView)) disabled @endif />
HTML;
    }

    private function generateNumberField(string $col): string
    {
        return <<<HTML
<input type="number" class="form-control" id="$col" name="$col" value="{{ isset(\$item) ? \$item->$col : old('$col') }}" required @if(isset(\$isView)) disabled @endif />
HTML;
    }

    private function generateDecimalField(string $col): string
    {
        return <<<HTML
<input type="number" step="0.01" class="form-control" id="$col" name="$col" value="{{ isset(\$item) ? \$item->$col : old('$col') }}" required @if(isset(\$isView)) disabled @endif />
HTML;
    }

    private function generateTextField(string $col): string
    {
        return <<<HTML
<input type="text" class="form-control" id="$col" name="$col" value="{{ isset(\$item) ? \$item->$col : old('$col') }}" required @if(isset(\$isView)) disabled @endif />
HTML;
    }
}
