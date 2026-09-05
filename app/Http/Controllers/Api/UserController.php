<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('roles')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('role'), function ($query) use ($request) {
                $role = $request->string('role')->toString();

                $query->whereHas('roles', function ($q) use ($role) {
                    $q->where('name', $role);
                });
            })
            ->latest()
            ->paginate(15);

        $users->getCollection()->transform(
            fn (User $user) => $this->userResponse($user)
        );

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => [
                'required',
                'string',
                Rule::exists('roles', 'name'),
            ],
            'profile_photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        $user = new User();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->password = $validated['password'];

        if ($request->hasFile('profile_photo')) {
            $user->profile_photo_path = $request
                ->file('profile_photo')
                ->store('profile-photos', 'local');
        }

        $user->save();

        $user->assignRole($validated['role']);
        $user->load('roles');

        return response()->json([
            'message' => 'User created successfully.',
            'data' => $this->userResponse($user),
        ], 201);
    }

    public function show(User $user)
    {
        $user->load('roles');

        return response()->json([
            'data' => $this->userResponse($user),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => [
                'required',
                'string',
                Rule::exists('roles', 'name'),
            ],
            'profile_photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (!empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo_path) {
                Storage::disk('local')->delete($user->profile_photo_path);
            }

            $user->profile_photo_path = $request
                ->file('profile_photo')
                ->store('profile-photos', 'local');
        }

        $user->save();

        $user->syncRoles([$validated['role']]);
        $user->load('roles');

        return response()->json([
            'message' => 'User updated successfully.',
            'data' => $this->userResponse($user),
        ]);
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->id === $user->id) {
            return response()->json([
                'message' => 'You cannot delete your own account.',
            ], 422);
        }

        if ($user->profile_photo_path) {
            Storage::disk('local')->delete($user->profile_photo_path);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }

    public function photo(User $user)
    {
        abort_unless($user->profile_photo_path, 404);

        abort_unless(
            Storage::disk('local')->exists($user->profile_photo_path),
            404
        );

        return Storage::disk('local')->response(
            $user->profile_photo_path
        );
    }

    private function userResponse(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->roles
                ->pluck('name')
                ->values()
                ->all(),
            'profile_photo_url' => $user->profile_photo_path
                ? "/users/{$user->id}/photo"
                : null,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }
}