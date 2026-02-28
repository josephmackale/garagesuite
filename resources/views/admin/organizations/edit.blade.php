{{-- resources/views/admin/organizations/edit.blade.php --}}
@include('admin.organizations.form', [
    'organization' => $organization,
    'mode' => 'edit',
])
