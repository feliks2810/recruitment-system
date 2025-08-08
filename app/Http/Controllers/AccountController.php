<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

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
        $users = User::orderBy('created_at', 'desc')->paginate(10);
        
        $stats = [
            'total' => User::count(),
            'admin' => User::where('role', 'admin')->count(),
            'team_hc' => User::where('role', 'team_hc')->count(),
            'department' => User::where('role', 'department')->count(),
            'user' => User::where('role', 'user')->count(),
            'active' => User::where('status', true)->count(),
        ];
        
        return view('accounts.index', compact('users', 'stats'));
    }

    public function create()
    {
        return view('accounts.create', ['departments' => $this->departments]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,team_hc,department,user',
            'department' => 'required_if:role,department,admin|' . Rule::in($this->departments),
            'status' => 'required|boolean',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'department' => in_array($request->role, ['department', 'admin']) ? $request->department : null,
            'status' => $request->status,
            'email_verified_at' => now(), // Auto verify for admin created accounts
        ]);

        return redirect()->route('accounts.index')
                        ->with('success', 'Akun berhasil dibuat.');
    }

    public function edit(User $account)
    {
        return view('accounts.edit', [
            'account' => $account,
            'departments' => $this->departments
        ]);
    }

    public function update(Request $request, User $account)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($account->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|in:admin,team_hc,department,user',
            'department' => 'required_if:role,department|' . Rule::in($this->departments),
            'status' => 'required|boolean',
        ]);

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'department' => $request->role === 'department' ? $request->department : null,
            'status' => $request->status,
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $account->update($updateData);

        return redirect()->route('accounts.index')
                        ->with('success', 'Akun berhasil diperbarui.');
    }

    public function destroy(User $account)
    {
        // Prevent deleting the last admin
        if ($account->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
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