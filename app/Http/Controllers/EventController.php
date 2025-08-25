<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class EventController extends Controller
{
    /**
     * Store a newly created event in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'date' => 'required|date',
                'time' => 'nullable|string',
                'description' => 'nullable|string|max:1000',
                'location' => 'nullable|string|max:255',
            ], [
                'title.required' => 'Judul event wajib diisi.',
                'title.max' => 'Judul event maksimal 255 karakter.',
                'date.required' => 'Tanggal event wajib diisi.',
                'date.date' => 'Format tanggal tidak valid.',
                'description.max' => 'Deskripsi maksimal 1000 karakter.',
                'location.max' => 'Lokasi maksimal 255 karakter.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data yang dimasukkan tidak valid.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $event = Event::create([
                'title' => $request->title,
                'date' => $request->date,
                'time' => $request->time,
                'description' => $request->description,
                'location' => $request->location,
                'created_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Event berhasil ditambahkan!',
                'data' => $event->load('creator')
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating event: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan event. Silakan coba lagi.'
            ], 500);
        }
    }

    /**
     * Display the specified event.
     */
    public function show(Event $event)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $event->load('creator')
            ]);
        } catch (\Exception $e) {
            Log::error('Error showing event: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Event tidak ditemukan.'
            ], 404);
        }
    }

    /**
     * Update the specified event in storage.
     */
    public function update(Request $request, Event $event)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'date' => 'required|date',
                'time' => 'nullable|string',
                'description' => 'nullable|string|max:1000',
                'location' => 'nullable|string|max:255',
            ], [
                'title.required' => 'Judul event wajib diisi.',
                'title.max' => 'Judul event maksimal 255 karakter.',
                'date.required' => 'Tanggal event wajib diisi.',
                'date.date' => 'Format tanggal tidak valid.',
                'description.max' => 'Deskripsi maksimal 1000 karakter.',
                'location.max' => 'Lokasi maksimal 255 karakter.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data yang dimasukkan tidak valid.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $event->update([
                'title' => $request->title,
                'date' => $request->date,
                'time' => $request->time,
                'description' => $request->description,
                'location' => $request->location,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Event berhasil diperbarui!',
                'data' => $event->load('creator')
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating event: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui event.'
            ], 500);
        }
    }

    /**
     * Remove the specified event from storage.
     */
    public function destroy(Event $event)
    {
        try {
            $event->delete();

            return response()->json([
                'success' => true,
                'message' => 'Event berhasil dihapus!'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting event: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus event.'
            ], 500);
        }
    }

    /**
     * Get events for a specific date range.
     */
    public function getEventsByDateRange(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parameter tanggal tidak valid.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            $events = Event::with('creator')
                ->when($startDate, function ($query, $startDate) {
                    return $query->where('date', '>=', $startDate);
                })
                ->when($endDate, function ($query, $endDate) {
                    return $query->where('date', '<=', $endDate);
                })
                ->orderBy('date', 'asc')
                ->orderBy('time', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $events,
                'count' => $events->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching events by date range: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data event.'
            ], 500);
        }
    }

    /**
     * Get events for today.
     */
    public function getTodayEvents()
    {
        try {
            $today = now()->toDateString();
            
            $events = Event::with('creator')
                ->whereDate('date', $today)
                ->orderBy('time', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $events,
                'count' => $events->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching today events: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data event hari ini.'
            ], 500);
        }
    }

    /**
     * Get upcoming events (next 7 days).
     */
    public function getUpcomingEvents()
    {
        try {
            $today = now()->toDateString();
            $nextWeek = now()->addDays(7)->toDateString();
            
            $events = Event::with('creator')
                ->whereBetween('date', [$today, $nextWeek])
                ->orderBy('date', 'asc')
                ->orderBy('time', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $events,
                'count' => $events->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching upcoming events: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data event mendatang.'
            ], 500);
        }
    }

    public function getCalendarEvents(Request $request)
    {
        $user = Auth::user();

        // 1. Get custom events
        $customEvents = Event::all()->map(function ($event) {
            return [
                'id' => 'custom_'.$event->id,
                'title' => $event->title,
                'date' => $event->date->format('Y-m-d'),
                'description' => $event->description,
                'is_custom' => true,
                'url' => '#'
            ];
        });

        // 2. Get candidate next test date events
        $candidateTestEvents = collect();
        $candidatesWithUpcomingTestsQuery = Candidate::whereNotNull('next_test_date');

        // Apply department filter if user has 'department' role
        if ($user->hasRole('department') && $user->department_id) {
            $candidatesWithUpcomingTestsQuery->where('department_id', $user->department_id);
        }

        $candidatesWithUpcomingTests = $candidatesWithUpcomingTestsQuery->get();

        foreach ($candidatesWithUpcomingTests as $candidate) {
            if ($candidate->next_test_date && $candidate->current_stage_display) {
                $candidateTestEvents->push([
                    'id' => 'candidate_'.$candidate->id,
                    'title' => $candidate->nama . ' - ' . $candidate->current_stage_display,
                    'date' => $candidate->next_test_date->format('Y-m-d'),
                    'description' => 'Tes Selanjutnya: ' . $candidate->current_stage_display,
                    'is_custom' => false,
                    'url' => route('candidates.show', $candidate->id)
                ]);
            }
        }
        
        $allEvents = $customEvents->merge($candidateTestEvents);

        return response()->json($allEvents);
    }
}
