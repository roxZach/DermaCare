<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Service;
use Illuminate\Http\Request;

/** Manage Services & Fees. */
class ServiceController extends Controller
{
    public function index()
    {
        return view('admin.services.index', ['services' => Service::orderBy('category')->orderBy('name')->get()]);
    }

    public function create()
    {
        return view('admin.services.form', ['service' => new Service(['is_active' => true])]);
    }

    public function store(Request $request)
    {
        $s = Service::create($this->validated($request));
        AuditLog::record('service_created', $s->name);
        return redirect()->route('admin.services.index')->with('success', 'Service added.');
    }

    public function edit(Service $service)
    {
        return view('admin.services.form', compact('service'));
    }

    public function update(Request $request, Service $service)
    {
        $service->update($this->validated($request));
        AuditLog::record('service_updated', $service->name);
        return redirect()->route('admin.services.index')->with('success', 'Service updated.');
    }

    public function destroy(Service $service)
    {
        AuditLog::record('service_deleted', $service->name);
        $service->delete();
        return back()->with('success', 'Service deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:150',
            'category' => 'required|string|max:50',
            'description' => 'nullable|string|max:2000',
            'price' => 'required|numeric|min:0',
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
