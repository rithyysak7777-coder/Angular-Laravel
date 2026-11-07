<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $like = config('database.default') === 'pgsql' ? 'ILIKE' : 'LIKE';
        $query = User::with(['role', 'orders']);

        if ($request->filled('role')) {
            $role = $request->get('role');
            $query->whereHas('role', fn ($q) => $q->where('name', $role));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search, $like) {
                $q->where('fullName', $like, "%{$search}%")
                  ->orWhere('email', $like, "%{$search}%")
                  ->orWhere('phone_number', $like, "%{$search}%");
            });
        }

        $users = $query->orderBy('id', 'asc')->get();

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'count'   => $users->count(),
                'data'    => $users,
            ]);
        }

        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = Role::all();

        return view('users.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Resolve Full Name
        $fullName = trim((string) (
            $request->input('fullName') ??
            $request->input('full_name') ??
            $request->input('name') ??
            trim(($request->input('first_name', '') . ' ' . $request->input('last_name', '')))
        ));
        if (empty($fullName)) {
            $fullName = 'Customer';
        }

        // 2. Resolve Role
        $roleId = $request->input('role_id');
        if (!$roleId && $request->filled('role')) {
            $roleName = strtolower(trim($request->input('role')));
            $role = Role::firstOrCreate(['name' => $roleName], ['description' => ucfirst($roleName) . ' Role']);
            $roleId = $role->id;
        }
        if (!$roleId) {
            $customerRole = Role::firstOrCreate(['name' => 'customer'], ['description' => 'Retail Customer']);
            $roleId = $customerRole->id;
        }

        // 3. Resolve Phone & Status
        $phone = $request->input('phone_number') ?? $request->input('phone');
        $isActive = $request->has('status') 
            ? strtoupper((string) $request->input('status')) === 'ACTIVE'
            : $request->boolean('is_active', true);

        // 4. Resolve Password
        $password = $request->input('password', 'password123');

        $email = strtolower(trim((string) $request->input('email', '')));
        if (empty($email)) {
            return response()->json([
                'success' => false,
                'message' => 'The email field is required.',
            ], 422);
        }

        // Check if email already exists
        if (User::where('email', $email)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'A user with this email address already exists.',
            ], 422);
        }

        $user = User::create([
            'role_id'       => $roleId,
            'fullName'      => $fullName,
            'email'         => $email,
            'password_hash' => Hash::make($password),
            'phone_number'  => $phone,
            'is_active'     => $isActive,
        ]);

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Customer created successfully',
                'data'    => $user->load('role'),
            ], 201);
        }

        return redirect()->route('users.index')->with('success', 'Customer created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id = null)
    {
        $targetId = $request->route('user') ?? $request->route('id') ?? $id;
        $user = $targetId instanceof User ? $targetId : User::with(['role', 'orders'])->findOrFail($targetId);

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data'    => $user,
            ]);
        }

        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id = null)
    {
        $targetId = request()->route('user') ?? request()->route('id') ?? $id;
        $user = $targetId instanceof User ? $targetId : User::with('role')->findOrFail($targetId);
        $roles = Role::all();

        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id = null)
    {
        $targetId = $request->route('user') ?? $request->route('id') ?? $id;
        $user = $targetId instanceof User ? $targetId : User::findOrFail($targetId);

        $updateData = [];

        // Name handling
        $newName = $request->input('fullName') ?? $request->input('full_name') ?? $request->input('name');
        if ($newName) {
            $updateData['fullName'] = trim((string) $newName);
        } elseif ($request->filled('first_name') || $request->filled('last_name')) {
            $updateData['fullName'] = trim($request->input('first_name', '') . ' ' . $request->input('last_name', ''));
        }

        // Email
        if ($request->filled('email')) {
            $newEmail = strtolower(trim($request->input('email')));
            if ($newEmail !== $user->email && User::where('email', $newEmail)->where('id', '!=', $user->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email address is already in use by another user.',
                ], 422);
            }
            $updateData['email'] = $newEmail;
        }

        // Role
        if ($request->filled('role_id')) {
            $updateData['role_id'] = (int) $request->input('role_id');
        } elseif ($request->filled('role')) {
            $roleName = strtolower(trim($request->input('role')));
            $role = Role::firstOrCreate(['name' => $roleName], ['description' => ucfirst($roleName) . ' Role']);
            $updateData['role_id'] = $role->id;
        }

        // Phone
        if ($request->has('phone_number')) {
            $updateData['phone_number'] = $request->input('phone_number');
        } elseif ($request->has('phone')) {
            $updateData['phone_number'] = $request->input('phone');
        }

        // Status
        if ($request->has('status')) {
            $updateData['is_active'] = strtoupper((string) $request->input('status')) === 'ACTIVE';
        } elseif ($request->has('is_active')) {
            $updateData['is_active'] = $request->boolean('is_active');
        }

        // Password
        if ($request->filled('password')) {
            $updateData['password_hash'] = Hash::make($request->input('password'));
        }

        if (!empty($updateData)) {
            $user->update($updateData);
        }

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Customer updated successfully',
                'data'    => $user->fresh('role'),
            ]);
        }

        return redirect()->route('users.index')->with('success', 'Customer updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id = null)
    {
        $targetId = $request->route('user') ?? $request->route('id') ?? $id;
        $user = $targetId instanceof User ? $targetId : User::find($targetId);

        if (!$user) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Customer already removed or not found',
                ]);
            }
            return redirect()->route('users.index')->with('success', 'Customer deleted successfully');
        }

        DB::transaction(function () use ($user) {
            // 1. Delete cart items
            $user->cartItems()->delete();

            // 2. Cascade delete orders and their items/payments
            foreach ($user->orders as $order) {
                $order->items()->delete();
                $order->payments()->delete();
                $order->delete();
            }

            // 3. Delete user record
            $user->delete();
        });

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Customer deleted successfully',
            ]);
        }

        return redirect()->route('users.index')->with('success', 'Customer deleted successfully');
    }

    /**
     * Search users by name, email, or phone.
     */
    public function search(Request $request)
    {
        $like = config('database.default') === 'pgsql' ? 'ILIKE' : 'LIKE';
        $query = trim((string) $request->get('query', $request->get('q', '')));

        $users = User::with('role')
            ->when($query !== '', function ($q) use ($query, $like) {
                $q->where(function ($sub) use ($query, $like) {
                    $sub->where('first_name', $like, "%{$query}%")
                        ->orWhere('last_name', $like, "%{$query}%")
                        ->orWhere('email', $like, "%{$query}%")
                        ->orWhere('phone_number', $like, "%{$query}%");
                });
            })
            ->get();

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'query'   => $query,
                'count'   => $users->count(),
                'data'    => $users,
            ]);
        }

        return view('users.search', compact('users', 'query'));
    }
}

