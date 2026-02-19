<?php $page = 'form-fileupload'; ?>
@extends('layout.mainlayout')
@section('content')
			<!-- Page Wrapper -->
            <div class="page-wrapper">
                <div class="content container-fluid">
				
					<!-- Page Header -->
					<div class="page-header">
						<div class="content-page-header">
							<h5>File Upload</h5>
						</div>	
					</div>
					<!-- /Page Header -->
					
					<div class="row">
					
						<!-- Drag Card -->					
						<div class="col-md-12">	
						
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Single File Upload</h5>
								</div>
								<div class="card-body">
									<div class="custom-file-container" data-upload-id="myFirstImage">
										<label>Upload (Single File) <a href="javascript:void(0)" class="custom-file-container__image-clear" title="Clear Image">x</a></label>
										<label class="custom-file-container__custom-file" >
											<input type="file" class="custom-file-container__custom-file__custom-file-input" accept="image/*">
											<input type="hidden" name="MAX_FILE_SIZE" value="10485760">
											<span class="custom-file-container__custom-file__custom-file-control"></span>
										</label>
										<div class="custom-file-container__image-preview"></div>
									</div>
								</div>
							</div>
							
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Multiple File Upload</h5>
								</div>
								<div class="card-body">
									<div class="custom-file-container" data-upload-id="mySecondImage">
										<label>Upload (Allow Multiple) <a href="javascript:void(0)" class="custom-file-container__image-clear" title="Clear Image">x</a></label>
										<label class="custom-file-container__custom-file" >
											<input type="file" class="custom-file-container__custom-file__custom-file-input" multiple>
											<input type="hidden" name="MAX_FILE_SIZE" value="10485760">
											<span class="custom-file-container__custom-file__custom-file-control"></span>
										</label>
										<div class="custom-file-container__image-preview"></div>
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