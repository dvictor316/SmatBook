<?php $page = 'badges'; ?>
@extends('layout.mainlayout')
@section('content')
			<!-- Page Wrapper -->
            <div class="page-wrapper">
                <div class="content container-fluid">
                    <!-- Page Header -->
                    <div class="page-header">
						<div class="content-page-header">
							<h5>Badges</h5>
						</div>	
					</div>	
                    <!-- /Page Header -->
                    <div class="row">
                        <div class="col-xxl-6">
                            <div class="card">
                                <div class="card-header align-items-center d-flex">
                                    <h4 class="card-title mb-0">Default Badges</h4>
                                </div>
                                <!-- end card header -->

                                <div class="card-body">
                                    <p class="text-muted">Use the <code>badge</code> class to set a default badge.</p>
                                    <div class="d-flex flex-wrap gap-2">
                                        <span class="badge bg-primary">Primary</span>
                                        <span class="badge bg-secondary">Secondary</span>
                                        <span class="badge bg-success">Success</span>
                                        <span class="badge bg-info">Info</span>
                                        <span class="badge bg-warning">Warning</span>
                                        <span class="badge bg-danger">Danger</span>
                                        <span class="badge bg-dark">Dark</span>
                                        <span class="badge bg-light text-dark">Light</span>
                                    </div>
                                </div>
                                <!-- end card-body -->
                            </div>
                            <!-- end card -->
                        </div>
                        <div class="col-xxl-6">
                            <div class="card">
                                <div class="card-header align-items-center d-flex">
                                    <h4 class="card-title mb-0">Soft Badges </h4>
                                </div>
                                <!-- end card header -->
                                <div class="card-body">
                                    <p class="text-muted">Use the <code>badge-soft-</code> class with below-mentioned variation to create softer badge.</p>
                                    <div class="d-flex flex-wrap gap-2">
                                        <span class="badge badge-soft-primary">Primary</span>
                                        <span class="badge badge-soft-secondary">Secondary</span>
                                        <span class="badge badge-soft-success">Success</span>
                                        <span class="badge badge-soft-info">Info</span>
                                        <span class="badge badge-soft-warning">Warning</span>
                                        <span class="badge badge-soft-danger">Danger</span>
                                        <span class="badge badge-soft-dark">Dark</span>
                                        <span class="badge badge-soft-light text-dark">Light</span>
                                    </div>
                                </div>
                                <!-- end card-body -->
                            </div>
                            <!-- end card -->
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xxl-6">
                            <div class="card">
                                <div class="card-header align-items-center d-flex">
                                    <h4 class="card-title mb-0">Outline Badges </h4>
                                </div>
                                <!-- end card header -->
                                <div class="card-body">
                                    <p class="text-muted">Use the <code>badge-outline-</code> class with the
                                        below-mentioned variation to create a badge with the outline.</p>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="#" class="badge badge-outline-primary">Primary</a>
                                        <span class="badge badge-outline-secondary">Secondary</span>
                                        <span class="badge badge-outline-success">Success</span>
                                        <span class="badge badge-outline-info">Info</span>
                                        <span class="badge badge-outline-warning">Warning</span>
                                        <span class="badge badge-outline-danger">Danger</span>
                                        <span class="badge badge-outline-dark">Dark</span>
                                        <span class="badge badge-outline-light text-dark">Light</span>
                                    </div>
                                </div>
                                <!-- end card-body -->
                            </div>
                            <!-- end card -->
                        </div>

                        <div class="col-xxl-6">
                            <div class="card">
                                <div class="card-header align-items-center d-flex">
                                    <h4 class="card-title mb-0">Rounded Pill Badges </h4>
                                </div>
                                <!-- end card header -->

                                <div class="card-body">
                                    <p class="text-muted">Use the <code>rounded-pill</code> class to make badges more rounded with a larger border-radius.</p>
                                    <div class="d-flex flex-wrap gap-2">
                                        <span class="badge rounded-pill bg-primary">Primary</span>
                                        <span class="badge rounded-pill bg-secondary">Secondary</span>
                                        <span class="badge rounded-pill bg-success">Success</span>
                                        <span class="badge rounded-pill bg-info">Info</span>
                                        <span class="badge rounded-pill bg-warning">Warning</span>
                                        <span class="badge rounded-pill bg-danger">Danger</span>
                                        <span class="badge rounded-pill bg-dark">Dark</span>
                                        <span class="badge rounded-pill bg-light text-dark">Light</span>
                                    </div>
                                </div>
                                <!-- end card-body -->
                            </div>
                            <!-- end card -->
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xxl-6">
                            <div class="card">
                                <div class="card-header align-items-center d-flex">
                                    <h4 class="card-title mb-0">Rounded Pill Badges with soft effect </h4>
                                </div>
                                <!-- end card header -->

                                <div class="card-body">
                                    <p class="text-muted">Use the <code>rounded-pill badge-soft-</code> class with the below-mentioned variation to create a badge more rounded with a soft background.</p>
                                    <div class="d-flex flex-wrap gap-2">
                                        <span class="badge rounded-pill badge-soft-primary">Primary</span>
                                        <span class="badge rounded-pill badge-soft-secondary">Secondary</span>
                                        <span class="badge rounded-pill badge-soft-success">Success</span>
                                        <span class="badge rounded-pill badge-soft-info">Info</span>
                                        <span class="badge rounded-pill badge-soft-warning">Warning</span>
                                        <span class="badge rounded-pill badge-soft-danger">Danger</span>
                                        <span class="badge rounded-pill badge-soft-dark">Dark</span>
                                        <span class="badge rounded-pill badge-soft-light text-dark">Light</span>
                                    </div>
                                </div>
                                <!-- end card-body -->
                            </div>
                            <!-- end card -->
                        </div>

                        <div class="col-xxl-6">
                            <div class="card">
                                <div class="card-header align-items-center d-flex">
                                    <h4 class="card-title mb-0">Soft Border Badges</h4>
                                </div>
                                <!-- end card header -->

                                <div class="card-body">
                                    <p class="text-muted">
                                        Use the <code>badge-border</code> and <code>badge-soft-</code> with below
                                        mentioned modifier classes to make badges with border & soft backgorund.
                                    </p>
                                    <div class="d-flex flex-wrap gap-2">
                                        <span class="badge badge-soft-primary badge-border">Primary</span>
                                        <span class="badge badge-soft-secondary badge-border">Secondary</span>
                                        <span class="badge badge-soft-success badge-border">Success</span>
                                        <span class="badge badge-soft-danger badge-border">Danger</span>
                                        <span class="badge badge-soft-warning badge-border">Warning</span>
                                        <span class="badge badge-soft-info badge-border">Info</span>
                                        <span class="badge badge-soft-dark badge-border">Dark</span>
                                        <span class="badge badge-soft-light badge-border text-dark">Light</span>
                                    </div>
                                </div>
                                <!-- end card-body -->
                            </div>
                            <!-- end card -->
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xxl-6">
                            <div class="card">
                                <div class="card-header align-items-center d-flex">
                                    <h4 class="card-title mb-0">Outline Pill Badges </h4>
                                </div>
                                <!-- end card header -->

                                <div class="card-body">
                                    <p class="text-muted">
                                        Use the <code>rounded-pill badge-outline-</code> class with the below-mentioned
                                        variation to create a outline Pill badge.</p>
                                    <div class="d-flex flex-wrap gap-2">
                                        <span class="badge rounded-pill badge-outline-primary">Primary</span>
                                        <span class="badge rounded-pill badge-outline-secondary">Secondary</span>
                                        <span class="badge rounded-pill badge-outline-success">Success</span>
                                        <span class="badge rounded-pill badge-outline-info">Info</span>
                                        <span class="badge rounded-pill badge-outline-warning">Warning</span>
                                        <span class="badge rounded-pill badge-outline-danger">Danger</span>
                                        <span class="badge rounded-pill badge-outline-dark">Dark</span>
                                        <span class="badge rounded-pill badge-outline-light text-dark">Light</span>
                                    </div>
                                </div>
                                <!-- end card-body -->
                            </div>
                            <!-- end card -->
                        </div>

                        <div class="col-xxl-6">
                            <div class="card">
                                <div class="card-header align-items-center d-flex">
                                    <h4 class="card-title mb-0">Label Badges </h4>
                                </div>
                                <!-- end card header -->

                                <div class="card-body">
                                    <p class="text-muted">
                                        Use the <code>badge-label</code> class to create a badge with the label.</p>
                                    <div class="d-flex flex-wrap gap-2">
                                        <span class="badge badge-label bg-primary"><i class="mdi mdi-circle-medium"></i> Primary</span>
                                        <span class="badge badge-label bg-secondary"><i class="mdi mdi-circle-medium"></i> Secondary</span>
                                        <span class="badge badge-label bg-success"><i class="mdi mdi-circle-medium"></i> Success</span>
                                        <span class="badge badge-label bg-danger"><i class="mdi mdi-circle-medium"></i> Danger</span>
                                        <span class="badge badge-label bg-warning"><i class="mdi mdi-circle-medium"></i> Warning</span>
                                        <span class="badge badge-label bg-info"><i class="mdi mdi-circle-medium"></i> Info</span>
                                        <span class="badge badge-label bg-dark"><i class="mdi mdi-circle-medium"></i> Dark</span>
                                        <span class="badge badge-label bg-light text-dark"><i class="mdi mdi-circle-medium"></i> Light</span>
                                    </div>
                                </div>
                                <!-- end card-body -->
                            </div>
                            <!-- end card -->
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xxl-12">
                            <div class="card">
                                <div class="card-header align-items-center d-flex">
                                    <h4 class="card-title mb-0">Gradient Badges</h4>
                                </div>
                                <!-- end card header -->

                                <div class="card-body">
                                    <p class="text-muted">
                                        Use the <code>badge-gradient-*</code> class to create a gradient styled badge.</p>
                                    <div class="d-flex flex-wrap gap-2">
                                        <span class="badge bg-primary">Primary</span>
                                        <span class="badge bg-secondary">Secondary</span>
                                        <span class="badge bg-success">Success</span>
                                        <span class="badge bg-danger">Danger</span>
                                        <span class="badge bg-warning">Warning</span>
                                        <span class="badge bg-info">Info</span>
                                        <span class="badge bg-dark">Dark</span>
                                    </div>
                                </div>
                                <!-- end card-body -->
                            </div>
                            <!-- end card -->
                        </div>
                        <!--end col-->
                    </div>

                    <div class="row">
                        <div class="col-xxl-6">
                            <div class="card">
                                <div class="card-header align-items-center d-flex">
                                    <h4 class="card-title mb-0">Button Position Badges</h4>
                                </div>
                                <!-- end card header -->

                                <div class="card-body">
                                    <p class="text-muted">Use the below utilities to modify a badge and position it in
                                        the corner of a link or button.</p>
                                    <div class="d-flex flex-wrap gap-4">
                                        <button type="button" class="btn btn-primary position-relative">
                                            Mails <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">+99
                                                <span class="visually-hidden">unread messages</span></span>
                                        </button>
                                        <button type="button" class="btn btn-light position-relative">
                                            Alerts <span class="position-absolute top-0 start-100 translate-middle badge border border-light rounded-circle bg-danger p-1"><span class="visually-hidden">unread messages</span></span>
                                        </button>
                                        <button type="button" class="btn btn-primary position-relative p-0 avatar-xs rounded">
                                            <span class="avatar-title bg-transparent">
                                                <i class="fas fa-envelope"></i>
                                            </span>
                                            <span class="position-absolute top-0 start-100 translate-middle badge border border-light rounded-circle bg-danger p-1"><span class="visually-hidden">unread messages</span></span>
                                        </button>
                                        <button type="button" class="btn btn-light position-relative p-0 avatar-xs rounded-circle">
                                            <span class="avatar-title bg-transparent text-reset">
                                                <i class="fas fa-bell"></i>
                                            </span>
                                        </button>
                                        <button type="button" class="btn btn-light position-relative p-0 avatar-xs rounded-circle">
                                            <span class="avatar-title bg-transparent text-reset">
                                                <i class="fas fa-bars"></i>
                                            </span>
                                            <span class="position-absolute top-0 start-100 translate-middle badge border border-light rounded-circle bg-success p-1"><span class="visually-hidden">unread messages</span></span>
                                        </button>
                                    </div>
                                </div>
                                <!-- end card-body -->
                            </div>
                            <!-- end card -->
                            <div class="card">
                                <div class="card-header align-items-center d-flex">
                                    <h4 class="card-title mb-0">Badges With Button</h4>
                                </div>
                                <!-- end card header -->
                                <div class="card-body">
                                    <p class="text-muted">Badges can be used as part of buttons to provide a counter.</p>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="button" class="btn btn-primary">
                                            Notifications <span class="badge bg-success ms-1">4</span>
                                        </button>
                                        <button type="button" class="btn btn-success">
                                            Messages <span class="badge bg-danger ms-1">2</span>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary">
                                            Draft <span class="badge bg-success ms-1">2</span>
                                        </button>
                                    </div>
                                </div>
                                <!-- end card-body -->
                            </div>
                            <!-- end card -->
                        </div>
                        <div class="col-xxl-6">
                            <div class="card">
                                <div class="card-header align-items-center d-flex">
                                    <h4 class="card-title mb-0">Badges with Heading</h4>
                                </div>
                                <!-- end card header -->
                                <div class="card-body">
                                    <p class="text-muted">Example of the badge used in the HTML Heading.</p>
                                    <div>
                                        <h1>Example heading <span class="badge bg-secondary">New</span></h1>
                                        <h2>Example heading <span class="badge bg-secondary">New</span></h2>
                                        <h3>Example heading <span class="badge bg-secondary">New</span></h3>
                                        <h4>Example heading <span class="badge bg-secondary">New</span></h4>
                                        <h5>Example heading <span class="badge bg-secondary">New</span></h5>
                                        <h6 class="mb-0">Example heading <span class="badge bg-secondary">New</span></h6>
                                    </div>
                                </div>
                                <!-- end card-body -->
                            </div>
                            <!-- end card -->
                        </div>
                    </div>
                     
                </div>
            </div>
            <!-- /Page Wrapper -->
@endsection