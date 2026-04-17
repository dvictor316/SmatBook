<?php $page = 'counter'; ?>
@extends('layout.mainlayout')
@section('content')
			
            <div class="page-wrapper">
                <div class="content container-fluid">

					
					<div class="page-header">
						<div class="content-page-header">
							<h5>Counter</h5>
						</div>	
					</div>
					

					<div class="row">

						
						<div class="col-md-4">	
							<div class="card">
								<div class="card-body card-buttons">
									<h5 class="card-title">Clients</h5>
									<h6 class="counter">3,000</h6>
								</div>
							</div>
						</div>
						

						
						<div class="col-md-4">	
							<div class="card">
								<div class="card-body card-buttons">
									<h5 class="card-title">Total Sales</h5>
									<h6 class="counter">10,000</h6>
								</div>
							</div>
						</div>
						

						
						<div class="col-md-4">	
							<div class="card">
								<div class="card-body card-buttons">
									<h5 class="card-title">Total Projects</h5>
									<h6 class="counter">15,000</h6>
								</div>
							</div>
						</div>
						

						
						<div class="col-md-4">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Count Down</h5>
								</div>
								<div class="card-body card-buttons">
									<h6 class="card-text">Time Count from 3</h6>
									<span id="timer-countdown"></span>
								</div>
							</div>
						</div>
						

						
						<div class="col-md-4">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Count Up</h5>
								</div>
								<div class="card-body card-buttons">
									<h6 class="card-text">Time Counting From 0</h6>
									<span id="timer-countup"></span>
								</div>
							</div>
						</div>
						

						
						<div class="col-md-4">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Count Inbetween</h5>
								</div>
								<div class="card-body card-buttons">
									<h6 class="card-text">Time counting from 30 to  20</h6>
									<span id="timer-countinbetween"></span>
								</div>
							</div>
						</div>
						

						
						<div class="col-md-4">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Count Callback</h5>
								</div>
								<div class="card-body card-buttons">
									<h6 class="card-text">Count from 10 to 0 and calls timer end callback</h6>
									<span id="timer-countercallback"></span>
								</div>
							</div>
						</div>
						

						
						<div class="col-md-4">	
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">Custom Output</h5>
								</div>
								<div class="card-body card-buttons">
									<h6 class="card-text">Changed output pattern</h6>
									<span id="timer-outputpattern"></span>
								</div>
							</div>
						</div>
						

					</div>

				</div>			
			</div>
			
@endsection