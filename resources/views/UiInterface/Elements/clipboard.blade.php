<?php $page = 'clipboard'; ?>
@extends('layout.mainlayout')
@section('content')
			<!-- Page Wrapper -->
            <div class="page-wrapper">
                <div class="content container-fluid">
				
					<!-- Page Header -->
					<div class="page-header">
						<div class="content-page-header">
							<h5>Clipboard</h5>
						</div>	
					</div>
					<!-- /Page Header -->
					
					<div class="row">
					
						<!-- Drag Card -->					
						<div class="col-md-12">	
						
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Copy from input</h5>
								</div>
								<div class="card-body">
									<div class="clipboard">
										<form class="form-horizontal">
											<input type="text" class="form-control mb-4" id="input-copy" value="http://www.admin-dashboard.com">
											<a class="mb-1 btn clip-btn btn-primary" href="javascript:;" data-clipboard-action="copy" data-clipboard-target="#input-copy"><i class="far fa-copy"></i> Copy from Input</a>
											<a class="mb-1 btn clip-btn btn-dark" href="javascript:;" data-clipboard-action="cut" data-clipboard-target="#input-copy"><i class="fas fa-cut"></i> Cut from Input</a>
										</form>
									</div>
								</div>
							</div>
							
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Copy from Text Area</h5>
								</div>
								<div class="card-body">
									<div class="clipboard">
										<form class="form-horizontal">
											<textarea class="form-control mb-4" rows="3" id="textarea-copy">Lorem ipsum dolor sit amet, consectetur adipiscing elit...</textarea>
											<a class="mb-1 btn clip-btn btn-primary" href="javascript:;" data-clipboard-action="copy" data-clipboard-target="#textarea-copy"><i class="far fa-copy"></i> Copy from Input</a>
											<a class="mb-1 btn clip-btn btn-dark" href="javascript:;" data-clipboard-action="cut" data-clipboard-target="#textarea-copy"><i class="fas fa-cut"></i> Cut from Input</a>
										</form>
									</div>
								</div>
							</div>
							
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Copy Text from Paragraph</h5>
								</div>
								<div class="card-body">
									<div class="clipboard copy-txt">
										<p class="otp-pass">Here is your OTP <span id="paragraph-copy1">22991</span>.</p>
										<p class="mb-4">Please do not share it to anyone</p>
										<a class="mb-1 btn clip-btn btn-primary" href="javascript:;" data-clipboard-action="copy" data-clipboard-target="#paragraph-copy1"><i class="far fa-copy"></i> Copy from Input</a>
									</div>
								</div>
							</div>
							
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Copy Hidden Text (Advanced)</h5>
								</div>
								<div class="card-body">
									<div class="clipboard copy-txt">
										<p class="mb-4">Link -> <span id="advanced-paragraph">http://www.example.com/example</span></p>
										<a class="mb-1 btn clip-btn btn-primary" href="javascript:;" data-clipboard-action="copy" data-clipboard-target="#advanced-paragraph"><i class="far fa-copy"></i> Copy Link</a>
										<a class="mb-1 btn clip-btn btn-warning" href="javascript:;" data-clipboard-action="copy" data-clipboard-text="2291"><i class="far fa-copy"></i> Copy Hidden Code</a>
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