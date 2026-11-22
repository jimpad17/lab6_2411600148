<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.strip_tags((string) $request->input('search')).'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)->orWhere('email', 'like', $term);
                });
            })
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->input('role')))
            ->when($request->input('status') === 'active', fn ($q) => $q->active())
            ->when($request->input('status') === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }

    public function create()
    {
        return view('users.create', ['user' => new User()]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['email_verified_at'] = now();

        $user = User::create($data);
        ActivityLogger::log('created', 'Users', "Created {$user->role} account '{$user->name}'.");

        return redirect()->route('users.index')->with('success', "Account for '{$user->name}' was created.");
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        if ($user->isProtectedAdmin() && ! $request->boolean('is_active')) {
            return back()->with('error', 'The default ADMIN account cannot be deactivated.');
        }

        $data = $this->validateData($request, $user);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $data['is_active'] = $request->boolean('is_active');
        $user->update($data);

        ActivityLogger::log('updated', 'Users', "Updated account '{$user->name}'.");

        return redirect()->route('users.index')->with('success', "Account for '{$user->name}' was updated.");
    }

    /**
     * Accounts are deactivated, never removed.
     */
    public function destroy(User $user)
    {
        if ($user->isProtectedAdmin()) {
            return back()->with('error', 'The default ADMIN account cannot be deactivated.');
        }

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        if ($user->isAdmin() && User::where('role', 'admin')->where('is_active', true)->count() <= 1) {
            return back()->with('error', 'At least one active admin account must remain.');
        }

        $user->update(['is_active' => false]);
        ActivityLogger::log('deactivated', 'Users', "Deactivated account '{$user->name}'.");

        return redirect()->route('users.index')
            ->with('success', "Account '{$user->name}' is now inactive. No records were deleted.");
    }

    public function restore(User $user)
    {
        $user->update(['is_active' => true]);
        ActivityLogger::log('restored', 'Users', "Restored account '{$user->name}'.");

        return redirect()->route('users.index')->with('success', "Account '{$user->name}' is active again.");
    }

    private function validateData(Request $request, ?User $user = null): array
    {
        $uniqueEmail = Rule::unique('users', 'email');
        if ($user) {
            $uniqueEmail->ignore($user->id);
        }

        $passwordRules = $user
            ? ['nullable', 'string', 'min:8', 'confirmed']
            : ['required', 'string', 'min:8', 'confirmed'];

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', $uniqueEmail],
            'role' => ['required', Rule::in(['admin', 'staff'])],
            'password' => $passwordRules,
        ], [
            'name.required' => 'Name is required.',
            'email.required' => 'Email is required.',
            'email.unique' => 'This email is already registered.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'role.required' => 'Please choose a role.',
        ]);

        $data['name'] = strip_tags(trim($data['name']));
        $data['email'] = strtolower(trim($data['email']));

        return $data;
    }
}
