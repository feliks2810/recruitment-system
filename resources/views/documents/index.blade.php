@extends('layouts.app')

@section('page-title', 'Documents')

@section('content')
    <div class="py-12" x-data="{ search: '{{ $search ?? '' }}', documents: {{ json_encode($documents) }}, 
        fetchDocuments() {
            fetch(`{{ route('documents.index') }}?search=${this.search}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                this.documents = data.documents;
            });
        }
    }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">

                    <!-- Search Form -->
                    <div class="mb-6">
                        <div class="flex items-center space-x-2">
                            <input type="text" x-model="search" x-on:input.debounce.500ms="fetchDocuments" placeholder="Search documents by title..."
                                   class="block w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <button x-show="search" x-on:click="search = ''; fetchDocuments()" type="button" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:outline-none focus:border-gray-300 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                                Clear
                            </button>
                        </div>
                    </div>

                    <!-- Upload Form -->
                    <form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data" class="mb-6">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="title" class="block font-medium text-sm text-gray-700">Document Title</label>
                                <input type="text" name="title" id="title" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-200 focus:ring-opacity-50" required>
                                @error('title')
                                    <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="file" class="block font-medium text-sm text-gray-700">PDF File</label>
                                <input type="file" name="file" id="file" class="block mt-1 w-full" required>
                                @error('file')
                                    <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                                Upload
                            </button>
                        </div>
                    </form>

                    <!-- Document List -->
                    <div class="mt-6">
                        <h3 class="text-lg font-semibold">Uploaded Documents</h3>
                        <div class="mt-4 space-y-4" x-html="documents.length ? documents.map(doc => `
                            <div class='flex items-center justify-between p-4 border rounded-lg'>
                                <p class='text-gray-700'>${doc.title}</p>
                                <a href='{{ asset('storage/') }}/${doc.file_path}' target='_blank' class='text-indigo-600 hover:text-indigo-900 font-semibold'>View File</a>
                            </div>
                        `).join('') : `<p class='text-gray-500'>No documents have been uploaded yet.</p>`">
                            <!-- Initial documents will be rendered here by Alpine.js -->
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
