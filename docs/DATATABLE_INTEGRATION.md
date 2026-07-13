# DataTables Integration Guide

> **Superseded:** this document describes the earlier jQuery/DataTables.js + Controller integration. `make:rsc` now generates a Livewire-based index page using the `<x-livewire-data-table>` component (`resources/views/components/livewire-data-table.blade.php`) with server-rendered `wire:click` sorting instead of a client-side AJAX table — there is no CDN DataTables.js dependency, no `/route/data/list` JSON endpoint, and no Controller `list()` method. Kept below for historical reference.

Panduan lengkap tentang integrasi DataTables ke halaman index yang dihasilkan oleh CRUD Generator.

## Fitur

Integrasi DataTables ini memberikan fitur berikut untuk halaman index:

- ✅ **Server-side AJAX Loading** - Data dimuat via API endpoint yang dedicated
- ✅ **Sorting** - Klik header kolom untuk mengurutkan data
- ✅ **Searching** - Pencarian global across semua kolom
- ✅ **Pagination** - Pagination otomatis dengan opsi untuk mengubah jumlah baris per halaman
- ✅ **Responsive Actions** - Tombol Edit, View, dan Delete dengan styling yang konsisten
- ✅ **Dark Mode Support** - Kompatibel dengan tema dark yang sudah ada

## Cara Kerja

### 1. API Endpoint

Setiap CRUD generator membuat endpoint API untuk list data:

```
GET /route-name/data/list
```

Named route: `{route-name}.list`

Endpoint ini mengembalikan JSON response:

```json
{
  "data": [
    {
      "id": 1,
      "name": "Item Name",
      "created_at": "2026-06-26 10:30:45",
      "actions": {
        "show": "/route-name/1",
        "edit": "/route-name/1/edit",
        "delete": "/route-name/1"
      }
    },
    ...
  ]
}
```

### 2. Frontend Implementation

Template index view menggunakan DataTables.js dengan konfigurasi berikut:

- **Ajax**: Mengambil data dari endpoint `/route-name/data/list`
- **Columns**: Dikonfigurasi untuk menampilkan ID, Name, Created At, dan Actions
- **Pagination**: Default 10 baris per halaman (customizable)
- **Sorting**: Default sorting by ID descending

### 3. Delete Action

Tombol delete menggunakan fetch API dengan:
- CSRF token validation
- DELETE HTTP method
- Confirmation dialog sebelum delete
- Auto-reload halaman setelah sukses

## Customization

### Mengubah Kolom yang Ditampilkan

Edit file `resources/views/app/{route-name}/index.blade.php` dan modifikasi section `@push('scripts')`:

```javascript
columns: [
    { data: 'id', title: '{{ __("ID") }}' },
    { data: 'name', title: '{{ __("Name") }}' },
    { data: 'created_at', title: '{{ __("Created") }}' },
    // Tambah kolom baru di sini
    { data: 'status', title: '{{ __("Status") }}' },
    {
        data: 'actions',
        title: '{{ __("Actions") }}',
        orderable: false,
        searchable: false,
        render: function(data) { ... }
    }
]
```

### Mengubah Jumlah Rows per Halaman

```javascript
pageLength: 10  // Ubah ke nilai yang diinginkan (25, 50, 100, dll)
```

### Menambah Kolom di API Endpoint

Edit controller file `app/Http/Controllers/{Model}Controller.php`, method `list()`:

```php
public function list(Request $request)
{
    $items = $this->modelService->getAll();

    return response()->json([
        'data' => $items->map(fn($item) => [
            'id' => $item->id,
            'name' => $item->name ?? '',
            'status' => $item->status ?? '',  // Tambah di sini
            'created_at' => $item->created_at?->format('Y-m-d H:i:s') ?? '',
            'actions' => [
                'show' => route('route.show', $item->id),
                'edit' => route('route.edit', $item->id),
                'delete' => route('route.destroy', $item->id),
            ]
        ])->toArray()
    ]);
}
```

### Styling Actions

Edit template blade untuk mengubah styling tombol actions:

```blade
<div class="flex gap-2 justify-end">
    <a href="${data.show}" class="text-blue-600 hover:text-blue-900 text-sm font-medium">
        {{ __('View') }}
    </a>
    <a href="${data.edit}" class="text-amber-600 hover:text-amber-900 text-sm font-medium">
        {{ __('Edit') }}
    </a>
    <button onclick="deleteItem('${data.delete}')" class="text-red-600 hover:text-red-900 text-sm font-medium">
        {{ __('Delete') }}
    </button>
</div>
```

## Server-Side Pagination (Advanced)

Untuk dataset yang sangat besar, implementasi di atas menggunakan client-side pagination. Untuk server-side pagination:

### 1. Update Controller

```php
public function list(Request $request)
{
    $perPage = $request->get('length', 10);
    $start = $request->get('start', 0);
    $page = ($start / $perPage) + 1;

    $items = $this->modelService->getAll();
    $paginated = $items->paginate($perPage, ['*'], 'page', $page);

    return response()->json([
        'draw' => $request->get('draw'),
        'recordsTotal' => $paginated->total(),
        'recordsFiltered' => $paginated->total(),
        'data' => $paginated->items()->map(fn($item) => [
            // ... data mapping
        ])->toArray()
    ]);
}
```

### 2. Update JavaScript

```javascript
const dataTable = new DataTable('#ROUTENAME-table', {
    ajax: {
        url: '{{ route("ROUTENAME.list") }}',
        type: 'GET',
        dataSrc: 'data'
    },
    // ... columns config
    serverSide: true,  // Aktifkan server-side processing
    processing: true
});
```

## Styling & Theming

### DataTables CSS Overrides

Tambahkan custom CSS di dalam `@push('styles')` untuk override styling default:

```blade
@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">
    <style>
        /* Custom DataTables styling */
        .dataTable tbody tr:hover {
            background-color: rgba(0, 84, 198, 0.05);
        }
        
        .dataTable_wrapper .dataTable_length select {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
        }
    </style>
@endpush
```

### Dark Mode

Styling sudah support dark mode melalui Tailwind classes yang ada di template.

## Performance Considerations

1. **Large Datasets**: Untuk dataset > 10K records, implementasikan server-side pagination
2. **Caching**: Pertimbangkan caching response API endpoint
3. **Searching**: Untuk dataset besar, implementasikan server-side searching dengan LIKE queries
4. **Indexing**: Pastikan database columns yang sering di-search dan sort memiliki index

## Troubleshooting

### DataTables tidak loading

1. Periksa browser console untuk errors
2. Pastikan endpoint API returnnya valid JSON
3. Verifikasi CSRF token ada di meta tag

### Delete tidak bekerja

1. Periksa CSRF token di meta tag
2. Verifikasi route DELETE endpoint ada
3. Pastikan method DELETE di route sudah benar

### Data tidak tampil di table

1. Periksa network tab di browser dev tools
2. Verifikasi response API structure sesuai dengan columns config
3. Cek apakah ada JavaScript errors di console

## Referensi

- [DataTables Official Docs](https://datatables.net/)
- [DataTables API Reference](https://datatables.net/reference/api/)
- [Laravel CSRF Protection](https://laravel.com/docs/13.x/csrf)
