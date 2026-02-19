<?php $page = 'buttongroup'; ?>
@extends('layout.mainlayout')
@section('content')
			<!-- Page Wrapper -->
            <div class="page-wrapper">
                <div class="content container-fluid">
				
					<!-- Page Header -->
                    <div class="page-header">
						<div class="content-page-header">
							<h5>Button Group</h5>
						</div>	
					</div>	
                    <!-- /Page Header -->
											
					<!-- Button Group -->
					<div class="row">
						<div class="col-md-6">
							<div class="card bg-white">
								<div class="card-header">
									<h5 class="card-title">Horizontal</h5>
								</div>
								<div class="card-body">
									<div class="btn-group">
	                                    <button type="button" class="btn btn-primary">Left</button>
	                                    <button type="button" class="btn btn-primary">Middle</button>
	                                    <button type="button" class="btn btn-primary">Right</button>
	                                </div>
								</div>
							</div>
						</div>
						<div class="col-md-6">
							<div class="card bg-white">
								<div class="card-header">
									<h5 class="card-title"> Input Group </h5>
								</div>
								<div class="card-body">
									<div class="btn-group">
	                                    <button type="button" class="btn btn-primary">1</button>
	                                    <button type="button" class="btn btn-primary">2</button>
	                                    <button type="button" class="btn btn-primary">3</button>
	                                    <button type="button" class="btn btn-primary">4</button>
	                                </div>
								</div>
							</div>
						</div>
						<div class="col-md-6">
							<div class="card bg-white">
								<div class="card-header">
									<h5 class="card-title"> Vertical </h5>
								</div>
								<div class="card-body">
									<div class="btn-group">
	                                    <div class="btn-group-vertical">
	                                        <button type="button" class="btn btn-primary">Button</button>
	                                        <div class="btn-group" role="group">
		                                        <button id="btngroupverticaldrop1" type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
		                                            Dropdown
		                                        </button>
		                                        <div class="dropdown-menu" aria-labelledby="btngroupverticaldrop1">
		                                            <a class="dropdown-item" href="#">Dropdown link</a>
		                                            <a class="dropdown-item" href="#">Dropdown link</a>
		                                        </div>
	                                        </div>
	                                        <button type="button" class="btn btn-primary">Button</button>
	                                        <button type="button" class="btn btn-primary">Button</button>
	                                        <div class="btn-group" role="group">
		                                        <button id="btngroupverticaldrop2" type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
		                                            Dropdown
		                                        </button>
		                                        <div class="dropdown-menu" aria-labelledby="btngroupverticaldrop2">
		                                            <a class="dropdown-item" href="#">Dropdown link</a>
		                                            <a class="dropdown-item" href="#">Dropdown link</a>
		                                        </div>
	                                        </div>
	                                    </div>
	                                </div>
								</div>
							</div>
						</div>
					</div>
					<!-- /Button group-->
				</div>			
			</div>
			<!-- /Page Wrapper -->
@endsection