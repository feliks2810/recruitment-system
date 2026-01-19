<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8 flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Pengajuan MPP</h1>
          <p class="mt-2 text-gray-600">Daftar pengajuan manpower planning</p>
        </div>
        <Link
          href="/mpp-submissions/create"
          class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
        >
          + Buat Pengajuan Baru
        </Link>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-lg shadow mb-6 p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select
              v-model="filters.status"
              @change="applyFilters"
              class="w-full px-3 py-2 border border-gray-300 rounded-md"
            >
              <option value="">Semua Status</option>
              <option value="draft">Draft</option>
              <option value="submitted">Submitted</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                No.
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Departemen
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Dibuat Oleh
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Posisi
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Tanggal Dibuat
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Aksi
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-if="mppSubmissions.data.length === 0">
              <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                Tidak ada pengajuan MPP
              </td>
            </tr>
            <tr v-for="(mpp, index) in mppSubmissions.data" :key="mpp.id">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ (mppSubmissions.current_page - 1) * mppSubmissions.per_page + index + 1 }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ mpp.department.name }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ mpp.created_by_user.name }}
              </td>
              <td class="px-6 py-4 text-sm text-gray-900">
                <div v-if="mpp.vacancies.length > 0" class="flex flex-wrap gap-2">
                  <span v-for="v in mpp.vacancies" :key="v.id" class="inline-block px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
                    {{ v.name }}
                  </span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="getStatusBadgeClass(mpp.status)"
                  class="px-3 py-1 rounded-full text-xs font-medium"
                >
                  {{ formatStatus(mpp.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ formatDate(mpp.created_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <Link
                  :href="`/mpp-submissions/${mpp.id}`"
                  class="text-blue-600 hover:text-blue-900 mr-3"
                >
                  Lihat
                </Link>
                <button
                  v-if="mpp.status === 'draft' && userRole.includes('team_hc')"
                  @click="deleteSubmission(mpp.id)"
                  class="text-red-600 hover:text-red-900"
                >
                  Hapus
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="mppSubmissions.last_page > 1" class="mt-6 flex justify-center gap-2">
        <Link
          v-if="mppSubmissions.prev_page_url"
          :href="mppSubmissions.prev_page_url"
          class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400"
        >
          Sebelumnya
        </Link>
        <Link
          v-if="mppSubmissions.next_page_url"
          :href="mppSubmissions.next_page_url"
          class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400"
        >
          Selanjutnya
        </Link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
  mppSubmissions: Object,
  userRole: Array,
})

const filters = ref({
  status: '',
})

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

const formatStatus = (status) => {
  const statusMap = {
    draft: 'Draft',
    submitted: 'Submitted',
    approved: 'Approved',
    rejected: 'Rejected',
  }
  return statusMap[status] || status
}

const getStatusBadgeClass = (status) => {
  const classes = {
    draft: 'bg-gray-100 text-gray-800',
    submitted: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const applyFilters = () => {
  router.get('/mpp-submissions', filters.value, {
    preserveState: true,
  })
}

const deleteSubmission = (id) => {
  if (confirm('Apakah Anda yakin ingin menghapus pengajuan ini?')) {
    router.delete(`/mpp-submissions/${id}`)
  }
}
</script>
