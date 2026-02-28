{{-- resources/views/admin/organizations/create.blade.php --}}
@include('admin.organizations.form', [
    'organization' => new \App\Models\Organization(),
    'mode' => 'create',
])
