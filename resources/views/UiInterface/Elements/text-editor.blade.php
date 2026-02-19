<?php $page = 'text-editor'; ?>
@extends('layout.mainlayout')
@section('content')
			<!-- Page Wrapper -->
            <div class="page-wrapper">
                <div class="content container-fluid">
				
					<!-- Page Header -->
					<div class="page-header">
						<div class="content-page-header">
							<h5>Text Editor</h5>
						</div>	
					</div>
					<!-- /Page Header -->
					
					<div class="row">
					
						<!-- Editor -->
						<div class="col-md-12">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Editor</h5>
								</div>
								<div class="card-body">
									<div id="summernote"></div>
								</div>
							</div>
						</div>
						<!-- /Editor -->
							
					</div>				
				</div>			
			</div>
			<!-- /Page Wrapper -->
@endsection