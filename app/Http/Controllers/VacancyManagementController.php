<?php

namespace App\Http\Controllers;

use App\Models\Vacancy;
use App\Models\Department;
use Illuminate\Http\Request;

class VacancyManagementController extends Controller
{
    public function index()
    {
        $vacancies = Vacancy::with('department')->orderBy('name')->get();
        return view('vacancies.index', compact('vacancies'));
    }

    public function create()
    {
        $departments = Department::orderBy('name')->get();
        return view('vacancies.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:vacancies,name',
            'department_id' => 'required|exists:departments,id',
            'needed_count' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        Vacancy::create([
            'name' => $request->name,
            'department_id' => $request->department_id,
            'needed_count' => $request->needed_count,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('vacancies.index')->with('success', 'Posisi berhasil ditambahkan.');
    }

    public function edit(Vacancy $vacancy)
    {
        $departments = Department::orderBy('name')->get();
        return view('vacancies.edit', compact('vacancy', 'departments'));
    }

    public function update(Request $request, Vacancy $vacancy)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:vacancies,name,' . $vacancy->id,
            'department_id' => 'required|exists:departments,id',
            'needed_count' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $vacancy->update([
            'name' => $request->name,
            'department_id' => $request->department_id,
            'needed_count' => $request->needed_count,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('vacancies.index')->with('success', 'Posisi berhasil diperbarui.');
    }

    public function destroy(Vacancy $vacancy)
    {
        $vacancy->delete();
        return redirect()->route('vacancies.index')->with('success', 'Posisi berhasil dihapus.');
    }
}
