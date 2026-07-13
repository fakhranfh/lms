# DataTables Integration - Quick Start Example

> **Superseded:** this walkthrough describes the earlier jQuery/DataTables.js + Controller + `resources/views/app/{model}` generator output. The current `make:rsc` generates Livewire components (`app/Livewire/{Plural}`) and views under `resources/views/livewire/{plural-kebab}`, with the index page rendered via `<x-livewire-data-table>` — no `/data/list` AJAX endpoint or CDN DataTables.js. Kept below for historical reference; see [`CRUD_GENERATOR_SHOWCASE.md`](CRUD_GENERATOR_SHOWCASE.md) for current behavior.

Contoh praktis implementasi DataTables dengan CRUD Generator.

## Step-by-Step Implementation

### 1. Generate CRUD dengan make:rsc

```bash
php artisan make:rsc Product --label="Product"
```

Ketika diminta konfigurasi kolom, masukkan:

```
Column name: title
Column type: string
String length: 255
Nullable: No
Default value: No
Index: Yes
Unique: No
Input type: text
Confirm: Yes

Column name: price
Column type: decimal
Precision: 10
Scale: 2
Nullable: No
Default value: No
Input type: number
Confirm: Yes

Column name: description
Column type: text
Nullable: Yes
Input type: textarea
Confirm: Yes

Done
```

### 2. Hasil Generator

Command akan membuat struktur berikut:

```
app/
├── Models/
│   └── Product.php
├── Repositories/
│   └── Product/
│       ├── ProductRepository.php
│       └── ProductRepositoryInterface.php
├── Services/
│   └── ProductService.php
├── Http/
│   ├── Controllers/
│   │   └── ProductController.php
│   └── Requests/
│       └── Product/
│           ├── StoreProductRequest.php
│           └── UpdateProductRequest.php

resources/views/app/product/
├── index.blade.php
├── create.blade.php
├── edit.blade.php
└── show.blade.php
```

### 3. Routes yang Dibuat

Generated routes:

```
GET       /product              product.index      (show list page)
GET       /product/data/list    product.list       (API endpoint for DataTables)
GET       /product/create       product.create     (show create form)
POST      /product              product.store      (save new item)
GET       /product/{id}         product.show       (show detail)
GET       /product/{id}/edit    product.edit       (show edit form)
PUT/PATCH /product/{id}         product.update     (update item)
DELETE    /product/{id}         product.destroy    (delete item)
```

### 4. DataTables di Index Page

File `resources/views/app/product/index.blade.php` sudah otomatis menggunakan DataTables dengan:

**Features:**
- ✅ AJAX loading data dari `/product/data/list`
- ✅ Sorting by clicking column headers
- ✅ Search/filter across all columns
- ✅ Pagination (default 10 per page)
- ✅ Action buttons (View, Edit, Delete)

### 5. API Response Format

Endpoint `/product/data/list` mengembalikan:

```json
{
  "data": [
    {
      "id": 1,
      "name": "Product Name",
      "created_at": "2026-06-26 10:30:45",
      "actions": {
        "show": "/product/1",
        "edit": "/product/1/edit",
        "delete": "/product/1"
      }
    },
    {
      "id": 2,
      "name": "Another Product",
      "created_at": "2026-06-26 11:15:30",
      "actions": {
        "show": "/product/2",
        "edit": "/product/2/edit",
        "delete": "/product/2"
      }
    }
  ]
}
```

## Custom Implementation untuk Model yang Sudah Ada

Jika ingin menambahkan DataTables ke model yang sudah ada (bukan via make:rsc), ikuti langkah ini:

### 1. Tambah Method `list()` ke Controller

File: `app/Http/Controllers/ProductController.php`

```php
public function list(Request $request)
{
    $items = $this->productService->getAll();

    return response()->json([
        'data' => $items->map(fn($item) => [
            'id' => $item->id,
            'name' => $item->name ?? '',
            'title' => $item->title ?? '',
            'price' => $item->price ?? '',
            'created_at' => $item->created_at?->format('Y-m-d H:i:s') ?? '',
            'actions' => [
                'show' => route('product.show', $item->id),
                'edit' => route('product.edit', $item->id),
                'delete' => route('product.destroy', $item->id),
            ]
        ])->toArray()
    ]);
}
```

