<?php

namespace App\Http\Controllers;

use App\Models\ManpowerRequestFile;
use App\Models\Vacancy;
use App\Models\VacancyProposalHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VacancyProposalController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $canStep1 = $user->can('review-vacancy-proposals-step-1');
        $canStep2 = $user->can('review-vacancy-proposals-step-2');

        if (!$canStep1 && !$canStep2) {
            abort(403);
        }

        $year = $request->input('year', date('Y'));

        $proposalsQuery = Vacancy::with(['proposedByUser', 'department', 'manpowerRequestFiles'])
            ->whereNotNull('proposal_status')
            ->where(function ($query) use ($canStep1, $canStep2) {
                // HC1 sees proposals pending their approval
                if ($canStep1) {
                    $query->orWhere('proposal_status', Vacancy::STATUS_PENDING);
                }
                // HC2 sees proposals pending their approval
                if ($canStep2) {
                    $query->orWhere('proposal_status', Vacancy::STATUS_PENDING_HC2_APPROVAL);
                }
            });

        $proposals = $proposalsQuery->whereYear('created_at', $year)->get();
        
        // Simplified stats based on the user's role
        $userVisibleStatuses = [];
        if ($canStep1) $userVisibleStatuses[] = 'pending';
        if ($canStep2) $userVisibleStatuses[] = 'pending_hc2_approval';

        $histories = VacancyProposalHistory::with(['user', 'vacancy'])
            ->whereYear('created_at', $year)
            ->latest()
            ->get();
            
        $stats = [
            'total' => $histories->count(),
            'pending' => $histories->whereIn('status', $userVisibleStatuses)->count(),
            'approved' => $histories->where('status', 'approved')->count(),
            'rejected' => $histories->where('status', 'rejected')->count(),
        ];

        return view('proposals.index', [
            'proposals' => $proposals,
            'histories' => $histories,
            'stats' => $stats,
            'year' => $year,
        ]);
    }

    public function create(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (!$user->can('propose-vacancy')) {
            abort(403, 'You are not authorized to propose a vacancy.');
        }

        $year = $request->input('year', date('Y'));

        // Ambil vacancies dari departemen user yang tidak sedang dalam proses proposal
        $vacancies = Vacancy::where('department_id', $user->department_id)
            ->where(function ($query) {
                $query->whereNull('proposal_status')
                    ->orWhereIn('proposal_status', [Vacancy::STATUS_APPROVED, Vacancy::STATUS_REJECTED]);
            })
            ->get();

        // Ambil ID lowongan yang sedang dalam proses proposal
        $pendingProposalVacancyIds = Vacancy::where('department_id', $user->department_id)
            ->whereIn('proposal_status', [Vacancy::STATUS_PENDING, Vacancy::STATUS_PENDING_HC2_APPROVAL])
            ->pluck('id')
            ->toArray();

        // Ambil riwayat pengajuan untuk departemen pengguna
        $proposalHistories = VacancyProposalHistory::with('vacancy')
            ->whereHas('vacancy', function ($query) use ($user) {
                $query->where('department_id', $user->department_id);
            })
            ->whereYear('created_at', $year)
            ->latest()
            ->get();

        $stats = [
            'total' => $proposalHistories->count(),
            'pending' => $proposalHistories->whereIn('status', [Vacancy::STATUS_PENDING, Vacancy::STATUS_PENDING_HC2_APPROVAL])->count(),
            'approved' => $proposalHistories->where('status', Vacancy::STATUS_APPROVED)->count(),
            'rejected' => $proposalHistories->where('status', Vacancy::STATUS_REJECTED)->count(),
        ];

        return view('proposals.create', compact('vacancies', 'proposalHistories', 'stats', 'pendingProposalVacancyIds', 'year'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'vacancy_id' => ['required', 'integer', 'exists:vacancies,id'],
            'proposed_needed_count' => ['required', 'integer', 'min:1'],
            'document' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->can('propose-vacancy')) {
            abort(403, 'You are not authorized to propose a vacancy.');
        }

        $vacancy = Vacancy::find($request->input('vacancy_id'));

        // Pastikan lowongan milik departemen yang sama
        if ($vacancy->department_id !== $user->department_id) {
            return back()->with('error', 'You can only propose for your own department.');
        }

        // Pastikan tidak ada proposal yang sedang berjalan untuk lowongan ini
        if ($vacancy->proposal_status && !in_array($vacancy->proposal_status, [Vacancy::STATUS_APPROVED, Vacancy::STATUS_REJECTED])) {
            return back()->with('error', 'A proposal for this vacancy is already in process.');
        }

        DB::transaction(function () use ($request, $user, $vacancy) {
            $vacancy->update([
                'proposed_needed_count' => $request->input('proposed_needed_count'),
                'proposal_status' => Vacancy::STATUS_PENDING,
                'proposed_by_user_id' => $user->id,
            ]);

            $file = $request->file('document');
            $fileName = $this->generateFileName($vacancy, $file);
            $filePath = $file->storeAs('public/manpower_requests', $fileName);

            ManpowerRequestFile::create([
                'vacancy_id' => $vacancy->id,
                'user_id' => $user->id,
                'file_path' => $filePath,
                'stage' => 'initial',
            ]);

            VacancyProposalHistory::create([
                'vacancy_id' => $vacancy->id,
                'user_id' => $user->id,
                'status' => Vacancy::STATUS_PENDING,
                'notes' => 'Initial proposal for ' . $request->input('proposed_needed_count') . ' positions.',
                'proposed_needed_count' => $request->input('proposed_needed_count'),
            ]);
        });

        return redirect()->route('proposals.create')->with('success', 'Vacancy proposal submitted successfully.');
    }
    
    public function show(Vacancy $vacancy)
    {
        $vacancy->load(['proposalHistories.user', 'manpowerRequestFiles.user']);
        return view('proposals.show', compact('vacancy'));
    }

    public function approve(Vacancy $vacancy)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user || !$user->can('review-vacancy-proposals-step-2') || $vacancy->proposal_status !== Vacancy::STATUS_PENDING_HC2_APPROVAL) {
            return redirect()->back()->with('error', 'You are not authorized to perform this action.');
        }
        
        DB::transaction(function () use ($vacancy, $user) {
            $vacancy->update([
                'proposal_status' => Vacancy::STATUS_APPROVED,
                'needed_count' => $vacancy->needed_count + $vacancy->proposed_needed_count,
                 'is_active' => true,
            ]);
    
            VacancyProposalHistory::create([
                'vacancy_id' => $vacancy->id,
                'user_id' => $user->id,
                'status' => Vacancy::STATUS_APPROVED,
                'notes' => 'Vacancy proposal approved by Team HC 2.',
                'hc2_approved_at' => now(),
            ]);
        });

        return redirect()->back()->with('success', 'Vacancy proposal approved.');
    }

    public function reject(Request $request, Vacancy $vacancy)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user) {
            abort(403);
        }

        $canHC1 = $user->can('review-vacancy-proposals-step-1');
        $canHC2 = $user->can('review-vacancy-proposals-step-2');
        if (!$canHC1 && !$canHC2) {
            abort(403);
        }

        $request->validate([
            'rejection_reason' => ['required', 'string', 'min:3'],
            'document' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ]);

        $vacancy->load('department');

        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $fileName = $this->generateFileName($vacancy, $file);
            $filePath = $file->storeAs('public/manpower_requests', $fileName);

            ManpowerRequestFile::create([
                'vacancy_id' => $vacancy->id,
                'user_id' => $user->id,
                'file_path' => $filePath,
                'stage' => 'rejected',
            ]);
        }

        $rejectionReason = $request->input('rejection_reason');
        $rejectionStage = '';
        if ($canHC1 && $vacancy->proposal_status === Vacancy::STATUS_PENDING) {
            $rejectionStage = 'HC1';
        } elseif ($canHC2 && $vacancy->proposal_status === Vacancy::STATUS_PENDING_HC2_APPROVAL) {
            $rejectionStage = 'HC2';
        }

        $vacancy->update([
            'proposal_status' => Vacancy::STATUS_REJECTED,
            'rejection_reason' => $rejectionReason,
        ]);

        VacancyProposalHistory::create([
            'vacancy_id' => $vacancy->id,
            'user_id' => $user->id,
            'status' => 'rejected',
            'notes' => "Rejected at {$rejectionStage}: " . $rejectionReason,
        ]);

        return redirect()->back()->with('success', 'Vacancy proposal rejected.');
    }
    
    public function hc1Upload(Request $request, Vacancy $vacancy)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user || !$user->can('review-vacancy-proposals-step-1') || $vacancy->proposal_status !== Vacancy::STATUS_PENDING) {
            return redirect()->back()->with('error', 'You are not authorized to perform this action.');
        }

        $request->validate([ 'document' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240'], ]);

        $vacancy->load('department');

        DB::transaction(function () use ($request, $user, $vacancy) {
            $file = $request->file('document');
            $fileName = $this->generateFileName($vacancy, $file);
            $filePath = $file->storeAs('public/manpower_requests', $fileName);

            ManpowerRequestFile::create([
                'vacancy_id' => $vacancy->id,
                'user_id' => $user->id,
                'file_path' => $filePath,
                'stage' => 'hc1_revised',
            ]);

            $vacancy->update(['proposal_status' => Vacancy::STATUS_PENDING_HC2_APPROVAL]);

            VacancyProposalHistory::create([
                'vacancy_id' => $vacancy->id,
                'user_id' => $user->id,
                'status' => Vacancy::STATUS_PENDING_HC2_APPROVAL,
                'notes' => 'Proposal reviewed and approved by HC1. Forwarded to HC2.',
                'hc1_approved_at' => now(),
            ]);
        });

        return redirect()->route('proposals.index')->with('success', 'HC1 review uploaded successfully.');
    }

    public function uploadDocument(Request $request)
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,jpg,png|max:20480', // max 20MB
        ]);
    
        $path = $request->file('document')->store('documents', 'public');
    
        // Simpan info dokumen ke database jika perlu, misal ke tabel manpower_request_files
        // ManpowerRequestFile::create([
        //     'user_id' => auth()->id(),
        //     'path' => $path,
        //     'status' => 'pending', // status untuk diperiksa team HC 1
        // ]);
    
        return back()->with('success', 'Dokumen berhasil diupload dan menunggu pemeriksaan team HC 1.');
    }

    public function downloadFile(ManpowerRequestFile $manpowerRequestFile)
    {
        $fileName = basename($manpowerRequestFile->file_path);
        return Storage::download($manpowerRequestFile->file_path, $fileName);
    }

    private function generateFileName(Vacancy $vacancy, $file): string
    {
        $date = now()->format('Y-m-d');
        $position = preg_replace('/[^A-Za-z0-9_.-]/', '_', $vacancy->name);
        $department = preg_replace('/[^A-Za-z0-9_.-]/', '_', $vacancy->department->name);
        $extension = $file->getClientOriginalExtension();
        $timestamp = time();

        return "{$date}_Proposal_{$position}_{$department}_{$timestamp}.{$extension}";
    }
}