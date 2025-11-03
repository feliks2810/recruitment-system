
<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    vacancies: Array,
});

const form = useForm({
    vacancy_id: null,
    proposed_needed_count: 1,
});

const submit = () => {
    form.post(route('proposals.store'));
};
</script>

<template>
    <Head title="Propose New Vacancy" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Propose New Vacancy</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <form @submit.prevent="submit">
                            <div class="mb-4">
                                <label for="vacancy_id" class="block text-sm font-medium text-gray-700">Position</label>
                                <select v-model="form.vacancy_id" id="vacancy_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                    <option :value="null" disabled>Select a position</option>
                                    <option v-for="vacancy in vacancies" :key="vacancy.id" :value="vacancy.id">
                                        {{ vacancy.name }}
                                    </option>
                                </select>
                                <div v-if="form.errors.vacancy_id" class="text-red-500 text-xs mt-1">{{ form.errors.vacancy_id }}</div>
                            </div>

                            <div class="mb-4">
                                <label for="proposed_needed_count" class="block text-sm font-medium text-gray-700">Number of People Needed</label>
                                <input v-model="form.proposed_needed_count" type="number" id="proposed_needed_count" min="1" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                <div v-if="form.errors.proposed_needed_count" class="text-red-500 text-xs mt-1">{{ form.errors.proposed_needed_count }}</div>
                            </div>

                            <div class="flex items-center justify-end mt-4">
                                <button type="submit" :disabled="form.processing" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                                    Submit Proposal
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
