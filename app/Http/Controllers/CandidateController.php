<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Http\Requests\CandidateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel; // Untuk export (opsional)

class CandidateController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $type = $request->input('type');

        $query = Candidate::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%$search%")
                  ->orWhere('alamat_email', 'like', "%$search%")
                  ->orWhere('vacancy_airsys', 'like', "%$search%")
                  ->orWhere('applicant_id', 'like', "%$search%");
            });
        }

        if ($status) {
            $query->where('current_stage', $status);
        }

        if ($type) {
            $query->where('airsys_internal', $type === 'Organik' ? 'Yes' : 'No');
        }

        $candidates = $query->orderBy('created_at', 'desc')->paginate(10);

        $stats = [
            'total' => Candidate::count(),
            'dalam_proses' => Candidate::where('overall_status', 'DALAM PROSES')->count(),
            'pending' => Candidate::where('overall_status', 'PENDING')->count(),
            'lulus' => Candidate::where('overall_status', 'LULUS')->count(),
            'hired' => Candidate::where('overall_status', 'LULUS')->count(), // Duplikat dengan 'lulus', bisa dihapus jika tidak diperlukan
            'tidak_lulus' => Candidate::where('overall_status', 'TIDAK LULUS')->count(),
        ];

        return view('candidates.index', compact('candidates', 'stats', 'search', 'status', 'type'));
    }

    public function show($id)
    {
        $candidate = Candidate::findOrFail($id);
        $timeline = $this->generateTimeline($candidate);
        return view('candidates.show', compact('candidate', 'timeline'));
    }

    protected function generateTimeline($candidate)
    {
        return [
            [
                'stage' => 'Seleksi Berkas',
                'status' => 'completed',
                'date' => $candidate->created_at,
                'notes' => 'Berkas lengkap dan sesuai kualifikasi',
                'evaluator' => 'HR Team',
            ],
            [
                'stage' => 'Psikotes',
                'status' => $candidate->psikotes_result ? ($candidate->psikotes_result === 'LULUS' ? 'completed' : 'failed') : 'pending',
                'date' => $candidate->psikotest_date,
                'notes' => $candidate->psikotes_notes,
                'evaluator' => 'Psikolog',
                'result' => $candidate->psikotes_result,
            ],
            [
                'stage' => 'Interview HC',
                'status' => $candidate->hc_intv_status ? ($candidate->hc_intv_status === 'LULUS' ? 'completed' : ($candidate->hc_intv_status === 'PENDING' ? 'current' : 'failed')) : 'pending',
                'date' => $candidate->hc_intv_date,
                'notes' => $candidate->hc_intv_notes,
                'evaluator' => 'HC Team',
                'result' => $candidate->hc_intv_status,
            ],
            [
                'stage' => 'Interview User',
                'status' => $candidate->user_intv_status ? ($candidate->user_intv_status === 'LULUS' ? 'completed' : ($candidate->user_intv_status === 'PENDING' ? 'current' : 'failed')) : 'pending',
                'date' => $candidate->user_intv_date,
                'notes' => $candidate->itv_user_note,
                'evaluator' => 'Department Team',
                'result' => $candidate->user_intv_status,
            ],
            [
                'stage' => 'Interview BOD/GM',
                'status' => $candidate->bod_intv_status ? ($candidate->bod_intv_status === 'LULUS' ? 'completed' : ($candidate->bod_intv_status === 'PENDING' ? 'current' : 'failed')) : 'pending',
                'date' => $candidate->bod_gm_intv_date,
                'notes' => $candidate->bod_intv_note,
                'evaluator' => 'BOD/GM',
                'result' => $candidate->bod_intv_status,
            ],
            [
                'stage' => 'Offering Letter',
                'status' => $candidate->offering_letter_status ? ($candidate->offering_letter_status === 'ACCEPTED' ? 'completed' : ($candidate->offering_letter_status === 'SENT' ? 'current' : 'failed')) : 'pending',
                'date' => $candidate->offering_letter_date,
                'notes' => $candidate->offering_letter_notes,
                'evaluator' => 'HR Team',
                'result' => $candidate->offering_letter_status,
            ],
            [
                'stage' => 'Medical Check Up',
                'status' => $candidate->mcu_status ? ($candidate->mcu_status === 'LULUS' ? 'completed' : ($candidate->mcu_status === 'PENDING' ? 'current' : 'failed')) : 'pending',
                'date' => $candidate->mcu_date,
                'notes' => $candidate->mcu_note,
                'evaluator' => 'Medical Team',
                'result' => $candidate->mcu_status,
            ],
            [
                'stage' => 'Hiring',
                'status' => $candidate->hiring_status ? ($candidate->hiring_status === 'HIRED' ? 'completed' : ($candidate->hiring_status === 'PENDING' ? 'current' : 'failed')) : 'pending',
                'date' => $candidate->hiring_date,
                'notes' => $candidate->hiring_note,
                'evaluator' => 'HR Team',
                'result' => $candidate->hiring_status,
            ],
        ];
    }

    public function create(Request $request)
    {
        return view('candidates.create');
    }

    public function store(CandidateRequest $request)
    {
        $candidateData = $request->validated();
        
        if ($request->hasFile('cv')) {
            $candidateData['cv'] = $request->file('cv')->store('candidates/cv', 'public');
        }
        if ($request->hasFile('flk')) {
            $candidateData['flk'] = $request->file('flk')->store('candidates/flk', 'public');
        }

        $candidateData['current_stage'] = 'CV Review';
        $candidateData['overall_status'] = 'DALAM PROSES';
        $candidateData['no'] = Candidate::count() + 1;

        Candidate::create($candidateData);

        return redirect()->route('candidates.index')
                        ->with('success', 'Kandidat berhasil ditambahkan.');
    }

    public function edit(Candidate $candidate)
    {
        return view('candidates.edit', compact('candidate'));
    }

    public function update(CandidateRequest $request, Candidate $candidate)
    {
        $candidateData = $request->validated();
        
        if ($request->hasFile('cv')) {
            if ($candidate->cv) {
                Storage::disk('public')->delete($candidate->cv);
            }
            $candidateData['cv'] = $request->file('cv')->store('candidates/cv', 'public');
        }
        if ($request->hasFile('flk')) {
            if ($candidate->flk) {
                Storage::disk('public')->delete($candidate->flk);
            }
            $candidateData['flk'] = $request->file('flk')->store('candidates/flk', 'public');
        }

        $candidate->update($candidateData);

        return redirect()->route('candidates.index')
                        ->with('success', 'Kandidat berhasil diperbarui.');
    }

    public function destroy(Candidate $candidate)
    {
        if ($candidate->cv) {
            Storage::disk('public')->delete($candidate->cv);
        }
        if ($candidate->flk) {
            Storage::disk('public')->delete($candidate->flk);
        }

        $candidate->delete();

        return redirect()->route('candidates.index')
                        ->with('success', 'Kandidat berhasil dihapus.');
    }

    public function updateStage(Request $request, Candidate $candidate)
    {
        $request->validate([
            'stage' => 'required|string',
            'result' => 'required|in:lulus,tidak-lulus',
            'notes' => 'nullable|string',
        ]);

        $stage = $request->input('stage');
        $result = strtoupper($request->input('result') === 'lulus' ? 'LULUS' : 'TIDAK LULUS');
        $notes = $request->input('notes');

        $stageFields = [
            'Psikotes' => ['date' => 'psikotest_date', 'result' => 'psikotes_result', 'notes' => 'psikotes_notes'],
            'Interview HC' => ['date' => 'hc_intv_date', 'result' => 'hc_intv_status', 'notes' => 'hc_intv_notes'],
            'Interview User' => ['date' => 'user_intv_date', 'result' => 'user_intv_status', 'notes' => 'itv_user_note'],
            'Interview BOD/GM' => ['date' => 'bod_gm_intv_date', 'result' => 'bod_intv_status', 'notes' => 'bod_intv_note'],
            'Offering Letter' => ['date' => 'offering_letter_date', 'result' => 'offering_letter_status', 'notes' => 'offering_letter_notes'],
            'Medical Check Up' => ['date' => 'mcu_date', 'result' => 'mcu_status', 'notes' => 'mcu_note'],
            'Hiring' => ['date' => 'hiring_date', 'result' => 'hiring_status', 'notes' => 'hiring_note'],
        ];

        if (!isset($stageFields[$stage])) {
            return redirect()->route('candidates.show', $candidate)->with('error', 'Tahapan tidak valid.');
        }

        $fields = $stageFields[$stage];
        $updateData = [
            $fields['result'] => $result,
            $fields['notes'] => $notes,
        ];

        if (!$candidate->{$fields['date']}) {
            $updateData[$fields['date']] = now();
        }

        $candidate->update($updateData);

        // Update current_stage and overall_status
        $nextStage = array_key_first(array_slice($stageFields, array_search($stage, array_keys($stageFields)) + 1, 1));
        $candidate->update([
            'current_stage' => $result === 'LULUS' && $nextStage ? $nextStage : $stage,
            'overall_status' => $result === 'TIDAK LULUS' ? 'TIDAK LULUS' : ($candidate->hiring_status === 'HIRED' ? 'LULUS' : 'DALAM PROSES'),
        ]);

        return redirect()->route('candidates.show', $candidate)->with('success', 'Tahapan berhasil diperbarui.');
    }

    public function export(Request $request)
    {
        // Implementasi ekspor menggunakan Maatwebsite/Excel
        return Excel::download(new \App\Exports\CandidatesExport, 'kandidat_' . now()->format('Ymd_His') . '.xlsx');
    }
}