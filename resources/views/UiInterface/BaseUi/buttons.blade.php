<?php $page = 'buttons'; ?>
@extends('layout.mainlayout')
@section('content')
			<!-- Page Wrapper -->
            <div class="page-wrapper">
                <div class="content container-fluid">
				
					<!-- Page Header -->
                    <div class="page-header">
						<div class="content-page-header">
							<h5>Buttons</h5>
						</div>	
					</div>	
                    <!-- /Page Header -->
											
					<!-- Buttons -->
					<div class="card card-buttons">
						<div class="card-body">
							<h5 class="card-title">Default Button</h5>
							<button type="button" class="btn btn-primary">Primary</button>
							<button type="button" class="btn btn-secondary">Secondary</button>
							<button type="button" class="btn btn-success">Success</button>
							<button type="button" class="btn btn-danger">Danger</button>
							<button type="button" class="btn btn-warning">Warning</button>
							<button type="button" class="btn btn-info">Info</button>
							<button type="button" class="btn btn-light">Light</button>
							<button type="button" class="btn btn-dark">Dark</button>
							<button type="button" class="btn btn-link">Link</button>
							<hr>
							<h5 class="card-title">Button Sizes</h5>
							<p>
								<button type="button" class="btn btn-primary btn-lg">Primary</button>
								<button type="button" class="btn btn-secondary btn-lg">Secondary</button>
								<button type="button" class="btn btn-success btn-lg">Success</button>
								<button type="button" class="btn btn-danger btn-lg">Danger</button>
								<button type="button" class="btn btn-warning btn-lg">Warning</button>
								<button type="button" class="btn btn-info btn-lg">Info</button>
								<button type="button" class="btn btn-light btn-lg">Light</button>
								<button type="button" class="btn btn-dark btn-lg">Dark</button>
							</p>
							<p>
								<button type="button" class="btn btn-primary">Primary</button>
								<button type="button" class="btn btn-secondary">Secondary</button>
								<button type="button" class="btn btn-success">Success</button>
								<button type="button" class="btn btn-danger">Danger</button>
								<button type="button" class="btn btn-warning">Warning</button>
								<button type="button" class="btn btn-info">Info</button>
								<button type="button" class="btn btn-light">Light</button>
								<button type="button" class="btn btn-dark">Dark</button>
							</p>
							<p>
								<button type="button" class="btn btn-primary btn-sm">Primary</button>
								<button type="button" class="btn btn-secondary btn-sm">Secondary</button>
								<button type="button" class="btn btn-success btn-sm">Success</button>
								<button type="button" class="btn btn-danger btn-sm">Danger</button>
								<button type="button" class="btn btn-warning btn-sm">Warning</button>
								<button type="button" class="btn btn-info btn-sm">Info</button>
								<button type="button" class="btn btn-light btn-sm">Light</button>
								<button type="button" class="btn btn-dark btn-sm">Dark</button>
							</p>									
						</div>
					</div>							
					<!-- Rounded Button -->
					<div class="card card-buttons">
						<div class="card-header">
							<h5 class="card-title">Rounded Button</h5>
							<p class="card-text">use <code>.btn-rounded</code> in class <code>.btn</code> class to get Rounded button</p>
						</div>
						<div class="card-body">
							<button type="button" class="btn btn-rounded btn-primary">Primary</button>
							<button type="button" class="btn btn-rounded btn-secondary">Secondary</button>
							<button type="button" class="btn btn-rounded btn-success">Success</button>
							<button type="button" class="btn btn-rounded btn-danger">Danger</button>
							<button type="button" class="btn btn-rounded btn-warning">Warning</button>
							<button type="button" class="btn btn-rounded btn-info">Info</button>
							<button type="button" class="btn btn-rounded btn-light">Light</button>
							<button type="button" class="btn btn-rounded btn-dark">Dark</button>
							<hr>
							<p>use <code>.btn-rounded</code> in class <code>.btn-outline-*</code> class to get Rounded Outline button</p>
							<button type="button" class="btn btn-rounded btn-outline-primary">Primary</button>
							<button type="button" class="btn btn-rounded btn-outline-secondary">Secondary</button>
							<button type="button" class="btn btn-rounded btn-outline-success">Success</button>
							<button type="button" class="btn btn-rounded btn-outline-danger">Danger</button>
							<button type="button" class="btn btn-rounded btn-outline-warning">Warning</button>
							<button type="button" class="btn btn-rounded btn-outline-info">Info</button>
							<button type="button" class="btn btn-rounded btn-outline-light">Light</button>
							<button type="button" class="btn btn-rounded btn-outline-dark">Dark</button>
						</div>
					</div>
					<!-- /Rounded Button -->
					
					<!-- Outline Buttons -->
					<div class="card card-buttons">
						<div class="card-header">
							<h5 class="card-title">Outline Buttons</h5>
							<p class="card-text">Use <code>.btn-outline-*</code> class for outline buttons.</p>
						</div>
						<div class="card-body">
							<div class="row row-sm align-items-center">
								<div class="col-12 col-xl mb-3 mb-xl-0">Normal</div>
								<div class="col-6 col-sm-4 col-md-2 col-xl mb-3 mb-xl-0">
									<button type="button" class="btn w-100 btn-outline-primary">Primary</button>
								</div>
								<div class="col-6 col-sm-4 col-md-2 col-xl mb-3 mb-xl-0">
									<button type="button" class="btn w-100 btn-outline-secondary">Secondary</button>
								</div>
								<div class="col-6 col-sm-4 col-md-2 col-xl mb-3 mb-xl-0">
									<button type="button" class="btn w-100 btn-outline-success">Success</button>
								</div>
								<div class="col-6 col-sm-4 col-md-2 col-xl mb-3 mb-xl-0">
									<button type="button" class="btn w-100 btn-outline-warning">Warning</button>
								</div>
								<div class="col-6 col-sm-4 col-md-2 col-xl mb-3 mb-xl-0">
									<button type="button" class="btn w-100 btn-outline-danger">Danger</button>
								</div>
								<div class="col-6 col-sm-4 col-md-2 col-xl mb-3 mb-xl-0">
									<button type="button" class="btn w-100 btn-outline-info">Info</button>
								</div>
								<div class="col-6 col-sm-4 col-md-2 col-xl mb-3 mb-xl-0">
									<button type="button" class="btn w-100 btn-outline-light">Light</button>
								</div>
								<div class="col-6 col-sm-4 col-md-2 col-xl mb-3 mb-xl-0">
									<button type="button" class="btn w-100 btn-outline-dark">Dark</button>
								</div>
							</div>
							<div class="row row-sm align-items-center mt-3">
								<div class="col-12 col-xl mb-3 mb-xl-0">Active</div>
								<div class="col-6 col-sm-4 col-md-2 col-xl mb-3 mb-xl-0">
									<button type="button" class="btn w-100 btn-outline-primary active">Primary</button>
								</div>
								<div class="col-6 col-sm-4 col-md-2 col-xl mb-3 mb-xl-0">
									<button type="button" class="btn w-100 btn-outline-secondary active">Secondary</button>
								</div>
								<div class="col-6 col-sm-4 col-md-2 col-xl mb-3 mb-xl-0">
									<button type="button" class="btn w-100 btn-outline-success active">Success</button>
								</div>
								<div class="col-6 col-sm-4 col-md-2 col-xl mb-3 mb-xl-0">
									<button type="button" class="btn w-100 btn-outline-warning active">Warning</button>
								</div>
								<div class="col-6 col-sm-4 col-md-2 col-xl mb-3 mb-xl-0">
									<button type="button" class="btn w-100 btn-outline-danger active">Danger</button>
								</div>
								<div class="col-6 col-sm-4 col-md-2 col-xl mb-3 mb-xl-0">
									<button type="button" class="btn w-100 btn-outline-info active">Info</button>
								</div>
								<div class="col-6 col-sm-4 col-md-2 col-xl mb-3 mb-xl-0">
									<button type="button" class="btn w-100 btn-outline-light active">Light</button>
								</div>
								<div class="col-6 col-sm-4 col-md-2 col-xl mb-3 mb-xl-0">
									<button type="button" class="btn w-100 btn-outline-dark active">Dark</button>
								</div>
							</div>
							<div class="row row-sm align-items-center mt-3">
								<div class="col-12 col-xl mb-3 mb-xl-0">Disabled</div>
								<div class="col-6 col-sm-4 col-md-2 col-xl mb-3 mb-xl-0">
									<button disabled="" type="button" class="btn w-100 btn-outline-primary">Primary</button>
								</div>
								<div class="col-6 col-sm-4 col-md-2 col-xl mb-3 mb-xl-0">
									<button disabled="" type="button" class="btn w-100 btn-outline-secondary">Secondary</button>
								</div>
								<div class="col-6 col-sm-4 col-md-2 col-xl mb-3 mb-xl-0">
									<button disabled="" type="button" class="btn w-100 btn-outline-success">Success</button>
								</div>
								<div class="col-6 col-sm-4 col-md-2 col-xl mb-3 mb-xl-0">
									<button disabled="" type="button" class="btn w-100 btn-outline-warning">Warning</button>
								</div>
								<div class="col-6 col-sm-4 col-md-2 col-xl mb-3 mb-xl-0">
									<button disabled="" type="button" class="btn w-100 btn-outline-danger">Danger</button>
								</div>
								<div class="col-6 col-sm-4 col-md-2 col-xl mb-3 mb-xl-0">
									<button disabled="" type="button" class="btn w-100 btn-outline-info">Info</button>
								</div>
								<div class="col-6 col-sm-4 col-md-2 col-xl mb-3 mb-xl-0">
									<button disabled="" type="button" class="btn w-100 btn-outline-light">Light</button>
								</div>
								<div class="col-6 col-sm-4 col-md-2 col-xl mb-3 mb-xl-0">
									<button disabled="" type="button" class="btn w-100 btn-outline-dark">Dark</button>
								</div>
							</div>
						</div>
					</div>
					<!-- /Outline Buttons -->

					<!-- Progress Button -->
					<div class="card card-buttons">
						<div class="card-header">
							<h5 class="card-title mb-0">Progress Button</h5>
						</div>
						<div class="card-body">
							<button type="button" class="btn btn-primary"><span class="spinner-border spinner-border-sm me-2" role="status"></span>Primary</button>
							<button type="button" class="btn btn-secondary"><span class="spinner-border spinner-border-sm me-2" role="status"></span>Secondary</button>
							<button type="button" class="btn btn-success"><span class="spinner-border spinner-border-sm me-2" role="status"></span>Success</button>
							<button type="button" class="btn btn-danger"><span class="spinner-border spinner-border-sm me-2" role="status"></span>Danger</button>
							<button type="button" class="btn btn-warning"><span class="spinner-border spinner-border-sm me-2" role="status"></span>Warning</button>
							<button type="button" class="btn btn-info"><span class="spinner-border spinner-border-sm me-2" role="status"></span>Info</button>
							<button type="button" class="btn btn-dark"><span class="spinner-border spinner-border-sm me-2" role="status"></span>Dark</button>
						</div>
					</div>
					<!-- /Progress Button -->
				</div>			
			</div>
			<!-- /Page Wrapper -->
@endsection