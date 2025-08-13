<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;

class AccountController extends Controller
{
    protected $departments = [
        'Batam Production',
        'Batam QA & QC',
        'Engineering',
        'Finance & Accounting',
        'HCGAESRIT',
        'MDRM Legal & Communication Function',
        'Procurement & Subcontractor',
        'Production Control',
        'PE & Facility',
        'Warehouse & Inventory'
    ];

    public function index()
    {
        $users = User::with('roles')->orderBy('created_at', 'desc')->paginate(10);
        
        $stats = [
            'total' => User::count(),
            'admin' => User::role('admin')->count(),
            'team_hc' => User::role('team_hc')->count(),
            'department' => User::role('department')->count(),
            'user' => User::role('user')->count(),
            'active' => User::where('status', true)->count(),
        ];
        
        return view('accounts.index', compact('users', 'stats'));
    }

    public function create()
    {
        $roles = Role::all();
        return view('accounts.create', [
            'departments' => $this->departments,
            'roles' => $roles
        ]);
    }

    public function store(Request $request)
    {
        $validationRules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|exists:roles,name',
            'status' => 'required|boolean',
        ];

        // Hanya tambahkan validasi department jika role adalah department
        if ($request->role === 'department') {
            $validationRules['department'] = 'required|' . Rule::in($this->departments);
        }

        $request->validate($validationRules);

        // Buat user baru
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'department' => $request->role === 'department' ? $request->department : null,
            'status' => (bool) $request->status,
            'email_verified_at' => now(),
        ]);

        // Assign role menggunakan Spatie Permission
        $user->assignRole($request->role);

        return redirect()->route('accounts.index')
                        ->with('success', 'Akun berhasil dibuat.');
    }

    public function edit(User $account)
    {
        $roles = Role::all();
        $account->load('roles');
        
        return view('accounts.edit', [
            'account' => $account,
            'departments' => $this->departments,
            'roles' => $roles
        ]);
    }

    public function update(Request $request, User $account)
    {
        $validationRules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($account->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|exists:roles,name',
            'status' => 'required|boolean',
        ];

        // Hanya tambahkan validasi department jika role adalah department
        if ($request->role === 'department') {
            $validationRules['department'] = 'required|' . Rule::in($this->departments);
        }

        $request->validate($validationRules);

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'department' => $request->role === 'department' ? $request->department : null,
            'status' => (bool) $request->status,
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $account->update($updateData);

        // Update role menggunakan Spatie Permission
        $account->syncRoles([$request->role]);

        return redirect()->route('accounts.index')
                        ->with('success', 'Akun berhasil diperbarui.');
    }

    public function destroy(User $account)
    {
        // Prevent deleting the last admin
        if ($account->hasRole('admin') && User::role('admin')->count() <= 1) {
            return redirect()->route('accounts.index')
                            ->with('error', 'Tidak dapat menghapus admin terakhir.');
        }

        // Prevent self-deletion
        if ($account->id === Auth::user()->id) {
            return redirect()->route('accounts.index')
                            ->with('error', 'Tidak dapat menghapus akun sendiri.');
        }

        $account->delete();

        return redirect()->route('accounts.index')
                        ->with('success', 'Akun berhasil dihapus.');
    }

    public function export()
    {
        return Excel::download(new \App\Exports\UsersExport, 'users_' . now()->format('Ymd_His') . '.xlsx');
    }
}