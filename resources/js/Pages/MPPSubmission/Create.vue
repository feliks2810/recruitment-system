<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Pengajuan MPP (Manpower Planning)</h1>
        <p class="mt-2 text-gray-600">Buat pengajuan manpower planning untuk departemen Anda</p>
      </div>

      <!-- Form -->
      <div class="bg-white rounded-lg shadow">
        <form @submit.prevent="submitForm" class="p-6">
          <!-- Department Selection -->
          <div class="mb-6">
            <label for="department" class="block text-sm font-medium text-gray-700 mb-2">
              Departemen
            </label>
            <select
              v-model="form.department_id"
              id="department"
              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
              @change="updateAvailablePositions"
              required
            >
              <option value="">Pilih Departemen</option>
              <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                {{ dept.name }}
              </option>
            </select>
            <p v-if="errors.department_id" class="mt-1 text-sm text-red-600">
              {{ errors.department_id[0] }}
            </p>
          </div>

          <!-- Positions -->
          <div class="mb-6">
            <div class="flex justify-between items-center mb-4">
              <label class="block text-sm font-medium text-gray-700">
                Posisi yang Diajukan
              </label>
              <button
                v-if="form.positions.length < availablePositions.length"
                type="button"
                @click="addPosition"
                class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700"
              >
                + Tambah Posisi
              </button>
            </div>

            <div v-if="form.positions.length === 0" class="text-center py-6 bg-gray-50 rounded border border-gray-200">
              <p class="text-gray-500">Belum ada posisi yang ditambahkan</p>
            </div>

            <div v-else class="space-y-4">
              <div
                v-for="(position, index) in form.positions"
                :key="index"
                class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-gray-50 rounded border border-gray-200"
              >
                <!-- Position Name -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Posisi
                  </label>
                  <select
                    v-model="position.vacancy_id"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    required
                  >
                    <option value="">Pilih Posisi</option>
                    <option
                      v-for="pos in availablePositions"
                      :key="pos.id"
                      :value="pos.id"
                      :disabled="isPositionSelected(pos.id, index)"
                    >
                      {{ pos.name }}
                    </option>
                  </select>
                </div>

                <!-- Vacancy Status -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Status Vacancy
                  </label>
                  <select
                    v-model="position.vacancy_status"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    required
                  >
                    <option value="">Pilih Status</option>
                    <option value="OSPKWT">OSPKWT (Wajib upload Dokumen A1)</option>
                    <option value="OS">OS (Wajib upload Dokumen B1)</option>
                  </select>
                </div>

                <!-- Remove Button -->
                <div class="flex items-end">
                  <button
                    type="button"
                    @click="removePosition(index)"
                    class="w-full px-3 py-2 bg-red-100 text-red-700 rounded hover:bg-red-200 font-medium"
                  >
                    Hapus
                  </button>
                </div>
              </div>
            </div>

            <p v-if="errors['positions.0.vacancy_id']" class="mt-2 text-sm text-red-600">
              {{ errors['positions.0.vacancy_id'][0] }}
            </p>
          </div>

          <!-- Form Actions -->
          <div class="flex gap-4">
            <button
              type="submit"
              :disabled="isSubmitting"
              class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:bg-gray-400"
            >
              <span v-if="isSubmitting">Menyimpan...</span>
              <span v-else>Simpan & Kirim</span>
            </button>
            <Link
              href="/mpp-submissions"
              class="px-6 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 inline-block"
            >
              Batal
            </Link>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  departments: Array,
  positions: Array,
})

const isSubmitting = ref(false)
const availablePositions = ref([])

const form = useForm({
  department_id: '',
  positions: [],
})

const errors = computed(() => form.errors)

const updateAvailablePositions = () => {
  if (!form.department_id) {
    availablePositions.value = []
    return
  }

  const deptPositions = props.positions.find(p => p.department_id == form.department_id)
  availablePositions.value = deptPositions?.positions || []
}

const addPosition = () => {
  form.positions.push({
    vacancy_id: '',
    vacancy_status: '',
  })
}

const removePosition = (index) => {
  form.positions.splice(index, 1)
}

const isPositionSelected = (positionId, currentIndex) => {
  return form.positions.some((pos, index) => pos.vacancy_id == positionId && index !== currentIndex)
}

const submitForm = () => {
  if (form.positions.length === 0) {
    alert('Tambahkan minimal satu posisi')
    return
  }

  isSubmitting.value = true
  form.post('/mpp-submissions', {
    onFinish: () => {
      isSubmitting.value = false
    },
  })
}
</script>
