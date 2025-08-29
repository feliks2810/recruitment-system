@extends('layouts.app')

@section('title', 'Edit Kandidat')
@section('page-title', 'Edit Kandidat')
@section('page-subtitle', 'Perbarui informasi kandidat')

@push('header-filters')
<a href="{{ route('candidates.index') }}" class="text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-50 flex items-center gap-2 border border-gray-300">
    <i class="fas fa-arrow-left text-sm"></i>
    <span>Kembali</span>
</a>
@endpush

@section('content')
@can('edit-candidates')
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Form Edit Kandidat</h3>
        </div>
        
        <div class="p-6">
            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="w-6 h-6 bg-red-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-exclamation-circle text-red-600 text-sm"></i>
                        </div>
                        <div>
                            <p class="text-red-800 font-medium">Terdapat kesalahan dalam pengisian form:</p>
                            <ul class="list-disc pl-5 text-red-800 mt-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('candidates.update', $candidate->id) }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')
                
                <!-- Informasi Dasar -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-md font-medium text-gray-900 mb-4">Informasi Dasar</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="nama" class="block text-sm font-medium text-gray-700">Nama *</label>
                            <input type="text" name="nama" id="nama" value="{{ old('nama', $candidate->nama) }}" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                        </div>
                        <div>
                            <label for="alamat_email" class="block text-sm font-medium text-gray-700">Email *</label>
                            <input type="email" name="alamat_email" id="alamat_email" value="{{ old('alamat_email', $candidate->alamat_email) }}" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                        </div>
                        <div>
                            <label for="applicant_id" class="block text-sm font-medium text-gray-700">Applicant ID *</label>
                            <input type="text" name="applicant_id" id="applicant_id" value="{{ old('applicant_id', $candidate->applicant_id) }}" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                        </div>
                        <div>
                            <label for="jk" class="block text-sm font-medium text-gray-700">Jenis Kelamin</label>
                            <select name="jk" id="jk" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <option value="">Pilih Jenis Kelamin</option>
                                <option value="L" {{ old('jk', $candidate->jk) == 'L' ? 'selected' : '' }}>Laki-laki</option>
                                <option value="P" {{ old('jk', $candidate->jk) == 'P' ? 'selected' : '' }}>Perempuan</option>
                            </select>
                        </div>
                        <div>
                            <label for="tanggal_lahir" class="block text-sm font-medium text-gray-700">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" id="tanggal_lahir" value="{{ old('tanggal_lahir', $candidate->tanggal_lahir ? \Carbon\Carbon::parse($candidate->tanggal_lahir)->format('Y-m-d') : '') }}" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                        <div>
                            <label for="vacancy" class="block text-sm font-medium text-gray-700">Vacancy *</label>
                            <input type="text" name="vacancy" id="vacancy" value="{{ old('vacancy', $candidate->vacancy) }}" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                        </div>
                        <div>
                            <label for="department_id" class="block text-sm font-medium text-gray-700">Departemen *</label>
                            <select name="department_id" id="department_id" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                                <option value="">Pilih Departemen</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}" {{ old('department_id', $candidate->department_id) == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="airsys_internal" class="block text-sm font-medium text-gray-700">Tipe Kandidat *</label>
                            <select name="airsys_internal" id="airsys_internal" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                                <option value="Yes" {{ old('airsys_internal', $candidate->airsys_internal) == 'Yes' ? 'selected' : '' }}>Organik</option>
                                <option value="No" {{ old('airsys_internal', $candidate->airsys_internal) == 'No' ? 'selected' : '' }}>Non-Organik</option>
                            </select>
                        </div>
                        <div>
                            <label for="source" class="block text-sm font-medium text-gray-700">Sumber</label>
                            <input type="text" name="source" id="source" value="{{ old('source', $candidate->source) }}" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                    </div>
                </div>

                <!-- Informasi Pendidikan -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-md font-medium text-gray-900 mb-4">Informasi Pendidikan</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="jenjang_pendidikan" class="block text-sm font-medium text-gray-700">Jenjang Pendidikan</label>
                            <input type="text" name="jenjang_pendidikan" id="jenjang_pendidikan" value="{{ old('jenjang_pendidikan', $candidate->jenjang_pendidikan) }}" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                        <div>
                            <label for="perguruan_tinggi" class="block text-sm font-medium text-gray-700">Perguruan Tinggi</label>
                            <input type="text" name="perguruan_tinggi" id="perguruan_tinggi" value="{{ old('perguruan_tinggi', $candidate->perguruan_tinggi) }}" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                        <div>
                            <label for="jurusan" class="block text-sm font-medium text-gray-700">Jurusan</label>
                            <input type="text" name="jurusan" id="jurusan" value="{{ old('jurusan', $candidate->jurusan) }}" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                        <div>
                            <label for="ipk" class="block text-sm font-medium text-gray-700">IPK</label>
                            <input type="number" name="ipk" id="ipk" value="{{ old('ipk', $candidate->ipk) }}" step="0.01" min="0" max="4" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                    </div>
                </div>

                <!-- Berkas -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-md font-medium text-gray-900 mb-4">Berkas</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="cv" class="block text-sm font-medium text-gray-700">CV</label>
                            <input type="file" name="cv" id="cv" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            @if ($candidate->cv)
                                <p class="text-sm text-gray-500 mt-1">File saat ini: <a href="{{ Storage::url($candidate->cv) }}" target="_blank" class="text-blue-600 hover:underline">Lihat CV</a></p>
                            @endif
                        </div>
                        <div>
                            <label for="flk" class="block text-sm font-medium text-gray-700">FLK</label>
                            <input type="file" name="flk" id="flk" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            @if ($candidate->flk)
                                <p class="text-sm text-gray-500 mt-1">File saat ini: <a href="{{ Storage::url($candidate->flk) }}" target="_blank" class="text-blue-600 hover:underline">Lihat FLK</a></p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Tahap Psikotes -->
                @php
                    $isCvReviewPassed = in_array(old('cv_review_status', $candidate->cv_review_status), ['LULUS']);
                @endphp
                @if($isCvReviewPassed)
                <div class="bg-gray-50 rounded-lg p-4 mt-6">
                    <h4 class="text-md font-medium text-gray-900 mb-4">Tahap Psikotes</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="psikotest_date" class="block text-sm font-medium text-gray-700">Tanggal Psikotes</label>
                            <input type="date" name="psikotest_date" id="psikotest_date" value="{{ old('psikotest_date', $candidate->psikotest_date ? \Carbon\Carbon::parse($candidate->psikotest_date)->format('Y-m-d') : '') }}" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                        <div>
                            <label for="psikotes_result" class="block text-sm font-medium text-gray-700">Hasil Psikotes</label>
                            <select name="psikotes_result" id="psikotes_result" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <option value="">Pilih Hasil</option>
                                <option value="LULUS" {{ old('psikotes_result', $candidate->psikotes_result) == 'LULUS' ? 'selected' : '' }}>LULUS</option>
                                <option value="TIDAK LULUS" {{ old('psikotes_result', $candidate->psikotes_result) == 'TIDAK LULUS' ? 'selected' : '' }}>TIDAK LULUS</option>
                                <option value="PENDING" {{ old('psikotes_result', $candidate->psikotes_result) == 'PENDING' ? 'selected' : '' }}>PENDING</option>
                            </select>
                        </div>
                    </div>
                </div>
                @else
                <div class="bg-gray-50 rounded-lg p-4 mt-6">
                    <p class="text-sm text-yellow-600">Tahap Psikotes akan muncul setelah kandidat LULUS pada tahap CV Review.</p>
                </div>
                @endif

                <!-- Tahap Interview HC -->
                @php
                    $isPsikotesPassed = in_array(old('psikotes_result', $candidate->psikotes_result), ['LULUS']);
                @endphp
                @if($isPsikotesPassed)
                <div class="bg-gray-50 rounded-lg p-4 mt-6">
                    <h4 class="text-md font-medium text-gray-900 mb-4">Interview HC</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="hc_interview_date" class="block text-sm font-medium text-gray-700">Tanggal Interview HC</label>
                            <input type="date" name="hc_interview_date" id="hc_interview_date" value="{{ old('hc_interview_date', $candidate->hc_interview_date ? \Carbon\Carbon::parse($candidate->hc_interview_date)->format('Y-m-d') : '') }}" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                        <div>
                            <label for="hc_interview_status" class="block text-sm font-medium text-gray-700">Hasil Interview HC</label>
                            <select name="hc_interview_status" id="hc_interview_status" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <option value="">Pilih Hasil</option>
                                <option value="LULUS" {{ old('hc_interview_status', $candidate->hc_interview_status) == 'LULUS' ? 'selected' : '' }}>LULUS</option>
                                <option value="TIDAK LULUS" {{ old('hc_interview_status', $candidate->hc_interview_status) == 'TIDAK LULUS' ? 'selected' : '' }}>TIDAK LULUS</option>
                                <option value="PENDING" {{ old('hc_interview_status', $candidate->hc_interview_status) == 'PENDING' ? 'selected' : '' }}>PENDING</option>
                            </select>
                        </div>
                    </div>
                </div>
                @else
                <div class="bg-gray-50 rounded-lg p-4 mt-6">
                    <p class="text-sm text-yellow-600">Interview HC akan muncul setelah kandidat LULUS pada tahap Psikotes.</p>
                </div>
                @endif

                <!-- Tahap Interview User -->
                @php
                    $isHcInterviewPassed = in_array(old('hc_interview_status', $candidate->hc_interview_status), ['LULUS', 'DISARANKAN']);
                @endphp
                @if($isHcInterviewPassed)
                <div class="bg-gray-50 rounded-lg p-4 mt-6">
                    <h4 class="text-md font-medium text-gray-900 mb-4">Interview User</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="user_interview_date" class="block text-sm font-medium text-gray-700">Tanggal Interview User</label>
                            <input type="date" name="user_interview_date" id="user_interview_date" value="{{ old('user_interview_date', $candidate->user_interview_date ? \Carbon\Carbon::parse($candidate->user_interview_date)->format('Y-m-d') : '') }}" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                        <div>
                            <label for="user_interview_status" class="block text-sm font-medium text-gray-700">Hasil Interview User</label>
                            <select name="user_interview_status" id="user_interview_status" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <option value="">Pilih Hasil</option>
                                <option value="DISARANKAN" {{ old('user_interview_status', $candidate->user_interview_status) == 'DISARANKAN' ? 'selected' : '' }}>DISARANKAN</option>
                                <option value="TIDAK DISARANKAN" {{ old('user_interview_status', $candidate->user_interview_status) == 'TIDAK DISARANKAN' ? 'selected' : '' }}>TIDAK DISARANKAN</option>
                                <option value="PENDING" {{ old('user_interview_status', $candidate->user_interview_status) == 'PENDING' ? 'selected' : '' }}>PENDING</option>
                            </select>
                        </div>
                    </div>
                </div>
                @else
                <div class="bg-gray-50 rounded-lg p-4 mt-6">
                    <p class="text-sm text-yellow-600">Interview User akan muncul setelah kandidat LULUS/DISARANKAN pada tahap Interview HC.</p>
                </div>
                @endif

                <!-- Tahap Interview BOD -->
                @php
                    $isUserInterviewPassed = in_array(old('user_interview_status', $candidate->user_interview_status), ['DISARANKAN']);
                @endphp
                @if($isUserInterviewPassed)
                <div class="bg-gray-50 rounded-lg p-4 mt-6">
                    <h4 class="text-md font-medium text-gray-900 mb-4">Interview BOD</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="bod_interview_date" class="block text-sm font-medium text-gray-700">Tanggal Interview BOD</label>
                            <input type="date" name="bod_interview_date" id="bod_interview_date" value="{{ old('bod_interview_date', $candidate->bod_interview_date ? \Carbon\Carbon::parse($candidate->bod_interview_date)->format('Y-m-d') : '') }}" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                        <div>
                            <label for="bod_interview_status" class="block text-sm font-medium text-gray-700">Hasil Interview BOD</label>
                            <select name="bod_interview_status" id="bod_interview_status" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <option value="">Pilih Hasil</option>
                                <option value="DISARANKAN" {{ old('bod_interview_status', $candidate->bod_interview_status) == 'DISARANKAN' ? 'selected' : '' }}>DISARANKAN</option>
                                <option value="TIDAK DISARANKAN" {{ old('bod_interview_status', $candidate->bod_interview_status) == 'TIDAK DISARANKAN' ? 'selected' : '' }}>TIDAK DISARANKAN</option>
                                <option value="PENDING" {{ old('bod_interview_status', $candidate->bod_interview_status) == 'PENDING' ? 'selected' : '' }}>PENDING</option>
                            </select>
                        </div>
                    </div>
                </div>
                @else
                <div class="bg-gray-50 rounded-lg p-4 mt-6">
                    <p class="text-sm text-yellow-600">Interview BOD akan muncul setelah kandidat DISARANKAN pada tahap Interview User.</p>
                </div>
                @endif

                <!-- Tahap Offering Letter -->
                @php
                    $isBodInterviewPassed = in_array(old('bod_interview_status', $candidate->bod_interview_status), ['DISARANKAN']);
                @endphp
                @if($isBodInterviewPassed)
                <div class="bg-gray-50 rounded-lg p-4 mt-6">
                    <h4 class="text-md font-medium text-gray-900 mb-4">Offering Letter</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="offering_letter_date" class="block text-sm font-medium text-gray-700">Tanggal Offering Letter</label>
                            <input type="date" name="offering_letter_date" id="offering_letter_date" value="{{ old('offering_letter_date', $candidate->offering_letter_date ? \Carbon\Carbon::parse($candidate->offering_letter_date)->format('Y-m-d') : '') }}" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                        <div>
                            <label for="offering_letter_status" class="block text-sm font-medium text-gray-700">Hasil Offering Letter</label>
                            <select name="offering_letter_status" id="offering_letter_status" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <option value="">Pilih Hasil</option>
                                <option value="DITERIMA" {{ old('offering_letter_status', $candidate->offering_letter_status) == 'DITERIMA' ? 'selected' : '' }}>DITERIMA</option>
                                <option value="TIDAK DITERIMA" {{ old('offering_letter_status', $candidate->offering_letter_status) == 'TIDAK DITERIMA' ? 'selected' : '' }}>TIDAK DITERIMA</option>
                                <option value="PENDING" {{ old('offering_letter_status', $candidate->offering_letter_status) == 'PENDING' ? 'selected' : '' }}>PENDING</option>
                            </select>
                        </div>
                    </div>
                </div>
                @else
                <div class="bg-gray-50 rounded-lg p-4 mt-6">
                    <p class="text-sm text-yellow-600">Offering Letter akan muncul setelah kandidat DISARANKAN pada tahap Interview BOD.</p>
                </div>
                @endif

                <!-- Tahap MCU -->
                @php
                    $isOfferingLetterPassed = in_array(old('offering_letter_status', $candidate->offering_letter_status), ['DITERIMA']);
                @endphp
                @if($isOfferingLetterPassed)
                <div class="bg-gray-50 rounded-lg p-4 mt-6">
                    <h4 class="text-md font-medium text-gray-900 mb-4">MCU</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="mcu_date" class="block text-sm font-medium text-gray-700">Tanggal MCU</label>
                            <input type="date" name="mcu_date" id="mcu_date" value="{{ old('mcu_date', $candidate->mcu_date ? \Carbon\Carbon::parse($candidate->mcu_date)->format('Y-m-d') : '') }}" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                        <div>
                            <label for="mcu_status" class="block text-sm font-medium text-gray-700">Hasil MCU</label>
                            <select name="mcu_status" id="mcu_status" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <option value="">Pilih Hasil</option>
                                <option value="LULUS" {{ old('mcu_status', $candidate->mcu_status) == 'LULUS' ? 'selected' : '' }}>LULUS</option>
                                <option value="TIDAK LULUS" {{ old('mcu_status', $candidate->mcu_status) == 'TIDAK LULUS' ? 'selected' : '' }}>TIDAK LULUS</option>
                                <option value="PENDING" {{ old('mcu_status', $candidate->mcu_status) == 'PENDING' ? 'selected' : '' }}>PENDING</option>
                            </select>
                        </div>
                    </div>
                </div>
                @else
                <div class="bg-gray-50 rounded-lg p-4 mt-6">
                    <p class="text-sm text-yellow-600">MCU akan muncul setelah kandidat DITERIMA pada tahap Offering Letter.</p>
                </div>
                @endif

                <!-- Tahap Hiring -->
                @php
                    $isMcuPassed = in_array(old('mcu_status', $candidate->mcu_status), ['LULUS']);
                @endphp
                @if($isMcuPassed)
                <div class="bg-gray-50 rounded-lg p-4 mt-6">
                    <h4 class="text-md font-medium text-gray-900 mb-4">Hiring</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="hiring_date" class="block text-sm font-medium text-gray-700">Tanggal Hiring</label>
                            <input type="date" name="hiring_date" id="hiring_date" value="{{ old('hiring_date', $candidate->hiring_date ? \Carbon\Carbon::parse($candidate->hiring_date)->format('Y-m-d') : '') }}" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                        <div>
                            <label for="hiring_status" class="block text-sm font-medium text-gray-700">Hasil Hiring</label>
                            <select name="hiring_status" id="hiring_status" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <option value="">Pilih Hasil</option>
                                <option value="HIRED" {{ old('hiring_status', $candidate->hiring_status) == 'HIRED' ? 'selected' : '' }}>HIRED</option>
                                <option value="TIDAK DIHIRING" {{ old('hiring_status', $candidate->hiring_status) == 'TIDAK DIHIRING' ? 'selected' : '' }}>TIDAK DIHIRING</option>
                                <option value="PENDING" {{ old('hiring_status', $candidate->hiring_status) == 'PENDING' ? 'selected' : '' }}>PENDING</option>
                            </select>
                        </div>
                    </div>
                </div>
                @else
                <div class="bg-gray-50 rounded-lg p-4 mt-6">
                    <p class="text-sm text-yellow-600">Hiring akan muncul setelah kandidat LULUS pada tahap MCU.</p>
                </div>
                @endif

                <!-- Field Tanggal Tes Berikutnya -->
                <!-- Catatan: Tanggal tes berikutnya sekarang diatur melalui popup timeline rekrutmen saat hasil tes "LULUS" atau "DIPERTIMBANGKAN" -->

                <!-- Submit Button -->
                <div class="flex justify-end">
                    <div class="flex space-x-3">
                        <a href="{{ route('candidates.show', $candidate) }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            Batal
                        </a>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endcan
@endsection

@push('scripts')
    <!-- Success Message -->
    @if(session('success'))
    <div id="success-alert" class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
        <div class="flex items-center gap-2">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('success') }}</span>
            <button onclick="document.getElementById('success-alert').remove()" class="ml-2 text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <script>
        setTimeout(() => {
            const alert = document.getElementById('success-alert');
            if (alert) alert.remove();
        }, 5000);
    </script>
    @endif

    <!-- Error Message -->
    @if(session('error'))
    <div id="error-alert" class="fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
        <div class="flex items-center gap-2">
            <i class="fas fa-exclamation-circle"></i>
            <span>{{ session('error') }}</span>
            <button onclick="document.getElementById('error-alert').remove()" class="ml-2 text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <script>
        setTimeout(() => {
            const alert = document.getElementById('error-alert');
            if (alert) alert.remove();
        }, 5000);
    </script>
    @endif
@endpush