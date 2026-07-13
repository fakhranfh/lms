<?php

namespace App\Console\Commands\Stubs;

use Illuminate\Support\Str;

class BladeIndexStubGenerator
{
    public function generate(string $name, string $label): string
    {
        $kebabCaseName = Str::kebab($name);
        $pluralTitle = Str::pluralStudly($label);
        $labelKebab = Str::kebab($label);

        return <<<BLADE
@extends('siakad/layouts/contentNavbarLayout')

@section('title', __('{$pluralTitle}'))

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-0">@lang('{$pluralTitle}')</h4>
      <small class="text-muted">@lang('Manage your {$label}')</small>
    </div>
    <a href="/{$labelKebab}/create" class="btn btn-primary">
      <i class="bx bx-plus me-2"></i>@lang('New {$label}')
    </a>
  </div>

  <!-- Alerts -->
  @include('siakad.components.success-alert')
  @include('siakad.components.error-alert')

  <!-- Table Card -->
  <div class="card">
    <div class="card-header border-bottom">
      <h6 class="card-title mb-0">@lang('All {$pluralTitle}')</h6>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive text-nowrap">
        <table class="table table-hover" id="{$kebabCaseName}-table">
          <thead class="table-light">
            <tr>
              <th>@lang('ID')</th>
              <th>@lang('Name')</th>
              <th>@lang('Status')</th>
              <th>@lang('Created')</th>
              <th>@lang('Actions')</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Pagination -->
  <div class="mt-4">
    <nav aria-label="Page navigation">
      <ul class="pagination justify-content-center">
        <li class="page-item disabled">
          <a class="page-link" href="#" tabindex="-1">@lang('Previous')</a>
        </li>
        <li class="page-item"><a class="page-link" href="#">1</a></li>
        <li class="page-item"><a class="page-link" href="#">2</a></li>
        <li class="page-item"><a class="page-link" href="#">3</a></li>
        <li class="page-item">
          <a class="page-link" href="#">@lang('Next')</a>
        </li>
      </ul>
    </nav>
  </div>
</div>

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTables
    const table = document.getElementById('{$kebabCaseName}-table');
    if (table) {
      // Add DataTables initialization here
      console.log('Table initialized');
    }
  });
</script>
@endpush
@endsection
BLADE;
    }
}
