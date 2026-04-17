@extends('layout.mainlayout')

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            @component('components.page-header')
                @slot('title') Calendar @endslot
            @endcomponent

            <div class="row">

                <div class="col-lg-3 col-md-4">
                    <div class="card shadow-sm border-0" style="border-radius: 15px;">
                        <div class="card-body">

                            <h5 class="card-title mb-3 fw-bold">Add Event</h5>
                            <form id="sidebar_add_event_form" class="mb-4">
                                <div class="mb-3">
                                    <input type="text" id="sidebar_event_name" class="form-control form-control-sm" placeholder="Event Name" required>
                                </div>
                                <div class="mb-3">
                                    <input type="date" id="sidebar_event_date" class="form-control form-control-sm" required>
                                </div>
                                <button type="submit" id="sidebar_submit_btn" class="btn btn-primary w-100 rounded-pill shadow-sm">
                                    <span id="btn_text">Add Event</span>
                                    <span id="btn_spinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                                </button>
                            </form>

                            <hr>

                            <h5 class="card-title mb-3 fw-bold">Drag & Drop</h5>
                            <div id="external-events" class="mb-3">
                                <div class="fc-event calendar-events p-2 mb-2 rounded border bg-info-light text-info" 
                                     data-class="bg-info" style="cursor: move;">
                                    <i class="fas fa-circle me-2"></i> Meeting
                                </div>
                                <div class="fc-event calendar-events p-2 mb-2 rounded border bg-success-light text-success" 
                                     data-class="bg-success" style="cursor: move;">
                                    <i class="fas fa-circle me-2"></i> Task Done
                                </div>
                            </div>

                            <button type="button" class="btn btn-outline-primary w-100 shadow-sm rounded-pill mt-2" data-bs-toggle="modal" data-bs-target="#add_category_modal">
                                <i class="fas fa-plus me-1"></i> Add Category
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-lg-9 col-md-8">
                    <div class="card bg-white shadow-sm border-0" style="border-radius: 15px;">
                        <div class="card-body">
                            <div id="calendar"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="add_category_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" id="new_category_name" class="form-control" placeholder="Meeting">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Choose Color</label>
                        <select id="new_category_color" class="form-select">
                            <option value="bg-primary">Blue</option>
                            <option value="bg-success">Green</option>
                            <option value="bg-danger">Red</option>
                            <option value="bg-warning">Yellow</option>
                            <option value="bg-info">Light Blue</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="save_category_btn">Add Category</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="edit_event_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_event_id">
                    <div class="mb-3">
                        <label class="form-label">Event Title</label>
                        <input type="text" id="edit_event_title" class="form-control">
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-danger" id="delete_event_btn">Delete Event</button>
                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="update_event_btn">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var containerEl = document.getElementById('external-events');
        var csrfToken = '{{ csrf_token() }}';

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            editable: true,
            droppable: true,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: '/api/events',

            receive: function(info) {
                let eventData = {
                    title: info.event.title,
                    start: info.event.startStr,
                    category_color: info.event.classNames[0] || 'bg-primary',
                    _token: csrfToken
                };
                saveEventToDB(eventData);
            },

            eventDrop: function(info) { updateEvent(info.event); },
            eventResize: function(info) { updateEvent(info.event); },

            eventClick: function(info) {
                document.getElementById('edit_event_id').value = info.event.id;
                document.getElementById('edit_event_title').value = info.event.title;
                var editModal = new bootstrap.Modal(document.getElementById('edit_event_modal'));
                editModal.show();

                document.getElementById('update_event_btn').onclick = function() {
                    updateEvent(info.event, document.getElementById('edit_event_title').value);
                    editModal.hide();
                };

                document.getElementById('delete_event_btn').onclick = function() {
                    if (confirm("Delete this event?")) {
                        fetch(`/api/events/destroy/${info.event.id}`, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': csrfToken }
                        }).then(() => {
                            info.event.remove();
                            editModal.hide();
                        });
                    }
                };
            }
        });

        calendar.render();

        // SIDEBAR FORM LOGIC (Manual Add)
        const sidebarForm = document.getElementById('sidebar_add_event_form');
        const submitBtn = document.getElementById('sidebar_submit_btn');
        const btnText = document.getElementById('btn_text');
        const btnSpinner = document.getElementById('btn_spinner');

        sidebarForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitBtn.disabled = true;
            btnText.classList.add('d-none');
            btnSpinner.classList.remove('d-none');

            const payload = {
                title: document.getElementById('sidebar_event_name').value,
                start: document.getElementById('sidebar_event_date').value,
                category_color: 'bg-primary',
                _token: csrfToken
            };

            fetch('/api/events/store', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(() => {
                calendar.refetchEvents();
                sidebarForm.reset();
            })
            .finally(() => {
                submitBtn.disabled = false;
                btnText.classList.remove('d-none');
                btnSpinner.classList.add('d-none');
            });
        });

        function saveEventToDB(data) {
            fetch('/api/events/store', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(data)
            }).then(() => calendar.refetchEvents());
        }

        function updateEvent(event, newTitle = null) {
            fetch('/api/events/update/' + event.id, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({
                    title: newTitle || event.title,
                    start: event.startStr,
                    end: event.endStr
                })
            }).then(() => { if(newTitle) event.setProp('title', newTitle); });
        }

        new FullCalendar.Draggable(containerEl, {
            itemSelector: '.fc-event',
            eventData: function(eventEl) {
                return { title: eventEl.innerText.trim(), className: eventEl.getAttribute('data-class') };
            }
        });

        document.getElementById('save_category_btn').addEventListener('click', function() {
            let name = document.getElementById('new_category_name').value;
            let color = document.getElementById('new_category_color').value;
            if (name) {
                let html = `<div class="fc-event calendar-events p-2 mb-2 rounded border ${color}-light text-dark" data-class="${color}" style="cursor: move;"><i class="fas fa-circle ${color.replace('bg-', 'text-')} me-2"></i> ${name}</div>`;
                containerEl.insertAdjacentHTML('beforeend', html);
                bootstrap.Modal.getInstance(document.getElementById('add_category_modal')).hide();
                document.getElementById('new_category_name').value = '';
            }
        });
    });
</script>
@endpush