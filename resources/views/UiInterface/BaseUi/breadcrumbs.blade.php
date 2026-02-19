<?php $page = 'breadcrumbs'; ?>
@extends('layout.mainlayout')
@section('content')
			<!-- Page Wrapper -->
            <div class="page-wrapper">
                <div class="content container-fluid">
				
					<!-- Page Header -->
                    <div class="page-header">
						<div class="content-page-header">
							<h5>Breadcrumbs</h5>
						</div>	
					</div>	
                    <!-- /Page Header -->
											
					<!-- Breadcrumbs -->
					<div class="col-md-12">
						<div class="card card-buttons">
							<div class="card-body">
								<h5 class="card-title">Basic</h5>
								<p>Use an ordered or unordered list with linked list items to create a minimally styled breadcrumb. Use our utilities to add additional styles as desired.</p>
								<div class="rounded border p-4">
									<ol class="breadcrumb text-muted mb-0">
										<li class="breadcrumb-item">
											<a href="#">Home</a>
										</li>
										<li class="breadcrumb-item">
											<a href="#">Library</a>
										</li>
										<li class="breadcrumb-item text-muted">Active</li>
									</ol>
								</div>
							</div>
						</div>
					</div>
					<div class="col-md-12">
						<div class="card card-buttons">
							<div class="card-body">
								<h5 class="card-title">Line Separator</h5>
								<p>Add <code>.breadcrumb-lineto</code> enable line separator style for a breadcrumb.</p>
								<div class="rounded border p-4">
									<ol class="breadcrumb breadcrumb-line text-muted mb-0">
										<li class="breadcrumb-item ">
											<a href="#">Home</a>
										</li>
										<li class="breadcrumb-item ">
											<a href="#">Library</a>
										</li>
										<li class="breadcrumb-item text-muted">Active</li>
									</ol>
								</div>
							</div>
						</div>
					</div>
					<div class="col-md-12">
						<div class="card card-buttons">
							<div class="card-body">
								<h5 class="card-title">Dot Separator</h5>
								<p>Add <code>.breadcrumb-dot</code> enable line separator style for a breadcrumb.</p>
								<div class="rounded border p-4">
									<ol class="breadcrumb breadcrumb-dot text-muted mb-0">
										<li class="breadcrumb-item">
											<a href="#">Home</a>
										</li>
										<li class="breadcrumb-item">
											<a href="#">Library</a>
										</li>
										<li class="breadcrumb-item text-muted">Active</li>
									</ol>
								</div>
							</div>
						</div>
					</div>
					<div class="col-md-12">
						<div class="card card-buttons">
							<div class="card-body">
								<h5 class="card-title">Without Separator</h5>
								<p>Add <code>.breadcrumb-separatorless</code> to remove the separator.</p>
								<div class="rounded border p-4">
									<ol class="breadcrumb breadcrumb-separatorless text-muted mb-0">
										<li class="breadcrumb-item">
											<a href="#">Home</a>
										</li>
										<li class="breadcrumb-item">
											<a href="#">Library</a>
										</li>
										<li class="breadcrumb-item text-muted">Active</li>
									</ol>
								</div>
							</div>
						</div>
					</div>
					<div class="col-md-12">
						<div class="card card-buttons">
							<div class="card-body">
								<h5 class="card-title">Colored Breadcrumb</h5>
								<p>use class <code> .breadcrumb-colored .bg-primary</code></p>
			                    <ol class="breadcrumb breadcrumb-colored bg-primary">
			                      <li class="breadcrumb-item"><a href="javascript:void(0)" data-bs-original-title="" title="">Home</a></li>
			                      <li class="breadcrumb-item active">Library</li>
			                    </ol>
			                    <ol class="breadcrumb breadcrumb-colored bg-secondary">
			                      <li class="breadcrumb-item"><a href="javascript:void(0)" data-bs-original-title="" title="">Home</a></li>
			                      <li class="breadcrumb-item active">Library</li>
			                    </ol>
			                    <ol class="breadcrumb breadcrumb-colored bg-success">
			                      <li class="breadcrumb-item"><a href="javascript:void(0)" data-bs-original-title="" title="">Home</a></li>
			                      <li class="breadcrumb-item active">Library</li>
			                    </ol>
			                    <ol class="breadcrumb breadcrumb-colored bg-info">
			                      <li class="breadcrumb-item"><a href="javascript:void(0)" data-bs-original-title="" title="">Home</a></li>
			                      <li class="breadcrumb-item active">Library</li>
			                    </ol>
			                    <ol class="breadcrumb breadcrumb-colored bg-warning">
			                      <li class="breadcrumb-item"><a href="javascript:void(0)" data-bs-original-title="" title="">Home</a></li>
			                      <li class="breadcrumb-item active">Library</li>
			                    </ol>
			                    <ol class="breadcrumb breadcrumb-colored bg-danger">
			                      <li class="breadcrumb-item"><a href="javascript:void(0)" data-bs-original-title="" title="">Home</a></li>
			                      <li class="breadcrumb-item active">Library</li>
			                    </ol>
			                    <ol class="breadcrumb breadcrumb-colored bg-dark mb-0">
			                      <li class="breadcrumb-item"><a href="javascript:void(0)" data-bs-original-title="" title="">Home</a></li>
			                      <li class="breadcrumb-item active">Library</li>
			                    </ol>
							</div>
						</div>
					</div>
					<!-- /Breadcrumbs -->
									
				</div>			
			</div>
			<!-- /Page Wrapper -->
@endsection