<?php $page = 'stickynote'; ?>
@extends('layout.mainlayout')
@section('content')
			<!-- Page Wrapper -->
            <div class="page-wrapper">
                <div class="content container-fluid">
				
					<!-- Page Header -->
					<div class="page-header">
						<div class="content-page-header">
							<h5>Sticky Note</h5>
						</div>	
					</div>
					<!-- /Page Header -->
					
					<div class="row">
					
						<!-- Sticky -->
						<div class="col-md-12">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Sticky Note <a class="btn btn-primary float-sm-end m-l-10" id="add_new" href="javascript:;">Add New Note</a></h5>
								</div>
								<div class="card-body">
									 <div class="sticky-note" id="board"></div>
								</div>
							</div>
						</div>
						<!-- /Sticky -->
							
					</div>
				
				</div>			
			</div>
			<!-- /Page Wrapper -->
@endsection