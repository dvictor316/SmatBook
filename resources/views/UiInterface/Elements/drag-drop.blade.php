<?php $page = 'drag-drop'; ?>
@extends('layout.mainlayout')
@section('content')
			<!-- Page Wrapper -->
            <div class="page-wrapper">
                <div class="content container-fluid">
				
					<!-- Page Header -->
					<div class="page-header">
						<div class="content-page-header">
							<h5>Drag & Drop</h5>
						</div>	
					</div>
					<!-- /Page Header -->	
					
					<div class="row" id="card-drag-area">
					
						<!-- Drag Card -->					
						<div class="col-md-4 draggable">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Draggable Card 1</h5>
								</div>
								<div class="card-body">
									<p>Jelly beans sugar plum cheesecake cookie oat cake soufflé.Tootsie roll bonbon liquorice tiramisu pie
									powder.Donut sweet roll marzipan pastry cookie cake tootsie roll oat cake cookie.</p>
								</div>
							</div>
						</div>
						<!-- /Drag Card -->
						
						<!-- Drag Card -->					
						<div class="col-md-4 draggable">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Draggable Card 2</h5>
								</div>
								<div class="card-body">
									<p>Jelly beans sugar plum cheesecake cookie oat cake soufflé.Tootsie roll bonbon liquorice tiramisu pie
									powder.Donut sweet roll marzipan pastry cookie cake tootsie roll oat cake cookie.</p>
								</div>
							</div>
						</div>
						<!-- /Drag Card -->
						
						<!-- Drag Card -->					
						<div class="col-md-4 draggable">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Draggable Card 3</h5>
								</div>
								<div class="card-body">
									<p>Jelly beans sugar plum cheesecake cookie oat cake soufflé.Tootsie roll bonbon liquorice tiramisu pie
									powder.Donut sweet roll marzipan pastry cookie cake tootsie roll oat cake cookie.</p>
								</div>
							</div>
						</div>
						<!-- /Drag Card -->
					</div>	
					
					<div class="row">						
						<div class="col-md-12">
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Basic List Group Sortable</h5>
								</div>
								<div class="card-body">
									<!-- Drag Card -->					
									<ul class="list-group" id="basic-list-group">
										<li class="list-group-item draggable">
											<div class="chat-block">
												<div class="media-body">
													<h5 class="mt-0">Darren Elder</h5>
													<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod</p>
												</div>
											</div>
										</li>
										<li class="list-group-item draggable">
											<div class="chat-block">
												<div class="media-body">
													<h5 class="mt-0">Linda Ellis</h5>
													<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod</p>
												</div>
											</div>
										</li>
										<li class="list-group-item draggable">
											<div class="chat-block">
												<div class="media-body">
													<h5 class="mt-0">Toney Ritch</h5>
													<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod</p>
												</div>
											</div>
										</li>
										<li class="list-group-item draggable">
											<div class="chat-block">
												<div class="media-body">
													<h5 class="mt-0">Flora Jag</h5>
													<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod</p>
												</div>
											</div>
										</li>
										<li class="list-group-item draggable">
											<div class="chat-block">
												<div class="media-body">
													<h5 class="mt-0">Linda Marris</h5>
													<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod</p>
												</div>
											</div>
										</li>
									  </ul>
									<!-- /Drag Card -->
								</div>	
							</div>	
						</div>	
					</div>	
					
					<div class="row">
					
						<!-- Drag Card -->					
						<div class="col-md-12">	
							<div class="card" id="dd-handle">
								<div class="card-header">
									<h5 class="card-title">Drag Handler</h5>
								</div>
								<div class="card-body">
									<div class="row">
										<div class="col-sm-6">
											<h4 class="my-1">List One</h4>
											<ul class="list-group" id="handle-list-1">
												<li class="list-group-item"><span class="handle">+</span> Cras justo odio</li>
												<li class="list-group-item"><span class="handle">+</span> Dapibus ac facilisis in</li>
												<li class="list-group-item"><span class="handle">+</span> Morbi leo risus</li>
												<li class="list-group-item"><span class="handle">+</span> Porta ac consectetur ac</li>
												<li class="list-group-item"><span class="handle">+</span> Vestibulum at eros</li>
											</ul>
										</div>
										<div class="col-sm-6">
											<h4 class="my-1">List Two</h4>
											<ul class="list-group" id="handle-list-2">
												<li class="list-group-item"><span class="handle">+</span> Cras justo odio</li>
												<li class="list-group-item"><span class="handle">+</span> Dapibus ac facilisis in</li>
												<li class="list-group-item"><span class="handle">+</span> Morbi leo risus</li>
												<li class="list-group-item"><span class="handle">+</span> Porta ac consectetur ac</li>
												<li class="list-group-item"><span class="handle">+</span> Vestibulum at eros</li>
											</ul>
										</div>
									</div>
								</div>
							</div>
						</div>
						<!-- /Drag Card -->
					</div>	
				
				</div>			
			</div>
			<!-- /Page Wrapper -->
@endsection