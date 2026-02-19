<?php $page = 'tab'; ?>
@extends('layout.mainlayout')
@section('content')
			<!-- Page Wrapper -->
            <div class="page-wrapper">
                <div class="content container-fluid">
				
					<!-- Page Header -->
					<div class="page-header">
						<div class="content-page-header">
							<h5>Tabs</h5>
						</div>	
					</div>
					<!-- /Page Header -->
											
					<!-- Tabs -->
						<div class="row">
							<div class="col-md-6">
								<div class="card bg-white">
									<div class="card-header">
										<h5 class="card-title">Basic tabs</h5>
									</div>
									<div class="card-body">
										<ul class="nav nav-tabs">
											<li class="nav-item"><a class="nav-link active" href="#basictab1" data-bs-toggle="tab">Home</a></li>
											<li class="nav-item"><a class="nav-link" href="#basictab2" data-bs-toggle="tab">Profile</a></li>
											<li class="nav-item"><a class="nav-link" href="#basictab3" data-bs-toggle="tab">Messages</a></li>
										</ul>
										<div class="tab-content">
											<div class="tab-pane show active" id="basictab1">
												Tab content 1
											</div>
											<div class="tab-pane" id="basictab2">
												Tab content 2
											</div>
											<div class="tab-pane" id="basictab3">
												Tab content 3
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="col-md-6">
								<div class="card bg-white">
									<div class="card-header">
										<h5 class="card-title">Basic justified tabs</h5>
									</div>
									<div class="card-body">
										<ul class="nav nav-tabs nav-justified">
											<li class="nav-item"><a class="nav-link active" href="#basic-justified-tab1" data-bs-toggle="tab">Home</a></li>
											<li class="nav-item"><a class="nav-link" href="#basic-justified-tab2" data-bs-toggle="tab">Profile</a></li>
											<li class="nav-item dropdown">
												<a href="#" class="dropdown-toggle nav-link" data-bs-toggle="dropdown">Dropdown</a>
												<div class="dropdown-menu dropdown-menu-right">
													<a class="dropdown-item" href="#basic-justified-tab3" data-bs-toggle="tab">Dropdown 1</a>
													<a class="dropdown-item" href="#basic-justified-tab4" data-bs-toggle="tab">Dropdown 2</a>
												</div>
											</li>
										</ul>
										<div class="tab-content">
											<div class="tab-pane show active" id="basic-justified-tab1">
												Tab content 1
											</div>
											<div class="tab-pane" id="basic-justified-tab2">
												Tab content 2
											</div>
											<div class="tab-pane" id="basic-justified-tab3">
												Tab content 3
											</div>
											<div class="tab-pane" id="basic-justified-tab4">
												Tab content 4
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>

						<div class="row">
							<div class="col-md-6">
								<div class="card bg-white">
									<div class="card-header">
										<h5 class="card-title">Top line tabs</h5>
									</div>
									<div class="card-body">
										<ul class="nav nav-tabs nav-tabs-top">
											<li class="nav-item"><a class="nav-link active" href="#top-tab1" data-bs-toggle="tab">Home</a></li>
											<li class="nav-item"><a class="nav-link" href="#top-tab2" data-bs-toggle="tab">Profile</a></li>
											<li class="nav-item"><a class="nav-link" href="#top-tab3" data-bs-toggle="tab">Messages</a></li>
										</ul>
										<div class="tab-content">
											<div class="tab-pane show active" id="top-tab1">
												Tab content 1
											</div>
											<div class="tab-pane" id="top-tab2">
												Tab content 2
											</div>
											<div class="tab-pane" id="top-tab3">
												Tab content 3
											</div>
										</div>
									</div>
								</div>
							</div>

							<div class="col-md-6">
								<div class="card bg-white">
									<div class="card-header">
										<h5 class="card-title">Top line justified</h5>
									</div>
									<div class="card-body">
										<ul class="nav nav-tabs nav-tabs-top nav-justified">
											<li class="nav-item"><a class="nav-link active" href="#top-justified-tab1" data-bs-toggle="tab">Home</a></li>
											<li class="nav-item"><a class="nav-link" href="#top-justified-tab2" data-bs-toggle="tab">Profile</a></li>
											<li class="nav-item"><a class="nav-link" href="#top-justified-tab3" data-bs-toggle="tab">Messages</a></li>
										</ul>
										<div class="tab-content">
											<div class="tab-pane show active" id="top-justified-tab1">
												Tab content 1
											</div>
											<div class="tab-pane" id="top-justified-tab2">
												Tab content 2
											</div>

											<div class="tab-pane" id="top-justified-tab3">
												Tab content 3
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>

						<div class="row">
							<div class="col-md-6">
								<div class="card bg-white">
									<div class="card-header">
										<h5 class="card-title">Bottom line tabs</h5>
									</div>
									<div class="card-body">
										<ul class="nav nav-tabs nav-tabs-bottom">
											<li class="nav-item"><a class="nav-link active" href="#bottom-tab1" data-bs-toggle="tab">Home</a></li>
											<li class="nav-item"><a class="nav-link" href="#bottom-tab2" data-bs-toggle="tab">Profile</a></li>
											<li class="nav-item"><a class="nav-link" href="#bottom-tab3" data-bs-toggle="tab">Messages</a></li>
										</ul>

										<div class="tab-content">
											<div class="tab-pane show active" id="bottom-tab1">
												Tab content 1
											</div>
											<div class="tab-pane" id="bottom-tab2">
												Tab content 2
											</div>
											<div class="tab-pane" id="bottom-tab3">
												Tab content 3
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="col-md-6">
								<div class="card bg-white">
									<div class="card-header">
										<h5 class="card-title">Bottom line justified</h5>
									</div>
									<div class="card-body">
										<ul class="nav nav-tabs nav-tabs-bottom nav-justified">
											<li class="nav-item"><a class="nav-link active" href="#bottom-justified-tab1" data-bs-toggle="tab">Home</a></li>
											<li class="nav-item"><a class="nav-link" href="#bottom-justified-tab2" data-bs-toggle="tab">Profile</a></li>
											<li class="nav-item"><a class="nav-link" href="#bottom-justified-tab3" data-bs-toggle="tab">Messages</a></li>
										</ul>
										<div class="tab-content">
											<div class="tab-pane show active" id="bottom-justified-tab1">
												Tab content 1
											</div>
											<div class="tab-pane" id="bottom-justified-tab2">
												Tab content 2
											</div>
											<div class="tab-pane" id="bottom-justified-tab3">
												Tab content 3
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-6">
								<div class="card bg-white">
									<div class="card-header">
										<h5 class="card-title">Solid tabs</h5>
									</div>
									<div class="card-body">
										<ul class="nav nav-tabs nav-tabs-solid">
											<li class="nav-item"><a class="nav-link active" href="#solid-tab1" data-bs-toggle="tab">Home</a></li>
											<li class="nav-item"><a class="nav-link" href="#solid-tab2" data-bs-toggle="tab">Profile</a></li>
											<li class="nav-item"><a class="nav-link" href="#solid-tab3" data-bs-toggle="tab">Messages</a></li>
										</ul>
										<div class="tab-content">
											<div class="tab-pane show active" id="solid-tab1">
												Tab content 1
											</div>
											<div class="tab-pane" id="solid-tab2">
												Tab content 2
											</div>
											<div class="tab-pane" id="solid-tab3">
												Tab content 3
											</div>
										</div>
									</div>
								</div>
							</div>

							<div class="col-md-6">
								<div class="card bg-white">
									<div class="card-header">
										<h5 class="card-title">Solid justified</h5>
									</div>
									<div class="card-body">
										<ul class="nav nav-tabs nav-tabs-solid nav-justified">
											<li class="nav-item"><a class="nav-link active" href="#solid-justified-tab1" data-bs-toggle="tab">Home</a></li>
											<li class="nav-item"><a class="nav-link" href="#solid-justified-tab2" data-bs-toggle="tab">Profile</a></li>
											<li class="nav-item"><a class="nav-link" href="#solid-justified-tab3" data-bs-toggle="tab">Messages</a></li>
										</ul>
										<div class="tab-content">
											<div class="tab-pane show active" id="solid-justified-tab1">
												Tab content 1
											</div>
											<div class="tab-pane" id="solid-justified-tab2">
												Tab content 2
											</div>
											<div class="tab-pane" id="solid-justified-tab3">
												Tab content 3
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-6">
								<div class="card bg-white">
									<div class="card-header">
										<h5 class="card-title">Solid Rounded</h5>
									</div>
									<div class="card-body">
										<ul class="nav nav-tabs nav-tabs-solid nav-tabs-rounded">
											<li class="nav-item"><a class="nav-link active" href="#solid-rounded-tab1" data-bs-toggle="tab">Home</a></li>
											<li class="nav-item"><a class="nav-link" href="#solid-rounded-tab2" data-bs-toggle="tab">Profile</a></li>
											<li class="nav-item"><a class="nav-link" href="#solid-rounded-tab3" data-bs-toggle="tab">Messages</a></li>
										</ul>
										<div class="tab-content">
											<div class="tab-pane show active" id="solid-rounded-tab1">
												Tab content 1
											</div>
											<div class="tab-pane" id="solid-rounded-tab2">
												Tab content 2
											</div>
											<div class="tab-pane" id="solid-rounded-tab3">
												Tab content 3
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="col-md-6">
								<div class="card bg-white">
									<div class="card-header">
										<h5 class="card-title">Rounded justified</h5>
									</div>
									<div class="card-body">
										<ul class="nav nav-tabs nav-tabs-solid nav-tabs-rounded nav-justified">
											<li class="nav-item"><a class="nav-link active" href="#solid-rounded-justified-tab1" data-bs-toggle="tab">Home</a></li>
											<li class="nav-item"><a class="nav-link" href="#solid-rounded-justified-tab2" data-bs-toggle="tab">Profile</a></li>
											<li class="nav-item"><a class="nav-link" href="#solid-rounded-justified-tab3" data-bs-toggle="tab">Messages</a></li>
										</ul>
										<div class="tab-content">
											<div class="tab-pane show active" id="solid-rounded-justified-tab1">
												Tab content 1
											</div>
											<div class="tab-pane" id="solid-rounded-justified-tab2">
												Tab content 2
											</div>
											<div class="tab-pane" id="solid-rounded-justified-tab3">
												Tab content 3
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					<!-- /Tabs -->
										
				</div>			
			</div>
			<!-- /Page Wrapper -->
@endsection