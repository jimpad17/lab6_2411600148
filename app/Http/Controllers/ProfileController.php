<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /** Staff/admin profile page: account details plus a compact product report. */
    public function index()
    {
        return view('profile.index', $this->profileData());
    }

    /** Edit name, email and profile picture. */
    public function update(Request $request)
    {
        $user = auth()->user();

        $rules = [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
        ];

        // The protected admin email is used by the login alias and protection
        // check, so it must not be changed from the profile form.
        if ($user->isProtectedAdmin()) {
            $rules['email'] = ['required', 'email', 'max:255'];
        }

        $data = $request->validate($rules, [
            'name.required' => 'Full name is required.',
            'avatar.image' => 'Profile picture must be an image.',
            'avatar.max' => 'Profile picture must not exceed 2 MB.',
        ]);

        $updates = [
            'name' => strip_tags(trim($data['name'])),
        ];

        // Re-read from the database instead of trusting the submitted value:
        // the protected admin address is part of the login alias and must
        // never change, even if a client bypasses the readonly input.
        if (! $user->isProtectedAdmin()) {
            $updates['email'] = strtolower(trim($data['email']));
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $updates['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($updates);

        ActivityLogger::log('updated', 'Profile', "Updated profile for '{$user->name}'.");

        return back()->with('success', 'Profile updated successfully.');
    }

    /** Change password only after verifying the current one. */
    public function changePassword(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Enter your current password.',
            'password.min' => 'New password must be at least 8 characters.',
            'password.confirmed' => 'The new passwords do not match.',
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Your current password is incorrect.']);
        }

        if (Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['password' => 'New password must be different from the current one.']);
        }

        $user->update(['password' => Hash::make($data['password'])]);

        ActivityLogger::log('updated', 'Profile', "Changed password for '{$user->name}'.");

        return back()->with('success', 'Password changed successfully.');
    }

    private function profileData(): array
    {
        $user = auth()->user();

        return [
            'user' => $user,
            'recentOrders' => Order::forUser($user->id)->with('product')->latest()->limit(5)->get(),
            'productCount' => Product::active()->count(),
            'lowStockCount' => Product::active()->lowStock()->count(),
        ];
    }
}