### 2. Tambah API Route

File: `routes/web.php`

```php
Route::middleware(['auth'])->group(function () {
    Route::resource('product', ProductController::class);
    Route::get('product/data/list', [ProductController::class, 'list'])->name('product.list');
});
```

**Important**: Pastikan route API endpoint didefinisikan **sebelum** route resource.

### 3. Update Index View

File: `resources/views/app/product/index.blade.php`

Ganti bagian table dan pagination dengan template DataTables dari dokumentasi.

## Customization Examples

### Contoh 1: Tambah Kolom Status

**Step 1**: Update API response di controller

```php
public function list(Request $request)
{
    $items = $this->productService->getAll();

    return response()->json([
        'data' => $items->map(fn($item) => [
            'id' => $item->id,
            'name' => $item->name ?? '',
            'status' => $item->status ?? 'active',  // Tambah ini
            'created_at' => $item->created_at?->format('Y-m-d H:i:s') ?? '',
            'actions' => [
                'show' => route('product.show', $item->id),
                'edit' => route('product.edit', $item->id),
                'delete' => route('product.destroy', $item->id),
            ]
        ])->toArray()
    ]);
}
```

**Step 2**: Tambah kolom di columns config

```javascript
columns: [
    { data: 'id', title: '{{ __("ID") }}' },
    { data: 'name', title: '{{ __("Name") }}' },
    { 
        data: 'status', 
        title: '{{ __("Status") }}',
        render: function(data) {
            const statusClass = data === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
            return `<span class="px-2 py-1 rounded text-sm font-medium ${statusClass}">${data}</span>`;
        }
    },
    { data: 'created_at', title: '{{ __("Created") }}' },
    // Actions column...
]
```

### Contoh 2: Format Harga/Currency

```javascript
{ 
    data: 'price', 
    title: '{{ __("Price") }}',
    render: function(data) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR'
        }).format(data);
    }
}
```

### Contoh 3: Ubah Jumlah Rows per Halaman

```javascript
new DataTable('#product-table', {
    // ... config lainnya
    pageLength: 25,  // Default 25 rows per page
    lengthMenu: [10, 25, 50, 100],  // Opsi dropdown
    // ...
});
```

## Testing

### Test DataTables via Browser

1. Start development server:
   ```bash
   php artisan serve
   ```

2. Navigate ke `/product` (atau route index yang dibuat)

3. Verifikasi:
   - ✅ Table tampil dengan data
   - ✅ Sorting works (klik header)
   - ✅ Search works (ketik di search box)
   - ✅ Pagination works
   - ✅ Action buttons work

### Test API Endpoint

```bash
curl -X GET "http://localhost:8000/product/data/list" \
  -H "Accept: application/json" \
  -H "X-Requested-With: XMLHttpRequest"
```

Harusnya return JSON dengan struktur `{ "data": [...] }`

## Common Issues & Solutions

### Issue: "DataTable is not defined"

**Cause**: DataTables library belum diload

**Fix**: Pastikan `<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>` ada di blade template

### Issue: No data tampil di table

**Cause**: API endpoint error atau structure response berbeda

**Fix**: 
1. Check browser console untuk errors
2. Check network tab untuk API response
3. Verifikasi response structure sesuai dengan columns config

### Issue: Delete button tidak bekerja

**Cause**: CSRF token missing atau route DELETE tidak ada

**Fix**:
1. Add meta tag di master layout: `<meta name="csrf-token" content="{{ csrf_token() }}">`
2. Verifikasi route DELETE di routes/web.php
3. Check browser console untuk fetch errors

## Performance Tips

1. **Large Datasets**: Gunakan server-side pagination (lihat dokumentasi lanjutan)
2. **Caching**: Cache response API dengan Redis
3. **Indexing**: Add database indexes ke columns yang sering di-search/sort
4. **Lazy Loading**: Load DataTables JavaScript hanya di halaman index yang perlu

## Next Steps

- Baca dokumentasi lengkap: [DATATABLE_INTEGRATION.md](./DATATABLE_INTEGRATION.md)
- Cek [DataTables Official Docs](https://datatables.net/)
- Implementasikan server-side pagination untuk large datasets
