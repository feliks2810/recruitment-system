<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1" name="viewport"/>
    <title>Tambah Kandidat - Patria Maritim Perkasa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex">
    @include('layouts.sidebar')

    <main class="flex-1 flex flex-col">
        <header class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Tambah Kandidat</h1>
                    <p class="text-sm text-gray-600">Masukkan informasi kandidat baru</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('candidates.index') }}" class="flex items-center gap-2 px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                        <i class="fas fa-arrow-left mr-1"></i>
                        Kembali
                    </a>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-700">{{ substr(Auth::user()->name, 0, 2) }}</span>
                        <button class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <span class="text-sm font-medium text-blue-600">{{ substr(Auth::user()->name, 0, 2) }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <div class="flex-1 p-6">
            <div class="bg-white rounded-xl p-6 border border-gray-200">
                <h2 class="text-lg font-semibold mb-4">Form Kandidat</h2>
                @if ($errors->any())
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-6 h-6 bg-red-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-times text-red-600 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-red-800">Terdapat kesalahan dalam pengisian form:</p>
                                <ul class="list-disc pl-5 text-red-800">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('candidates.store') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="nama" class="block text-sm font-medium text-gray-700">Nama</label>
                            <input type="text" name="nama" id="nama" value="{{ old('nama') }}" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                        </div>
                        <div>
                            <label for="alamat_email" class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" name="alamat_email" id="alamat_email" value="{{ old('alamat_email') }}" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                        </div>
                        <div>
                            <label for="applicant_id" class="block text-sm font-medium text-gray-700">Applicant ID</label>
                            <input type="text" name="applicant_id" id="applicant_id" value="{{ old('applicant_id') }}" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                        </div>
                        <div>
                            <label for="vacancy_airsys" class="block text-sm font-medium text-gray-700">Vacancy</label>
                            <input type="text" name="vacancy_airsys" id="vacancy_airsys" value="{{ old('vacancy_airsys') }}" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                        </div>
                        <div>
                            <label for="airsys_internal" class="block text-sm font-medium text-gray-700">Airsys Internal</label>
                            <select name="airsys_internal" id="airsys_internal" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                                <option value="Yes" {{ old('airsys_internal') == 'Yes' ? 'selected' : '' }}>Yes</option>
                                <option value="No" {{ old('airsys_internal') == 'No' ? 'selected' : '' }}>No</option>
                            </select>
                        </div>
                        <div>
                            <label for="internal_position" class="block text-sm font-medium text-gray-700">Posisi Internal</label>
                            <input type="text" name="internal_position" id="internal_position" value="{{ old('internal_position') }}" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label for="on_process_by" class="block text-sm font-medium text-gray-700">Diproses Oleh</label>
                            <input type="text" name="on_process_by" id="on_process_by" value="{{ old('on_process_by') }}" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label for="source" class="block text-sm font-medium text-gray-700">Sumber</label>
                            <input type="text" name="source" id="source" value="{{ old('source') }}" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label for="jk" class="block text-sm font-medium text-gray-700">Jenis Kelamin</label>
                            <select name="jk" id="jk" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Pilih</option>
                                <option value="L" {{ old('jk') == 'L' ? 'selected' : '' }}>Laki-laki</option>
                                <option value="P" {{ old('jk') == 'P' ? 'selected' : '' }}>Perempuan</option>
                            </select>
                        </div>
                        <div>
                            <label for="tanggal_lahir" class="block text-sm font-medium text-gray-700">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" id="tanggal_lahir" value="{{ old('tanggal_lahir') }}" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label for="jenjang_pendidikan" class="block text-sm font-medium text-gray-700">Jenjang Pendidikan</label>
                            <input type="text" name="jenjang_pendidikan" id="jenjang_pendidikan" value="{{ old('jenjang_pendidikan') }}" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label for="perguruan_tinggi" class="block text-sm font-medium text-gray-700">Perguruan Tinggi</label>
                            <input type="text" name="perguruan_tinggi" id="perguruan_tinggi" value="{{ old('perguruan_tinggi') }}" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label for="jurusan" class="block text-sm font-medium text-gray-700">Jurusan</label>
                            <input type="text" name="jurusan" id="jurusan" value="{{ old('jurusan') }}" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label for="ipk" class="block text-sm font-medium text-gray-700">IPK</label>
                            <input type="number" name="ipk" id="ipk" value="{{ old('ipk') }}" step="0.01" min="0" max="4" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label for="cv" class="block text-sm font-medium text-gray-700">CV</label>
                            <input type="file" name="cv" id="cv" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label for="flk" class="block text-sm font-medium text-gray-700">FLK</label>
                            <input type="file" name="flk" id="flk" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label for="psikotest_date" class="block text-sm font-medium text-gray-700">Tanggal Psikotes</label>
                            <input type="date" name="psikotest_date" id="psikotest_date" value="{{ old('psikotest_date') }}" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label for="psikotes_result" class="block text-sm font-medium text-gray-700">Hasil Psikotes</label>
                            <select name="psikotes_result" id="psikotes_result" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Pilih Status</option>
                                <option value="LULUS" {{ old('psikotes_result') == 'LULUS' ? 'selected' : '' }}>Lulus</option>
                                <option value="TIDAK LULUS" {{ old('psikotes_result') == 'TIDAK LULUS' ? 'selected' : '' }}>Tidak Lulus</option>
                                <option value="DIPERTIMBANGKAN" {{ old('psikotes_result') == 'DIPERTIMBANGKAN' ? 'selected' : '' }}>Dipertimbangkan</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label for="psikotes_notes" class="block text-sm font-medium text-gray-700">Catatan Psikotes</label>
                            <textarea name="psikotes_notes" id="psikotes_notes" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent" rows="4">{{ old('psikotes_notes') }}</textarea>
                        </div>
                        <div>
                            <label for="hc_intv_date" class="block text-sm font-medium text-gray-700">Tanggal Interview HC</label>
                            <input type="date" name="hc_intv_date" id="hc_intv_date" value="{{ old('hc_intv_date') }}" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label for="hc_intv_status" class="block text-sm font-medium text-gray-700">Status Interview HC</label>
                            <select name="hc_intv_status" id="hc_intv_status" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Pilih Status</option>
                                <option value="DISARANKAN" {{ old('hc_intv_status') == 'DISARANKAN' ? 'selected' : '' }}>Disarankan</option>
                                <option value="TIDAK DISARANKAN" {{ old('hc_intv_status') == 'TIDAK DISARANKAN' ? 'selected' : '' }}>Tidak Disarankan</option>
                                <option value="DIPERTIMBANGKAN" {{ old('hc_intv_status') == 'DIPERTIMBANGKAN' ? 'selected' : '' }}>Dipertimbangkan</option>
                                <option value="CANCEL" {{ old('hc_intv_status') == 'CANCEL' ? 'selected' : '' }}>Cancel</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label for="hc_intv_notes" class="block text-sm font-medium text-gray-700">Catatan Interview HC</label>
                            <textarea name="hc_intv_notes" id="hc_intv_notes" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent" rows="4">{{ old('hc_intv_notes') }}</textarea>
                        </div>
                        <div>
                            <label for="user_intv_date" class="block text-sm font-medium text-gray-700">Tanggal Interview User</label>
                            <input type="date" name="user_intv_date" id="user_intv_date" value="{{ old('user_intv_date') }}" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label for="user_intv_status" class="block text-sm font-medium text-gray-700">Status Interview User</label>
                            <select name="user_intv_status" id="user_intv_status" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Pilih Status</option>
                                <option value="DISARANKAN" {{ old('user_intv_status') == 'DISARANKAN' ? 'selected' : '' }}>Disarankan</option>
                                <option value="TIDAK DISARANKAN" {{ old('user_intv_status') == 'TIDAK DISARANKAN' ? 'selected' : '' }}>Tidak Disarankan</option>
                                <option value="DIPERTIMBANGKAN" {{ old('user_intv_status') == 'DIPERTIMBANGKAN' ? 'selected' : '' }}>Dipertimbangkan</option>
                                <option value="CANCEL" {{ old('user_intv_status') == 'CANCEL' ? 'selected' : '' }}>Cancel</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label for="itv_user_note" class="block text-sm font-medium text-gray-700">Catatan Interview User</label>
                            <textarea name="itv_user_note" id="itv_user_note" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent" rows="4">{{ old('itv_user_note') }}</textarea>
                        </div>
                        <div>
                            <label for="bod_gm_intv_date" class="block text-sm font-medium text-gray-700">Tanggal Interview BOD/GM</label>
                            <input type="date" name="bod_gm_intv_date" id="bod_gm_intv_date" value="{{ old('bod_gm_intv_date') }}" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label for="bod_intv_status" class="block text-sm font-medium text-gray-700">Status Interview BOD/GM</label>
                            <select name="bod_intv_status" id="bod_intv_status" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Pilih Status</option>
                                <option value="DISARANKAN" {{ old('bod_intv_status') == 'DISARANKAN' ? 'selected' : '' }}>Disarankan</option>
                                <option value="TIDAK DISARANKAN" {{ old('bod_intv_status') == 'TIDAK DISARANKAN' ? 'selected' : '' }}>Tidak Disarankan</option>
                                <option value="DIPERTIMBANGKAN" {{ old('bod_intv_status') == 'DIPERTIMBANGKAN' ? 'selected' : '' }}>Dipertimbangkan</option>
                                <option value="CANCEL" {{ old('bod_intv_status') == 'CANCEL' ? 'selected' : '' }}>Cancel</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label for="bod_intv_note" class="block text-sm font-medium text-gray-700">Catatan Interview BOD/GM</label>
                            <textarea name="bod_intv_note" id="bod_intv_note" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent" rows="4">{{ old('bod_intv_note') }}</textarea>
                        </div>
                        <div>
                            <label for="offering_letter_date" class="block text-sm font-medium text-gray-700">Tanggal Offering Letter</label>
                            <input type="date" name="offering_letter_date" id="offering_letter_date" value="{{ old('offering_letter_date') }}" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label for="offering_letter_status" class="block text-sm font-medium text-gray-700">Status Offering Letter</label>
                            <select name="offering_letter_status" id="offering_letter_status" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Pilih Status</option>
                                <option value="DITERIMA" {{ old('offering_letter_status') == 'DITERIMA' ? 'selected' : '' }}>Diterima</option>
                                <option value="DITOLAK" {{ old('offering_letter_status') == 'DITOLAK' ? 'selected' : '' }}>Ditolak</option>
                                <option value="SENT" {{ old('offering_letter_status') == 'SENT' ? 'selected' : '' }}>Sent</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label for="offering_letter_notes" class="block text-sm font-medium text-gray-700">Catatan Offering Letter</label>
                            <textarea name="offering_letter_notes" id="offering_letter_notes" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent" rows="4">{{ old('offering_letter_notes') }}</textarea>
                        </div>
                        <div>
                            <label for="mcu_date" class="block text-sm font-medium text-gray-700">Tanggal MCU</label>
                            <input type="date" name="mcu_date" id="mcu_date" value="{{ old('mcu_date') }}" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label for="mcu_status" class="block text-sm font-medium text-gray-700">Status MCU</label>
                            <select name="mcu_status" id="mcu_status" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Pilih Status</option>
                                <option value="LULUS" {{ old('mcu_status') == 'LULUS' ? 'selected' : '' }}>Lulus</option>
                                <option value="TIDAK LULUS" {{ old('mcu_status') == 'TIDAK LULUS' ? 'selected' : '' }}>Tidak Lulus</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label for="mcu_note" class="block text-sm font-medium text-gray-700">Catatan MCU</label>
                            <textarea name="mcu_note" id="mcu_note" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent" rows="4">{{ old('mcu_note') }}</textarea>
                        </div>
                        <div>
                            <label for="hiring_date" class="block text-sm font-medium text-gray-700">Tanggal Hiring</label>
                            <input type="date" name="hiring_date" id="hiring_date" value="{{ old('hiring_date') }}" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label for="hiring_status" class="block text-sm font-medium text-gray-700">Status Hiring</label>
                            <select name="hiring_status" id="hiring_status" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Pilih Status</option>
                                <option value="HIRED" {{ old('hiring_status') == 'HIRED' ? 'selected' : '' }}>Hired</option>
                                <option value="TIDAK DIHIRING" {{ old('hiring_status') == 'TIDAK DIHIRING' ? 'selected' : '' }}>Tidak Dihiring</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label for="hiring_note" class="block text-sm font-medium text-gray-700">Catatan Hiring</label>
                            <textarea name="hiring_note" id="hiring_note" class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent" rows="4">{{ old('hiring_note') }}</textarea>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3">
                        <a href="{{ route('candidates.index') }}" class="px-4 py-2 border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50">
                            Batal
                        </a>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html>