<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Interview Calendar') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Event Detail Modal -->
    <div id="eventDetailModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modalTitle"></h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500" id="modalTime"></p>
                                <div id="modalZoomDetails" class="mt-4 hidden">
                                    <p class="text-sm"><strong>Meeting ID:</strong> <span id="modalZoomId"></span></p>
                                    <p class="text-sm"><strong>Password:</strong> <span id="modalZoomPassword"></span></p>
                                    <div class="mt-2 relative">
                                        <input type="text" id="zoomLinkInput" class="w-full border-gray-300 rounded-md shadow-sm" readonly>
                                        <button id="copyLinkBtn" class="absolute inset-y-0 right-0 px-3 flex items-center bg-gray-200 text-gray-600 rounded-r-md hover:bg-gray-300">Copy</button>
                                    </div>
                                </div>
                                <div id="modalDescription" class="mt-4 text-sm text-gray-600"></div>
                                <div id="modalCandidateInfo" class="mt-4 p-3 bg-gray-50 rounded-md hidden">
                                    <h4 class="font-bold">Candidate Information</h4>
                                    <p id="candidateName"></p>
                                    <a href="#" id="candidateTimelineLink" class="text-blue-500 hover:underline">View Timeline</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <a href="#" id="joinZoomBtn" target="_blank" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 sm:ml-3 sm:w-auto sm:text-sm hidden">Join Zoom Meeting</a>
                    <button type="button" id="closeModalBtn" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <style>
        .zoom-only-event {
            background-color: #2d8cff;
            border-color: #2d8cff;
            color: white;
        }
        .google-calendar-event {
            background-color: #34a853;
            border-color: #34a853;
            color: white;
        }
        .fc-event-title {
            font-weight: 600;
        }
    </style>
    @endpush

    @push('scripts')
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            let calendar;

            function initializeCalendar(candidateId = null) {
                let eventSourceUrl = '/api/calendar/data';
                if (candidateId) {
                    eventSourceUrl += `?candidate_id=${candidateId}`;
                }

                if (calendar) {
                    calendar.destroy();
                }

                calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    events: {
                        url: eventSourceUrl,
                        headers: {
                            'Authorization': `Bearer ${localStorage.getItem('api_token')}`, // Assuming you store a token
                            'Accept': 'application/json'
                        },
                        failure: function() {
                            alert('There was an error while fetching events!');
                        }
                    },
                    eventContent: function(arg) {
                        let props = arg.event.extendedProps;
                        let title = arg.event.title;
                        let content = `<div class="fc-event-title">${title}</div>`;
                        if (props.isZoom) {
                            content += `<div class="text-xs">ID: ${props.zoomMeetingId}</div>`;
                        }
                        return { html: content };
                    },
                    eventClick: function(info) {
                        const modal = document.getElementById('eventDetailModal');
                        const props = info.event.extendedProps;

                        document.getElementById('modalTitle').innerText = info.event.title;
                        document.getElementById('modalTime').innerText = formatEventTime(info.event.start, info.event.end);
                        document.getElementById('modalDescription').innerHTML = props.description || '';

                        const zoomDetails = document.getElementById('modalZoomDetails');
                        const joinBtn = document.getElementById('joinZoomBtn');
                        if (props.isZoom) {
                            document.getElementById('modalZoomId').innerText = props.zoomMeetingId;
                            document.getElementById('modalZoomPassword').innerText = props.zoomPassword;
                            document.getElementById('zoomLinkInput').value = props.joinUrl;
                            joinBtn.href = props.joinUrl;
                            zoomDetails.classList.remove('hidden');
                            joinBtn.classList.remove('hidden');
                        } else {
                            zoomDetails.classList.add('hidden');
                            joinBtn.classList.add('hidden');
                        }

                        loadCandidateInfo(props.candidate_id);

                        modal.classList.remove('hidden');
                    }
                });

                calendar.render();
            }

            function formatEventTime(start, end) {
                const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
                let timeText = new Date(start).toLocaleString('en-US', options);
                if (end) {
                    timeText += ' - ' + new Date(end).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                } else {
                    // Handle all-day events if necessary
                }
                return timeText;
            }

            async function loadCandidateInfo(candidateId) {
                const infoBox = document.getElementById('modalCandidateInfo');
                if (!candidateId) {
                    infoBox.classList.add('hidden');
                    return;
                }

                try {
                    // This assumes you have an API endpoint to get candidate details
                    const response = await fetch(`/api/candidates/${candidateId}`, {
                         headers: {
                            'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
                            'Accept': 'application/json'
                        }
                    });
                    if (!response.ok) throw new Error('Candidate not found');
                    
                    const candidate = await response.json();
                    document.getElementById('candidateName').innerText = `Name: ${candidate.name}`;
                    const timelineLink = document.getElementById('candidateTimelineLink');
                    timelineLink.href = `/candidates/${candidate.id}`;
                    timelineLink.onclick = (e) => {
                        e.preventDefault();
                        updateCandidateTimeline(candidate.id);
                        // You might want to close the modal here
                        document.getElementById('eventDetailModal').classList.add('hidden');
                    };
                    infoBox.classList.remove('hidden');
                } catch (error) {
                    console.error('Error loading candidate info:', error);
                    infoBox.classList.add('hidden');
                }
            }

            function updateCandidateTimeline(candidateId) {
                // This is a placeholder for your existing timeline update logic.
                // For example, you might make an AJAX call to refresh a timeline component on your page.
                console.log(`Updating timeline for candidate ID: ${candidateId}`);
                // Example: document.dispatchEvent(new CustomEvent('update-timeline', { detail: { candidateId } }));
            }

            // Modal button handlers
            document.getElementById('closeModalBtn').addEventListener('click', () => {
                document.getElementById('eventDetailModal').classList.add('hidden');
            });

            document.getElementById('copyLinkBtn').addEventListener('click', function() {
                const input = document.getElementById('zoomLinkInput');
                input.select();
                document.execCommand('copy');
                this.innerText = 'Copied!';
                setTimeout(() => { this.innerText = 'Copy'; }, 2000);
            });

            // Initial load
            // You can get the candidate ID from the URL or another element
            // to filter the calendar on page load.
            const urlParams = new URLSearchParams(window.location.search);
            const candidateId = urlParams.get('candidate_id');
            initializeCalendar(candidateId);

        });
    </script>
    @endpush
</x-app-layout>
