@extends('layouts.app')

@section('title', 'Posisi & Pelamar')
@section('page-title', 'Posisi & Pelamar')
@section('page-subtitle', 'Daftar posisi dan jumlah pelamar dari Pengajuan MPP yang disetujui')

@section('content')
<div class="bg-white rounded-xl p-4 sm:p-6 border border-gray-200 shadow-sm">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-lg font-medium text-gray-900">Daftar Posisi (MPP Approved)</h2>
        <form method="GET" action="{{ route('posisi-pelamar.index') }}" class="flex items-center gap-4">
            <div>
                <label for="year" class="sr-only">Tahun</label>
                <select name="year" id="year" class="w-full px-3 py-2 border border-gray-300 rounded-md" onchange="this.form.submit()">
                    <option value="">Semua Tahun</option>
                    @foreach ($years as $year)
                        <option value="{{ $year }}" @selected($selectedYear == $year)>{{ $year }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Departemen</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Posisi</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tahun Pengajuan MPP</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status Posisi</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah Dibutuhkan</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah Pelamar</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah Diterima</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($departments as $departmentName => $vacancies)
                    @foreach($vacancies as $vacancyName => $vacancyDetails)
                        @foreach($vacancyDetails as $vacancy)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $departmentName }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $vacancy['name'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $vacancy['mpp_year'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($vacancy['status'] == 'Aktif (Cukup Pelamar)')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            {{ $vacancy['status'] }}
                                        </span>
                                    @elseif($vacancy['status'] == 'Aktif (Kurang Pelamar)')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            {{ $vacancy['status'] }}
                                        </span>
                                    @elseif($vacancy['status'] == 'Aktif (Jumlah Dibutuhkan Tidak Ditentukan)')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            {{ $vacancy['status'] }}
                                        </span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            {{ $vacancy['status'] }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $vacancy['needed_count'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $vacancy['applicant_count'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ $vacancy['accepted_count'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('candidates.index', ['vacancy_id' => $vacancy['id'], 'year' => $vacancy['mpp_year']]) }}" class="text-blue-600 hover:text-blue-900">Lihat Detail</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                            Tidak ada posisi yang disetujui.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection