<?php $page = 'inbox'; ?>
@extends('layout.mainlayout')

@section('content')
    {{-- Main Wrapper --}}
    <div class="page-wrapper">
        <div class="content container-fluid">

            {{-- Page Header --}}
            <div class="page-header">
                <div class="content-page-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Mailbox</h5>
                    <div class="list-btn">
                        <button class="btn btn-outline-primary btn-print rounded-pill shadow-sm px-4" onclick="window.print()">
                            <i class="fas fa-print me-2"></i> Print Inbox
                        </button>
                    </div>
                </div>
            </div>

            <div class="row">
                {{-- Sidebar Navigation --}}
                <div class="col-xl-4 col-lg-5">
                    <div class="card shadow-sm border-0 sticky-top" style="border-radius: 20px; top: 20px;">
                        <div class="card-body p-4">
                            
                            {{-- Compose Button --}}
                            <div class="compose-btn mb-4">
                                <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#compose_modal" 
                                   class="btn btn-primary w-100 shadow rounded-pill py-3 fw-bold fs-5">
                                    <i class="fas fa-edit me-2"></i> Compose
                                </a>
                            </div>

                            {{-- Navigation Menu with Functional Badges --}}
                            <ul class="inbox-menu list-unstyled mb-0">
                                <li class="mb-2">
                                    <a href="javascript:void(0);" 
                                       onclick="Livewire.dispatch('setFolder', { name: 'inbox' })"
                                       class="folder-link d-flex justify-content-between align-items-center p-3 rounded-4 text-decoration-none active-folder">
                                        <span class="fs-6"><i class="fas fa-inbox me-3"></i> Inbox</span>
                                        <span class="badge bg-primary rounded-pill px-3 shadow-sm">{{ Auth::user()->unreadCount() }}</span>
                                    </a>
                                </li>
                                <li class="mb-2">
                                    <a href="javascript:void(0);" 
                                       onclick="Livewire.dispatch('setFolder', { name: 'sent' })" 
                                       class="folder-link d-flex justify-content-between align-items-center p-3 rounded-4 text-decoration-none hover-menu fs-6">
                                        <span><i class="far fa-paper-plane me-3"></i> Sent Mail</span>
                                        <span class="badge bg-secondary rounded-pill px-3 shadow-sm">{{ Auth::user()->sentCount() }}</span>
                                    </a>
                                </li>
                                <li class="mb-2">
                                    <a href="javascript:void(0);" 
                                       onclick="Livewire.dispatch('setFolder', { name: 'trash' })" 
                                       class="folder-link d-flex justify-content-between align-items-center p-3 rounded-4 text-decoration-none hover-menu fs-6">
                                        <span><i class="far fa-trash-alt me-3"></i> Trash Bin</span>
                                        <span class="badge bg-danger rounded-pill px-3 shadow-sm">{{ Auth::user()->trashCount() }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Livewire Content Area --}}
                <div class="col-xl-8 col-lg-7">
                    <div class="card shadow-sm border-0" style="border-radius: 20px; min-height: 75vh;">
                        <div class="card-body p-0">
                            {{-- This Livewire component must contain the toggleMessage() triggers --}}
                            @livewire('inbox-component')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Compose Modal --}}
    <div class="modal fade" id="compose_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0" style="border-radius: 20px; overflow: hidden;">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold text-white">New Message</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('messages.store') }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold">To:</label>
                            <select name="receiver_id" id="compose-recipient-select" class="form-control" required>
                                <option value="">Select Recipient...</option>
                                @foreach(\App\Models\User::where('id', '!=', Auth::id())->get() as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Subject:</label>
                            <input type="text" name="subject" class="form-control" placeholder="Brief summary" required>
                        </div>
                        <div class="mb-0">
                            <label class="form-label fw-bold">Message Content:</label>
                            <textarea name="content" class="form-control" rows="8" placeholder="Type your message here..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-5 shadow-sm">
                            Send <i class="fas fa-paper-plane ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .folder-link { transition: all 0.3s ease; color: #555; cursor: pointer; }
        .active-folder { background-color: rgba(0, 123, 255, 0.12) !important; color: #007bff !important; font-weight: bold; }
        .hover-menu:hover { background-color: #f4f7fe; color: #007bff !important; padding-left: 1.8rem !important; }
        .form-control { border-radius: 10px; border: 1px solid #e0e0e0; padding: 12px; }
        
        /* Expandable Content Styling */
        .message-content-row { 
            background-color: #fafafa; 
            border-bottom: 1px solid #eee;
            transition: all 0.3s ease-in-out;
        }
        .content-inner { 
            border-left: 4px solid #007bff; 
            padding: 20px; 
            background: white; 
            margin: 10px; 
            border-radius: 0 10px 10px 0; 
            box-shadow: inset 0 0 5px rgba(0,0,0,0.05); 
        }

        #compose_modal .select2-container {
            width: 100% !important;
        }

        #compose_modal .select2-container .select2-selection--single {
            height: 56px;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            padding: 0 12px;
        }

        #compose_modal .select2-container .select2-selection__rendered {
            line-height: 1.4;
            padding-left: 0;
            color: #212529;
        }

        #compose_modal .select2-container .select2-selection__arrow {
            height: 54px;
            right: 10px;
        }

        @media print {
            .page-wrapper { margin-left: 0 !important; }
            .col-xl-4, .btn-print, .compose-btn, .modal { display: none !important; }
            .col-xl-8 { width: 100% !important; flex: 0 0 100% !important; max-width: 100% !important; }
            .card { border: none !important; box-shadow: none !important; }
        }
    </style>

    <script>
        // Folder Switching UI Logic
        document.querySelectorAll('.folder-link').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.folder-link').forEach(i => i.classList.remove('active-folder'));
                this.classList.add('active-folder');
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            const composeModal = document.getElementById('compose_modal');
            const recipientSelect = $('#compose-recipient-select');

            if (!composeModal || !recipientSelect.length || !$.fn.select2) {
                return;
            }

            const initializeRecipientSearch = function () {
                if (recipientSelect.hasClass('select2-hidden-accessible')) {
                    return;
                }

                recipientSelect.select2({
                    dropdownParent: $('#compose_modal'),
                    width: '100%',
                    placeholder: 'Search recipient by name or email',
                    allowClear: true
                });
            };

            composeModal.addEventListener('shown.bs.modal', function () {
                initializeRecipientSearch();
                recipientSelect.select2('open');
            });

            composeModal.addEventListener('hidden.bs.modal', function () {
                recipientSelect.val('').trigger('change');
            });
        });

        /**
         * Core Expansion Script:
         * This script reveals the hidden message row within the Livewire table.
         */
        function toggleMessage(id) {
            const contentRow = document.getElementById(id);
            if (!contentRow) {
                console.error("Message row with ID " + id + " not found.");
                return;
            }
            
            const isHidden = contentRow.classList.contains('d-none');
            
            // Step 1: Hide any other messages that might be open
            document.querySelectorAll('.message-content-row').forEach(row => {
                row.classList.add('d-none');
            });
            
            // Step 2: Toggle the clicked message
            if (isHidden) {
                contentRow.classList.remove('d-none');
            }
        }
    </script>
@endsection
