<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = Document::query();

        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->input('search') . '%');
        }

        $documents = $query->latest()->get();

        if ($request->ajax()) {
            return response()->json(['documents' => $documents]);
        }

        $search = $request->input('search'); // Pass search term back to view

        return view('documents.index', compact('documents', 'search'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:2048',
            'title' => 'required|string|max:255',
        ]);

        $path = $request->file('file')->store('documents', 'public');

        Document::create([
            'title' => $request->title,
            'file_path' => $path,
        ]);

        return redirect()->route('documents.index');
    }
}
