<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Manage User Accounts, Assign Roles & Permissions. */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->query('role');
        $q = $request->query('q');
        $users = User::when($role, fn ($w) => $w->where('role', $role))
            ->when($q, fn ($w) => $w->where(fn ($x) => $x->where('name', 'like', "%$q%")->orWhere('email', 'like', "%$q%")))
            ->orderBy('role')->orderBy('name')->paginate(25)->withQueryString();
        return view('admin.users.index', compact('users', 'role', 'q'));
    }

    public function create()
    {
        return view('admin.users.form', ['user' => new User(['is_active' => true, 'role' => 'receptionist'])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['email_verified_at'] = now(); // accounts created by the admin do not need OTP verification
        $user = User::create($data);
        $this->syncProfile($user);
        AuditLog::record('user_created', "{$user->email} ({$user->role})");
        return redirect()->route('admin.users.index')->with('success', 'User created.');
    }

    public function edit(User $user)
    {
        return view('admin.users.form', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        if ($user->id === auth()->id() && ($data['role'] !== 'admin' || ! $data['is_active'])) {
            return back()->with('error', 'You cannot remove your own admin access.');
        }
        $old = $user->role;
        $user->update($data);
        $this->syncProfile($user);
        AuditLog::record('user_updated', "{$user->email}" . ($old !== $user->role ? " role {$old} -> {$user->role}" : ''));
        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function destroy(User $user)
    {
        abort_if($user->id === auth()->id(), 403, 'You cannot delete your own account.');
        AuditLog::record('user_deleted', $user->email);
        $user->delete();
        return back()->with('success', 'User deleted.');
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', 'max:150', Rule::unique('users')->ignore($user?->id)],
            'phone' => 'nullable|string|max:30',
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        return $data;
    }

    /** Make sure dermatologists have a doctor profile and patients have a patient record. */
    private function syncProfile(User $user): void
    {
        if ($user->role === 'dermatologist' && ! $user->doctor) {
            Doctor::create(['user_id' => $user->id]);
        }
        if ($user->role === 'patient' && ! $user->patient) {
            Patient::create(['user_id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'phone' => $user->phone]);
        }
    }
}
