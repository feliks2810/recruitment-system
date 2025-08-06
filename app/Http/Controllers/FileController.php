<?php
// app/Http/Controllers/FileController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function uploadCV(Request $request)
    {
        $request->validate([
            'cv' => 'required|file|mimes:pdf,doc,docx|max:5120', // 5MB max
        ]);

        $file = $request->file('cv');
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('cv', $filename, 'uploads');

        return response()->json([
            'success' => true,
            'filename' => $filename,
            'path' => $path
        ]);
    }

    public function uploadFLK(Request $request)
    {
        $request->validate([
            'flk' => 'required|file|mimes:pdf,doc,docx|max:5120', // 5MB max
        ]);

        $file = $request->file('flk');
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('flk', $filename, 'uploads');

        return response()->json([
            'success' => true,
            'filename' => $filename,
            'path' => $path
        ]);
    }
}