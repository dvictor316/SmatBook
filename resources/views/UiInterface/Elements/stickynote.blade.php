<?php $page = 'stickynote'; ?>
@extends('layout.mainlayout')
@section('content')
			
            <div class="page-wrapper">
                <div class="content container-fluid">

					
					<div class="page-header">
						<div class="content-page-header">
							<h5>Sticky Note</h5>
						</div>	
					</div>
					

					<div class="row">

						
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
						

					</div>

				</div>			
			</div>
			
@endsection