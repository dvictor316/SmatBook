<?php $page = 'video'; ?>
@extends('layout.mainlayout')
@section('content')
			
            <div class="page-wrapper">
                <div class="content container-fluid">

					
					<div class="page-header">
						<div class="content-page-header">
							<h5>Video</h5>
						</div>	
					</div>
						

					<div class="row">
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-body card-buttons">
                                    <h4 class="header-title">Responsive embed video 21:9</h4>
                                    <p class="sub-header">Use class <code>.ratio-21x9</code></p>
                                    
                                    <div class="ratio ratio-21x9">
                                        <iframe src="https://www.youtube.com/embed/6bzTrChjEdc?autohide=0&showinfo=0&controls=0"></iframe>
                                    </div>
                                </div>
                            </div>
                        </div> 

                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-body card-buttons">
                                    <h4 class="header-title">Responsive embed video 16:9</h4>
                                    <p class="sub-header">Use class <code>.ratio-16x9</code></p>
                                    
                                    <div class="ratio ratio-16x9">
                                        <iframe src="https://www.youtube.com/embed/6bzTrChjEdc?ecver=1"></iframe>
                                    </div>
                                </div>
                            </div>
                        </div> 
                    </div>
                    

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-body card-buttons">
                                    <h4 class="header-title">Responsive embed video 4:3</h4>
                                    <p class="sub-header">Use class <code>.ratio-4x3</code></p>
                                    
                                    <div class="ratio ratio-4x3">
                                        <iframe src="https://www.youtube.com/embed/6bzTrChjEdc?ecver=1"></iframe>
                                    </div>
                                </div>
                            </div>
                        </div> 

                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-body card-buttons">
                                    <h4 class="header-title">Responsive embed video 1:1</h4>
                                    <p class="sub-header">Use class <code>.ratio-1x1</code></p>
                                    
                                    <div class="ratio ratio-1x1">
                                        <iframe src="https://www.youtube.com/embed/6bzTrChjEdc?ecver=1"></iframe>
                                    </div>
                                </div>
                            </div>
                        </div> 
                    </div>
                    	

				</div>			
			</div>
			
@endsection