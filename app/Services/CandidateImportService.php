<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\Department;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class CandidateImportService
{
    public function __construct(
        protected int $authUserId,
        protected int $historyId
    ) {}

    public function import(array $row, int $rowIndex): void
    {
        if (empty($row['nama'])) {
            throw new Exception('Nama kosong');
        }

        DB::transaction(function () use ($row, $rowIndex) {

            $departmentId = $this->resolveDepartment($row);

            Candidate::create([
                'applicant_id'        => $row['applicant_id'],
                'nama'                => $row['nama'],
                'alamat_email'        => $row['email'] ?? $row['alamat_email'],
                'jk'                  => $row['jk'],
                'tanggal_lahir'       => Carbon::parse($row['tanggal_lahir']),
                'source'              => $row['source'],
                'raw_department_name' => $row['department'] ?? null,
                'department_id'       => $departmentId,
                'airsys_internal'     => 'Yes',
            ]);
        });
    }

    protected function resolveDepartment(array $row): ?int
    {
        if (empty($row['department'])) {
            return null;
        }

        return Department::firstOrCreate(
            ['name' => trim($row['department'])],
            ['created_by' => $this->authUserId]
        )->id;
    }
}
