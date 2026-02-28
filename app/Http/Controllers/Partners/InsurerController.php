<?php

namespace App\Http\Controllers\Partners;

use App\Http\Controllers\Controller;
use App\Models\Insurer;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InsurerController extends Controller
{
    private function garageId(): int
    {
        return (int) auth()->user()->garage_id;
    }

    private function scopedInsurerOrFail(int $id): Insurer
    {
        return Insurer::query()
            ->where('garage_id', $this->garageId())
            ->where('id', $id)
            ->firstOrFail();
    }

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $insurers = Insurer::query()
            ->where('garage_id', $this->garageId())
            ->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('partners.insurers.index', compact('insurers', 'q'));
    }

    public function create(): View
    {
        return view('partners.insurers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:150'],
            'phone'     => ['nullable', 'string', 'max:50'],
            'email'     => ['nullable', 'email', 'max:120'],
            'notes'     => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $name = trim($data['name']);

        // Prevent duplicates per garage (also enforced by DB unique index)
        $exists = Insurer::query()
            ->where('garage_id', $this->garageId())
            ->where('name', $name)
            ->exists();

        if ($exists) {
            return back()
                ->withErrors(['name' => 'This insurer already exists.'])
                ->withInput();
        }

        Insurer::create([
            'garage_id'  => $this->garageId(),
            'name'       => $name,
            'phone'      => $data['phone'] ?? null,
            'email'      => $data['email'] ?? null,
            'notes'      => $data['notes'] ?? null,
            'is_active'  => (bool) ($data['is_active'] ?? true),
        ]);

        return redirect()
            ->route('partners.insurers.index')
            ->with('status', 'Insurer added.');
    }

    public function edit(Insurer $insurer): View
    {
        // Force garage scope (ignore route-model binding if it’s not scoped)
        $insurer = $this->scopedInsurerOrFail((int) $insurer->id);

        return view('partners.insurers.edit', compact('insurer'));
    }

    public function update(Request $request, Insurer $insurer): RedirectResponse
    {
        $insurer = $this->scopedInsurerOrFail((int) $insurer->id);

        $data = $request->validate([
            'name'      => ['required', 'string', 'max:150'],
            'phone'     => ['nullable', 'string', 'max:50'],
            'email'     => ['nullable', 'email', 'max:120'],
            'notes'     => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $name = trim($data['name']);

        $dup = Insurer::query()
            ->where('garage_id', $this->garageId())
            ->where('name', $name)
            ->where('id', '!=', $insurer->id)
            ->exists();

        if ($dup) {
            return back()
                ->withErrors(['name' => 'Another insurer with this name already exists.'])
                ->withInput();
        }

        $insurer->update([
            'name'      => $name,
            'phone'     => $data['phone'] ?? null,
            'email'     => $data['email'] ?? null,
            'notes'     => $data['notes'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? $insurer->is_active),
        ]);

        return redirect()
            ->route('partners.insurers.index')
            ->with('status', 'Insurer updated.');
    }

    public function toggle(Request $request, Insurer $insurer): RedirectResponse
    {
        $insurer = $this->scopedInsurerOrFail((int) $insurer->id);

        $insurer->update([
            'is_active' => !$insurer->is_active,
        ]);

        return back()->with('status', 'Insurer status updated.');
    }

    public function storeFromPreferences(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'new_insurer_name'  => ['required', 'string', 'max:150'],
            'new_insurer_email' => ['nullable', 'email', 'max:120'],
        ]);

        $name = trim($data['new_insurer_name']);

        $exists = Insurer::query()
            ->where('garage_id', $this->garageId())
            ->where('name', $name)
            ->exists();

        if ($exists) {
            return back()
                ->withErrors(['new_insurer_name' => 'This insurer already exists.'])
                ->withInput();
        }

        Insurer::create([
            'garage_id' => $this->garageId(),
            'name'      => $name,
            'email'     => $data['new_insurer_email'] ?? null,
            'phone'     => null,
            'notes'     => null,
            'is_active' => true,
        ]);

        return back()->with('success', 'Insurer added.');
    }

    public function destroyFromPreferences(int $id): RedirectResponse
    {
        $insurer = $this->scopedInsurerOrFail($id);

        $insurer->delete();

        return back()->with('success', 'Insurer removed.');
    }

}
