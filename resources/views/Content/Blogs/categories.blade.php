<?php $page = 'categories'; ?>
@extends('layout.mainlayout')

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">

            @component('components.page-header')
                @slot('title')
                    Categories
                @endslot
            @endcomponent

            @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
            @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

            @component('components.search-filter')
            @endcomponent
            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-stripped table-hover datatable">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Category Name</th>
                                            <th>Date</th>
                                            <th>Added By</th>
                                            <th>Status</th>
                                            <th class="no-sort">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($categories ?? [] as $category)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>
                                                    <h2 class="table-avatar">
                                                        <img class="avatar-img rounded me-2" width="30" height="30"
                                                            src="{{ URL::asset('/assets/img/category/default.png') }}"
                                                            alt="Category Image">
                                                        {{ $category->name ?? 'N/A' }}
                                                    </h2>
                                                </td>

                                                <td>
                                                    @if(isset($category->created_at))
                                                        {{ is_string($category->created_at) ? date('d M Y', strtotime($category->created_at)) : $category->created_at->format('d M Y') }}
                                                    @else
                                                        N/A
                                                    @endif
                                                </td>
                                                <td>
                                                    <h2 class="table-avatar">
                                                        <a href="{{ url('profile') }}" class="avatar avatar-sm me-2">
                                                            <img class="avatar-img rounded-circle"
                                                                src="{{ URL::asset('/assets/img/profiles/avatar-01.jpg') }}"
                                                                alt="User Image">
                                                        </a>
                                                        <a href="{{ url('profile') }}">Admin</a>
                                                    </h2>
                                                </td>
                                                <td>
                                                    <div class="status-toggle">
                                                        <input id="status_{{ $category->id }}" class="check" type="checkbox" checked="">
                                                        <label for="status_{{ $category->id }}" class="checktoggle checkbox-bg">Active</label>
                                                    </div>
                                                </td>
                                                <td class="d-flex align-items-center">
                                                    <a class="btn-action-icon me-2" href="javascript:void(0);" 
                                                       data-bs-toggle="modal" data-bs-target="#editCategory{{ $category->id }}">
                                                        <i class="fe fe-edit"></i>
                                                    </a>

                                                    <form action="{{ route('categories.destroy', $category->id) }}" method="POST" style="display:inline;">
                                                        @csrf 
                                                        @method('DELETE')
                                                        <button type="submit" class="btn-action-icon" style="background:none; border:none;" 
                                                                onclick="return confirm('Are you sure?')">
                                                            <i class="fe fe-trash-2"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>

                                            <div class="modal fade" id="editCategory{{ $category->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <form action="{{ route('categories.update', $category->id) }}" method="POST">
                                                            @csrf @method('PUT')
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Edit Category</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="form-group">
                                                                    <label>Category Name</label>
                                                                    <input type="text" name="name" class="form-control" value="{{ $category->name ?? '' }}" required>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="submit" class="btn btn-primary">Update</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4">No categories found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addCategory" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('categories.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Category Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection