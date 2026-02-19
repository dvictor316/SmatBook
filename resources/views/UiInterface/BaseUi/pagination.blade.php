<?php $page = 'pagination'; ?>
@extends('layout.mainlayout')
@section('content')
			<!-- Page Wrapper -->
            <div class="page-wrapper">
                <div class="content container-fluid">
					<!-- Page Header -->
					<div class="page-header">
						<div class="content-page-header">
							<h5>Pagination</h5>
						</div>	
					</div>
					<!-- /Page Header -->	
											
					<!-- Pagination -->
						<div class="card bg-white">
							<div class="card-body">
								<div>
									<ul class="pagination mb-4">
										<li class="page-item disabled">
											<a class="page-link" href="#" tabindex="-1">Previous</a>
										</li>
										<li class="page-item"><a class="page-link" href="#">1</a></li>
										<li class="page-item active">
											<a class="page-link" href="#">2 <span class="visually-hidden">(current)</span></a>
										</li>
										<li class="page-item"><a class="page-link" href="#">3</a></li>
										<li class="page-item">
											<a class="page-link" href="#">Next</a>
										</li>
									</ul>
								</div>
								<div>
									<ul class="pagination mb-4">
										<li class="page-item">
											<a class="page-link" href="#" aria-label="Previous">
												<span aria-hidden="true">&laquo;</span>
												<span class="visually-hidden">Previous</span>
											</a>
										</li>
										<li class="page-item"><a class="page-link" href="#">1</a></li>
										<li class="page-item"><a class="page-link" href="#">2</a></li>
										<li class="page-item"><a class="page-link" href="#">3</a></li>
										<li class="page-item">
											<a class="page-link" href="#" aria-label="Next">
												<span aria-hidden="true">&raquo;</span>
											<span class="visually-hidden">Next</span>
											</a>
										</li>
									</ul>
								</div>
								<div>
									<ul class="pagination pagination-lg mb-4">
										<li class="page-item disabled">
											<a class="page-link" href="#" tabindex="-1">Previous</a>
										</li>
										<li class="page-item"><a class="page-link" href="#">1</a></li>
										<li class="page-item active">
											<a class="page-link" href="#">2 <span class="visually-hidden">(current)</span></a>
										</li>
										<li class="page-item"><a class="page-link" href="#">3</a></li>
										<li class="page-item">
											<a class="page-link" href="#">Next</a>
										</li>
									</ul>
								</div>
								
								<div>
									<ul class="pagination pagination-sm mb-0">
										<li class="page-item disabled">
											<a class="page-link" href="#" tabindex="-1">Previous</a>
										</li>
										<li class="page-item"><a class="page-link" href="#">1</a></li>
										<li class="page-item active">
											<a class="page-link" href="#">2 <span class="visually-hidden">(current)</span></a>
										</li>
										<li class="page-item"><a class="page-link" href="#">3</a></li>
										<li class="page-item">
											<a class="page-link" href="#">Next</a>
										</li>
									</ul>
								</div>
							</div>
						</div>
					<!-- /Pagination -->
				
				</div>			
			</div>
			<!-- /Page Wrapper -->
@endsection