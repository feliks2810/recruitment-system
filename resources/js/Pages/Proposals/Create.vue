
<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const form = useForm({
    position_name: '',
    needed_count: 1,
    document: null,
});

const onFileChange = (event) => {
    form.document = event.target.files[0];
};

const submit = () => {
    form.post(route('proposals.store'), {
        forceFormData: true,
    });
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
                                <label for="position_name" class="block text-sm font-medium text-gray-700">Position Name</label>
                                <input v-model="form.position_name" type="text" id="position_name" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                <div v-if="form.errors.position_name" class="text-red-500 text-xs mt-1">{{ form.errors.position_name }}</div>
                            </div>

                            <div class="mb-4">
                                <label for="needed_count" class="block text-sm font-medium text-gray-700">Number of People Needed</label>
                                <input v-model="form.needed_count" type="number" id="needed_count" min="1" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                <div v-if="form.errors.needed_count" class="text-red-500 text-xs mt-1">{{ form.errors.needed_count }}</div>
                            </div>

                            <div class="mb-4">
                                <label for="document" class="block text-sm font-medium text-gray-700">Manpower Request Document (PDF, Word)</label>
                                <input @change="onFileChange" type="file" id="document" class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none">
                                <div v-if="form.errors.document" class="text-red-500 text-xs mt-1">{{ form.errors.document }}</div>
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
