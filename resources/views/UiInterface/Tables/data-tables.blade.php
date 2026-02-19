<?php $page = 'data-tables'; ?>
@extends('layout.mainlayout')
@section('content')
			<!-- Page Wrapper -->
            <div class="page-wrapper">
                <div class="content container-fluid">

					<!-- Page Header -->
					<div class="page-header">
						<div class="content-page-header">
							<h5>Default Datatable</h5>
						</div>	
					</div>
					<!-- /Page Header -->
					
					<div class="row">
						<div class="col-sm-12">
							<div class="card">
								<div class="card-header card-buttons">
									<h4 class="card-title">Default Datatable</h4>
									<p class="card-text">
										This is the most basic example of the datatables with zero configuration. Use the <code>.datatable</code> class to initialize datatables.
									</p>
								</div>
								<div class="card-body">

									<div class="table-responsive">
										<table class="datatable table table-stripped">
											<thead>
												<tr>
													<th>Name</th>
													<th>Position</th>
													<th>Office</th>
													<th>Age</th>
													<th>Start date</th>
													<th>Salary</th>
												</tr>
											</thead>
											<tbody>
												@php
                                            $json = file_get_contents(public_path('../public/assets/json/tables-datatables.json'));
                                            $datatables = json_decode($json, true);
                                        @endphp
                                        @foreach ($datatables as $datatables)
                                            <tr>
                                                <td>{{ $datatables['Name'] }}</td>
                                                <td>{{ $datatables['Position'] }}</td>
                                                <td>{{ $datatables['Office'] }}</td>
                                                <td>{{ $datatables['Age'] }}</td>
                                                <td>{{ $datatables['Startdate'] }}</td>
                                                <td>{{ $datatables['Salary'] }}</td>
                                            </tr>
                                        @endforeach
											</tbody>
										</table>
									</div>
								</div>
							</div>
						</div>
					</div>
				
				</div>			
			</div>
			<!-- /Page Wrapper -->
@endsection