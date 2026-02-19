<?php $page = 'form-select2'; ?>
@extends('layout.mainlayout')
@section('content')
			<!-- Page Wrapper -->
            <div class="page-wrapper">
                <div class="content container-fluid">
				
					<!-- Page Header -->
					<div class="page-header">
						<div class="content-page-header">
							<h5>Form Select2</h5>
						</div>	
					</div>
					<!-- /Page Header -->
					
					<div class="row">
						<div class="col-md-6">
						
							<!-- Basic -->
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Basic</h5>
								</div>
								<div class="card-body card-buttons">
									<div class="row">
										<div class="col-md-12">
											<p>Use select2() function on select element to convert it to Select 2.</p>
											<select class="js-example-basic-single select2">
												<option selected="selected">orange</option>
												<option>white</option>
												<option>purple</option>
											</select>
										</div>
									</div>
								</div>
							</div>
							<!-- /Basic -->
							
							<!-- Nested -->
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Nested</h5>
								</div>
								<div class="card-body card-buttons">
									<div class="row">
										<div class="col-md-12">
											<p>Add options inside the optgroups to for group options.</p>
											<select class="form-control nested">
												<optgroup label="Group1">
													<option selected="selected">orange</option>
													<option>white</option>
													<option>purple</option>
												</optgroup>
												<optgroup label="Group2">
													<option>purple</option>
													<option>orange</option>
													<option>white</option>
												</optgroup>
												<optgroup label="Group3">
													<option>white</option>
													<option>purple</option>
													<option>orange</option>
												</optgroup>
											</select>
										</div>
									</div>
								</div>
							</div>
							<!-- /Nested -->
							
							<!-- Placeholder -->							
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Placeholder</h5>
								</div>
								<div class="card-body card-buttons">
									<div class="row">
										<div class="col-md-12">
											<p>Apply Placeholder by setting option placeholder option.</p>
											<select class="placeholder js-states form-control">
												<option>Choose...</option>
												<option value="one">First</option>
												<option value="two">Second</option>
												<option value="three">Third</option>
												<option value="four">Fourth</option>
												<option value="five">Fifth</option>
											</select>
										</div>
									</div>
								</div>
							</div>
							<!-- /Placeholder -->
							
							<!-- Tagging with multi-value -->
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Tagging with multi-value select boxes</h5>
								</div>
								<div class="card-body card-buttons">
									<div class="row">
										<div class="col-md-12">
											<p>Set tags: true to convert select 2 in Tag mode.</p>
											<select class="form-control tagging" multiple="multiple">
												<option>orange</option>
												<option>white</option>
												<option>purple</option>
											</select>
										</div>
									</div>
								</div>
							</div>
							<!-- /Tagging with multi-value -->
							
						</div>
					
						<div class="col-md-6">
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Small Select2</h5>
								</div>
								<div class="card-body card-buttons">
									<div class="row">
										<div class="col-md-12">
											<p>Use data('select2') function to get container of select2.</p>	
											<select class="form-control form-small select">
												<option selected="selected">orange</option>
												<option>white</option>
												<option>purple</option>
											</select>
										</div>
									</div>
								</div>
							</div>
							
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Disabling options</h5>
								</div>
								<div class="card-body card-buttons">
									<div class="row">
										<div class="col-md-12">
											<p>Disable Select using disabled attribute.</p>
											<select class="form-control disabled-results">
												<option value="one">First</option>
												<option value="two" disabled="disabled">Second</option>
												<option value="three">Third</option>
											</select>
										</div>
									</div>
								</div>
							</div>
							
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Limiting the number of Tagging</h5>
								</div>
								<div class="card-body card-buttons">
									<div class="row">
										<div class="col-md-12">
											<p>Set maximumSelectionLength: 2 with tags: true to limit selectin in Tag mode.</p>
											<select class="form-control tagging" multiple="multiple">
												<option>orange</option>
												<option>white</option>
												<option>purple</option>
											</select>
										</div>
									</div>
								</div>
							</div>
							
						</div>
					</div>
				
				</div>			
			</div>
			<!-- /Page Wrapper -->
@endsection