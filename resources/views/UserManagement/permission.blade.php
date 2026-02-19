<?php $page = 'permission'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">

            <div class="page-header">
                <div class="content-page-header">
                    <h5>Permission Settings</h5>
                </div>
                <div class="role-testing d-flex align-items-center justify-content-between">
                    <h6>Role Name: <span class="badge badge-primary ms-1">{{ $role->name }}</span></h6>
                </div>
            </div>

            <form action="{{ route('roles.permissions.update') }}" method="POST">
                @csrf
                <input type="hidden" name="role_id" value="{{ $role->id }}">
                
                <div class="row">
                    <div class="col-sm-12">
                        <div class="card-table">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-stripped table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Modules</th>
                                                <th>Sub Modules</th>
                                                <th class="text-center">Create</th>
                                                <th class="text-center">Edit</th>
                                                <th class="text-center">Delete</th>
                                                <th class="text-center">View</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $json = file_get_contents(public_path('assets/json/permission.json'));
                                                $modules = json_decode($json, true);
                                            @endphp
                                            @foreach ($modules as $module)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td class="role-data">{{ $module['Modules'] }}</td>
                                                    <td>{{ $module['SubModules'] }}</td>
                                                    
                                                    @foreach(['create', 'edit', 'delete', 'view'] as $action)
                                                        <td class="text-center">
                                                            <label class="custom_check">
                                                                <input type="checkbox" 
                                                                       name="permissions[{{ $module['Modules'] }}][{{ $module['SubModules'] }}][{{ $action }}]" 
                                                                       value="1">
                                                                <span class="checkmark"></span>
                                                            </label>
                                                        </td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="btn-center my-4 text-center">
                    <a href="{{ route('roles.index') }}" class="btn btn-secondary me-2">Back to Roles</a>
                    <button type="submit" class="btn btn-primary">Save Permissions</button>
                </div>
            </form>

        </div>
    </div>
@endsection