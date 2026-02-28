<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function index()
    {
        $organizations = Organization::orderBy('name')->paginate(20);

        return view('admin.organizations.index', compact('organizations'));
    }


    public function create()
    {
        return view('admin.organizations.create');
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'type'           => 'required|in:insurance,corporate',
            'contact_person' => 'nullable|string|max:255',
            'phone'          => 'nullable|string|max:50',
            'email'          => 'nullable|email|max:255',
            'billing_terms'  => 'required|integer|min:1|max:365',
            'status'         => 'required|in:active,inactive',
        ]);

        Organization::create($data);

        return redirect()
            ->route('admin.organizations.index')
            ->with('success', 'Organization created.');
    }


    public function edit(Organization $organization)
    {
        return view('admin.organizations.edit', compact('organization'));
    }


    public function update(Request $request, Organization $organization)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'type'           => 'required|in:insurance,corporate',
            'contact_person' => 'nullable|string|max:255',
            'phone'          => 'nullable|string|max:50',
            'email'          => 'nullable|email|max:255',
            'billing_terms'  => 'required|integer|min:1|max:365',
            'status'         => 'required|in:active,inactive',
        ]);

        $organization->update($data);

        return redirect()
            ->route('admin.organizations.index')
            ->with('success', 'Organization updated.');
    }
}
