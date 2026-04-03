@if (Route::is(['companies']))
   
		<!-- Add Asset -->
		<div class="toggle-sidebar">
			<div class="sidebar-layout-filter">
				<div class="sidebar-header">
					<h5>Filter</h5>
					<a href="#" class="sidebar-closes"><i class="fa-regular fa-circle-xmark"></i></a>
				</div>
				<div class="sidebar-body">
					<form action="#" autocomplete="off">
						<!-- Customer -->
						<div class="accordion" id="accordionMain1">
							<div class="card-header-new" id="headingOne">
								<h6 class="filter-title">
									<a href="javascript:void(0);" class="w-100" data-bs-toggle="collapse"
										data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
										Company Name
										<span class="float-end"><i class="fa-solid fa-chevron-down"></i></span>
									</a>
								</h6>
							</div>

							<div id="collapseOne" class="collapse show" aria-labelledby="headingOne"
								data-bs-parent="#accordionExample1">
								<div class="card-body-chat">
									<div class="row">
										<div class="col-md-12">
											<div id="checkBoxes1">
												<div class="form-custom">
													<input type="text" class="form-control member-search-dropdown" id="member_search1"
														placeholder="Search Company">
													<span><img src="{{URL::asset('/assets/img/icons/search.svg')}}" alt="img"></span>
												</div>
												<div class="selectBox-cont search-dropdown-item">
													<label class="custom_check w-100">
														<input type="checkbox" name="bystatus">
														<span class="checkmark"></span> Hermann Groups
													</label>
													<label class="custom_check w-100">
														<input type="checkbox" name="bystatus">
														<span class="checkmark"></span> Skiles LLC
													</label>
													<label class="custom_check w-100">
														<input type="checkbox" name="bystatus">
														<span class="checkmark"></span> Kerluke Group
													</label>
													<label class="custom_check w-100">
														<input type="checkbox" name="bystatus">
														<span class="checkmark"></span>Schowalter Group
													</label>
													<label class="custom_check w-100">
														<input type="checkbox" name="bystatus">
														<span class="checkmark"></span> Accentric Global
													</label>
													<label class="custom_check w-100">
														<input type="checkbox" name="bystatus">
														<span class="checkmark"></span> Dexter Matrix
													</label>
													<label class="custom_check w-100">
														<input type="checkbox" name="bystatus">
														<span class="checkmark"></span> Emporis Technologies
													</label>
													<label class="custom_check w-100">
														<input type="checkbox" name="bystatus">
														<span class="checkmark"></span>High Tech Lead
													</label>
													<label class="custom_check w-100">
														<input type="checkbox" name="bystatus">
														<span class="checkmark"></span> Beacon Softwares
													</label>
													<label class="custom_check w-100">
														<input type="checkbox" name="bystatus">
														<span class="checkmark"></span>Global tech
													</label>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<!-- /Customer -->

						<!-- By Plane -->
						<div class="accordion" id="accordionMain3">
							<div class="card-header-new" id="headingThree">
								<h6 class="filter-title">
									<a href="javascript:void(0);" class="w-100 collapsed" data-bs-toggle="collapse"
										data-bs-target="#collapseThree" aria-expanded="true"
										aria-controls="collapseThree">
										Plans
										<span class="float-end"><i class="fa-solid fa-chevron-down"></i></span>
									</a>
								</h6>
							</div>

							<div id="collapseThree" class="collapse" aria-labelledby="headingThree"
								data-bs-parent="#accordionExample3">
								<div class="card-body-chat">
									<div id="checkBoxes2">
										<div class="selectBox-cont">
											<label class="custom_check w-100">
												<input type="checkbox" name="bystatus">
												<span class="checkmark"></span> All Plans
											</label>
											<label class="custom_check w-100">
												<input type="checkbox" name="bystatus">
												<span class="checkmark"></span> Advanced
											</label>
											<label class="custom_check w-100">
												<input type="checkbox" name="bystatus">
												<span class="checkmark"></span> Basic
											</label>
											<label class="custom_check w-100">
												<input type="checkbox" name="bystatus">
												<span class="checkmark"></span> Enterprise
											</label>
											<label class="custom_check w-100">
												<input type="checkbox" name="bystatus">
												<span class="checkmark"></span>Premium
											</label>
											<label class="custom_check w-100">
												<input type="checkbox" name="bystatus">
												<span class="checkmark"></span>Free
											</label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<!-- /By Plane -->

						<!-- By Plane -->
						<div class="accordion" id="accordionMain4">
							<div class="card-header-new" id="headingFour">
								<h6 class="filter-title">
									<a href="javascript:void(0);" class="w-100 collapsed" data-bs-toggle="collapse"
										data-bs-target="#collapseFour" aria-expanded="true"
										aria-controls="collapseFour">
										Plans Type
										<span class="float-end"><i class="fa-solid fa-chevron-down"></i></span>
									</a>
								</h6>
							</div>

							<div id="collapseFour" class="collapse" aria-labelledby="headingFour"
								data-bs-parent="#accordionExample4">
								<div class="card-body-chat">
									<div id="checkBoxes2">
										<div class="selectBox-cont">
											<label class="custom_check w-100">
												<input type="checkbox" name="bystatus">
												<span class="checkmark"></span> All Plan Type
											</label>
											<label class="custom_check w-100">
												<input type="checkbox" name="bystatus">
												<span class="checkmark"></span> Monthly
											</label>
											<label class="custom_check w-100">
												<input type="checkbox" name="bystatus">
												<span class="checkmark"></span> Weekly
											</label>
											<label class="custom_check w-100">
												<input type="checkbox" name="bystatus">
												<span class="checkmark"></span>Lifetime
											</label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<!-- /By Plane -->

						<!-- Select Date -->
						<div class="accordion accordion-last" id="accordionMain2">
							<div class="card-header-new" id="headingTwo">
								<h6 class="filter-title">
									<a href="javascript:void(0);" class="w-100 collapsed" data-bs-toggle="collapse"
										data-bs-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">
										Select Date
										<span class="float-end"><i class="fa-solid fa-chevron-down"></i></span>
									</a>
								</h6>
							</div>

							<div id="collapseTwo" class="collapse" aria-labelledby="headingTwo"
								data-bs-parent="#accordionExample2">
								<div class="card-body-chat">
									<div class="input-block mb-3">
										<label class="form-control-label">From</label>
										<div class="cal-icon">
											<input type="email" class="form-control datetimepicker"
												placeholder="DD-MM-YYYY">
										</div>
									</div>
									<div class="input-block mb-3">
										<label class="form-control-label">To</label>
										<div class="cal-icon">
											<input type="email" class="form-control datetimepicker"
												placeholder="DD-MM-YYYY">
										</div>
									</div>
								</div>
							</div>
						</div>
						<!-- /Select Date -->

						

						<div class="filter-buttons">
							<button type="submit" class="d-inline-flex align-items-center justify-content-center btn w-100 btn-primary">
								Apply
							</button>
							<button type="submit" class="d-inline-flex align-items-center justify-content-center btn w-100 btn-secondary">
								Reset
							</button>
						</div>
					</form>

				</div>
			</div>
		</div>
		<!--/Add Asset -->

		<!-- Delete Items Modal -->
		<div class="modal custom-modal fade modal-delete" id="delete_modal" role="dialog">
			<div class="modal-dialog modal-dialog-centered modal-md">
				<div class="modal-content">
					<div class="modal-body">
						<div class="form-header">
							<div class="delete-modal-icon">
								<span><i class="fe fe-check-circle"></i></span>
							</div>
							<h3>Are You Sure?</h3>
							<p>You want delete company</p>
						</div>
						<div class="modal-btn delete-action">
							<div class="modal-footer justify-content-center p-0">
								<button type="submit" data-bs-dismiss="modal" class="btn btn-primary paid-continue-btn me-2">Yes, Delete</button>
								<button type="button" data-bs-dismiss="modal" class="btn btn-back cancel-btn">No, Cancel</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- /Delete Items Modal -->

		<!-- Add Companies Modal -->
		<div class="modal custom-modal custom-lg-modal fade p-20" id="add_companies" role="dialog">
			<div class="modal-dialog modal-dialog-centered modal-md">
				<div class="modal-content">
					<div class="modal-header border-0">
						<div class="form-header modal-header-title text-start mb-0">
							<h4 class="mb-0">Add New Company</h4>
						</div>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
						</button>
					</div>
					<form action="{{url('companies')}}">
						
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="form-field-item">
										<h5 class="form-title">Company Profile</h5>
										<div class="profile-picture">
											<div class="upload-profile">
												<div class="profile-img company-profile-img">
													<img id="company-img" class="img-fluid me-0" src="{{URL::asset('/assets/img/companies/company-add-img.svg')}}" alt="profile-img">
												</div>
												<div class="add-profile">
													<h5>Upload a New Photo</h5>
													<span>Profile-pic.jpg</span>
												</div>
											</div>
											<div class="img-upload">
												<label class="btn btn-upload">
													Upload <input type="file">
												</label>
												<a class="btn btn-remove">Remove</a>
											</div>										
										</div>
									</div>
								</div>
								<div class="col-md-12">
									<div class="input-block mb-3">
										<label class="form-label">Name </label> 
										<input type="text" class="form-control" placeholder="Enter Company Name">
									</div>
								</div>
								<div class="col-md-6">
									<div class="input-block mb-3">
										<label class="form-label">Email Address </label> 
										<input type="text" class="form-control" placeholder="Enter Company Email">
									</div>
								</div>
								<div class="col-md-6">
									<div class="input-block mb-3">
										<label class="form-label">Account Url </label> 
										<div class="url-text-box">
											<input type="text" class="form-control" placeholder="Account URL">
											<span class="url-text">kanakku.com</span>
										</div>
										
									</div>
								</div>
								<div class="col-md-6">
									<div class="input-block mb-3">
										<label class="mb-2">Phone Number</label>
										<input class="form-control" id="phone" name="phone" type="text" placeholder="Phone Number">
									</div>
								</div>
								<div class="col-md-6">
									<div class="input-block mb-3">
										<label class="form-label">Website </label> 
										<input type="text" class="form-control" placeholder="Enter Website">
									</div>
								</div>
								<div class="col-md-6">
									<div class="input-block mb-3">
										<label class="form-control-label">Password</label>
										<div class="pass-group modal-password-field">
											<input type="password" class="form-control pass-input" placeholder="Password">
											<span class="fas toggle-password fa-eye-slash"></span>
										</div>
									</div>
								</div>
								<div class="col-md-6">
									<div class="input-block mb-3">
										<label class="form-control-label">Confirm Password</label>
										<div class="pass-group modal-password-field">
											<input type="password" class="form-control pass-input-two" placeholder="Confirm Password">
											<span class="fas toggle-password-two fa-eye-slash"></span>
										</div>
									</div>
								</div>
								<div class="col-md-12">
									<div class="input-block mb-3">
										<label class="form-label">Company Address </label> 
										<textarea type="text" class="form-control" rows="3"></textarea>
									</div>
								</div>
								<div class="col-md-6">
									<div class="input-block mb-3">
										<label>Plan Name</label>
										<select class="select">
											<option>Select Plan</option>
											<option>All Plans</option>
											<option>Advanced</option>
											<option>Basic</option>
											<option>Enterprise</option>
											<option>Premium</option>
											<option>Free</option>
										</select>
									</div>
								</div>
								<div class="col-md-6">
									<div class="input-block mb-3">
										<label>Plan Type</label>
										<select class="select">
											<option>Select Plan Type</option>
											<option>Monthly</option>
											<option>Yearly</option>
											<option>Lifetime</option>
										</select>
									</div>
								</div>
								<div class="col-md-6">
									<div class="input-block mb-3">
										<label>Select Currency</label>
										<select class="select">
											<option>Select Currency</option>
											<option>₹</option>
											<option>$</option>
											<option>£</option>
											<option>€</option>
										</select>
									</div>
								</div>
								<div class="col-md-6">
									<div class="input-block mb-3">
										<label>Select Language</label>
										<select class="select">
											<option>Select language</option>
											<option>English</option>
											<option>French</option>
											<option>Spanish</option>
											<option>German</option>
										</select>
									</div>
								</div>
								<div class="col-md-12">
									<div class="d-flex align-items-center mb-3">
										<h6 class="mb-0">Status</h6>
										<div class="status-toggle">
											<input id="access-trail" class="check" type="checkbox" checked="">
											<label for="access-trail" class="checktoggle checkbox-bg">checkbox</label>
										</div>
									</div>
								</div>
							</div>
							
						</div>
						<div class="modal-footer">
							<button type="button" data-bs-dismiss="modal" class="btn btn-back cancel-btn me-2">Cancel</button>
							<button type="submit" data-bs-dismiss="modal" class="btn btn-primary paid-continue-btn">Add New</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Add Companies Modal -->

		<!-- Edit Companies Modal -->
		<div class="modal custom-modal custom-lg-modal fade p-20" id="edit_companies" role="dialog">
			<div class="modal-dialog modal-dialog-centered modal-md">
				<div class="modal-content">
					<div class="modal-header border-0">
						<div class="form-header modal-header-title text-start mb-0">
							<h4 class="mb-0">Edit Company</h4>
						</div>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
						</button>
					</div>
					<form action="{{url('companies')}}">
						
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="form-field-item">
										<h5 class="form-title">Company Profile</h5>
										<div class="profile-picture">
											<div class="upload-profile">
												<div class="profile-img company-profile-img">
													<img id="edit-company-img" class="img-fluid me-0" src="{{URL::asset('/assets/img/companies/company-01.svg')}}" alt="profile-img">
												</div>
												<div class="add-profile">
													<h5>Upload a New Photo</h5>
													<span>Profile-pic.jpg</span>
												</div>
											</div>
											<div class="img-upload">
												<label class="btn btn-upload">
													Upload <input type="file">
												</label>
												<a class="btn btn-remove">Remove</a>
											</div>										
										</div>
									</div>
								</div>
								<div class="col-md-12">
									<div class="input-block mb-3">
										<label class="form-label">Name </label> 
										<input type="text" class="form-control" placeholder="Enter Company Name" value="Hermann Groups">
									</div>
								</div>
								<div class="col-md-6">
									<div class="input-block mb-3">
										<label class="form-label">Email Address </label> 
										<input type="text" class="form-control" placeholder="Enter Company Email" value="info@example.com">
									</div>
								</div>
								<div class="col-md-6">
									<div class="input-block mb-3">
										<label class="form-label">Account Url </label> 
										<div class="url-text-box">
											<input type="text" class="form-control" placeholder="Account URL" value="www.hru.example.com">
											<span class="url-text">kanakku.com</span>
										</div>
										
									</div>
								</div>
								<div class="col-md-6">
									<div class="input-block mb-3">
										<label class="mb-2">Phone Number</label>
										<input class="form-control" id="phone_2" name="phone" type="text" placeholder="Phone Number" value="1245547887">
									</div>
								</div>
								<div class="col-md-6">
									<div class="input-block mb-3">
										<label class="form-label">Website </label> 
										<input type="text" class="form-control" placeholder="Enter Website" value="www.example.com">
									</div>
								</div>
								<div class="col-md-6">
									<div class="input-block mb-3">
										<label class="form-control-label">Password</label>
										<div class="pass-group modal-password-field">
											<input type="password" class="form-control pass-input" placeholder="Password" value="12345">
											<span class="fas toggle-password fa-eye-slash"></span>
										</div>
									</div>
								</div>
								<div class="col-md-6">
									<div class="input-block mb-3">
										<label class="form-control-label">Confirm Password</label>
										<div class="pass-group modal-password-field">
											<input type="password" class="form-control pass-input-two" placeholder="Confirm Password" value="12345">
											<span class="fas toggle-password-two fa-eye-slash"></span>
										</div>
									</div>
								</div>
								<div class="col-md-12">
									<div class="input-block mb-3">
										<label class="form-label">Company Address </label> 
										<textarea type="text" class="form-control" rows="3">22 Junior Avenue Duluth, GA 30097</textarea>
									</div>
								</div>
								<div class="col-md-6">
									<div class="input-block mb-3">
										<label>Plan Name</label>
										<select class="select">
											<option>All Plans</option>
											<option>Advanced</option>
											<option>Basic</option>
											<option>Enterprise</option>
											<option>Premium</option>
											<option>Free</option>
										</select>
									</div>
								</div>
								<div class="col-md-6">
									<div class="input-block mb-3">
										<label>Plan Type</label>
										<select class="select">
											<option>Monthly</option>
											<option>Yearly</option>
											<option>Lifetime</option>
										</select>
									</div>
								</div>
								<div class="col-md-6">
									<div class="input-block mb-3">
										<label>Select Currency</label>
										<select class="select">
											<option>United Stated Dollar (USD)</option>
											<option>$</option>
											<option>£</option>
											<option>€</option>
										</select>
									</div>
								</div>
								<div class="col-md-6">
									<div class="input-block mb-3">
										<label>Select Language</label>
										<select class="select">
											<option>English</option>
											<option>French</option>
											<option>Spanish</option>
											<option>German</option>
										</select>
									</div>
								</div>
								<div class="col-md-12">
									<div class="d-flex align-items-center mb-3">
										<h6 class="mb-0">Status</h6>
										<div class="status-toggle">
											<input id="access-trail-2" class="check" type="checkbox" checked="">
											<label for="access-trail-2" class="checktoggle checkbox-bg">checkbox</label>
										</div>
									</div>
								</div>
							</div>
							
						</div>
						<div class="modal-footer">
							<button type="button" data-bs-dismiss="modal" class="btn btn-back cancel-btn me-2">Cancel</button>
							<button type="submit" data-bs-dismiss="modal" class="btn btn-primary paid-continue-btn">Save Changes</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Edit Companies Modal -->

		<!-- View Companies Modal -->
		<div class="modal custom-modal custom-lg-modal fade p-20" id="view_companies" role="dialog">
			<div class="modal-dialog modal-dialog-centered modal-md">
				<div class="modal-content">
					<div class="modal-header border-0">
						<div class="form-header modal-header-title text-start mb-0">
							<h4 class="mb-0">Company Details</h4>
						</div>
						<div class="d-flex details-edit-link">
							<a href="#" class="modal-edit-link d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_companies"><i class="fe fe-edit me-2"></i>Edit Company</a>
							<button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close">
							</button>
						</div>
						
					</div>
					<div class="modal-body pb-0">
						<div class="row">
							<div class="col-md-12">
								<div class="form-field-item">
									<div class="profile-picture company-detail-head">
										<div class="upload-profile">
											<div class="profile-img company-profile-img">
												<img id="view-company-img" class="img-fluid me-0" src="{{URL::asset('/assets/img/companies/company-01.svg')}}" alt="profile-img">
											</div>
											<div class="add-profile">
												<h5>Hermann Groups</h5>
												<span>Hermllrich@example.com</span>
											</div>
										</div>		
										<span class="badge bg-success-light d-inline-flex align-items-center"><i class="fe fe-check me-1"></i>Active</span>								
									</div>
								</div>
							</div>
							<div class="col-md-12">
								<div class="plane-basic-info">
									<h5>Basic Info</h5>
									<div class="row">
										<div class="col-md-4 col-sm-6">
											<div class="basic-info-detail">
												<h6>Account URL</h6>
												<p>hru.example.com</p>
											</div>
											
										</div>
										<div class="col-md-4 col-sm-6">
											<div class="basic-info-detail">
												<h6>Phone Number</h6>
												<p>+1 15541 54544</p>
											</div>
											
										</div>
										<div class="col-md-4 col-sm-6">
											<div class="basic-info-detail">
												<h6>Website</h6>
												<p>www.example.com</p>
											</div>
											
										</div>
										<div class="col-md-4 col-sm-6">
											<div class="basic-info-detail">
												<h6>Company Address</h6>
												<p>22 Junior Avenue <br>Duluth, GA 30097</p>
											</div>
											
										</div>
										<div class="col-md-4 col-sm-6">
											<div class="basic-info-detail">
												<h6>Currency</h6>
												<p>United Stated Dollar (USD)</p>
											</div>
											
										</div>
										<div class="col-md-4 col-sm-6">
											<div class="basic-info-detail">
												<h6>Language</h6>
												<p>English</p>
											</div>
											
										</div>
									</div>
								</div>
								
							</div>
							<div class="col-md-12">
								<div class="plane-basic-info plane-detail">
									<h5>Plan Details</h5>
									<div class="row">
										<div class="col-md-4 col-sm-6">
											<div class="basic-info-detail">
												<h6>Plan Name</h6>
												<p>Enterprise</p>
											</div>
											
										</div>
										<div class="col-md-4 col-sm-6">
											<div class="basic-info-detail">
												<h6>Plan Type</h6>
												<p>Yearly</p>
											</div>
											
										</div>
										<div class="col-md-4 col-sm-6">
											<div class="basic-info-detail">
												<h6>Price</h6>
												<p>$200</p>
											</div>
											
										</div>
										<div class="col-md-4 col-sm-6">
											<div class="basic-info-detail">
												<h6>Register Date</h6>
												<p>15 Jan 2024</p>
											</div>
											
										</div>
										<div class="col-md-4 col-sm-6">
											<div class="basic-info-detail">
												<h6>Expiring On</h6>
												<p>15 Jan 2025</p>
											</div>
											
										</div>
									</div>
								</div>
								
							</div>
						</div>
						
					</div>
				</div>
			</div>
		</div>
		<!-- /View Companies Modal -->

		<!-- Change Plane Modal -->
		<div class="modal custom-modal custom-lg-modal fade p-20" id="change_pane" role="dialog">
			<div class="modal-dialog modal-dialog-centered modal-md">
				<div class="modal-content">
					<div class="modal-header border-0">
						<div class="form-header modal-header-title text-start mb-0">
							<h4 class="mb-0">Update Package</h4>
						</div>
						<button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close">
						</button>
						
					</div>
					<form action="#">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="plane-basic-info plane-detail">
										<h5>Current Plan Details</h5>
										<div class="row">
											<div class="col-md-4 col-sm-6">
												<div class="basic-info-detail">
													<h6>Company Name</h6>
													<p>Hermann Groups</p>
												</div>
												
											</div>
											<div class="col-md-4 col-sm-6">
												<div class="basic-info-detail">
													<h6>Plan Name</h6>
													<p>Enterprise</p>
												</div>
												
											</div>
											<div class="col-md-4 col-sm-6">
												<div class="basic-info-detail">
													<h6>Plan Type</h6>
													<p>Yearly</p>
												</div>
												
											</div>
											<div class="col-md-4 col-sm-6">
												<div class="basic-info-detail">
													<h6>Price</h6>
													<p>$200</p>
												</div>
												
											</div>
											<div class="col-md-4 col-sm-6">
												<div class="basic-info-detail">
													<h6>Register Date</h6>
													<p>15 Jan 2024</p>
												</div>
												
											</div>
											<div class="col-md-4 col-sm-6">
												<div class="basic-info-detail">
													<h6>Expiring On</h6>
													<p>15 Jan 2025</p>
												</div>
												
											</div>
										</div>
									</div>
									
								</div>
								<div class="col-md-12">
									<div class="plane-basic-info plane-detail">
										<h5>Change Company Plan</h5>
										<div class="row">
											<div class="col-md-6">
												<div class="input-block mb-3">
													<label>Plan Name</label>
													<select class="select">
														<option>Enterprise</option>
														<option>Advanced</option>
														<option>Basic</option>
														<option>Premium</option>
														<option>Free</option>
													</select>
												</div>																			
											</div>
											<div class="col-md-6">
												<div class="input-block mb-3">
													<label>Plan Type</label>
													<select class="select">
														<option>Yearly</option>
														<option>Monthly</option>
														<option>Lifetime</option>
													</select>
												</div>
											</div>
											<div class="col-md-6">
												<div class="input-block mb-3">
													<label class="form-label">Amount </label> 
													<input type="text" class="form-control">
												</div>
											</div>
											<div class="col-md-6">
												<div class="input-block mb-3">
													<label class="form-control-label">Payment Date</label>
													<div class="cal-icon">
														<input type="email" class="form-control datetimepicker" placeholder="DD-MM-YYYY">
													</div>
												</div>
											</div>
											<div class="col-md-6">
												<div class="input-block mb-3">
													<label class="form-control-label">Next Payment Date</label>
													<div class="cal-icon">
														<input type="email" class="form-control datetimepicker" placeholder="DD-MM-YYYY">
													</div>
												</div>
											</div>
											<div class="col-md-6">
												<div class="input-block mb-3">
													<label class="form-control-label">Expiring On</label>
													<div class="cal-icon">
														<input type="email" class="form-control datetimepicker" placeholder="DD-MM-YYYY">
													</div>
												</div>
											</div>
										</div>
									</div>
									
								</div>
							</div>
							
						</div>
						<div class="modal-footer">
							<button type="button" data-bs-dismiss="modal" class="btn btn-back cancel-btn me-2">Cancel</button>
							<button type="submit" data-bs-dismiss="modal" class="btn btn-primary paid-continue-btn">Save Changes</button>
						</div>
					</form>
					
				</div>
			</div>
		</div>
		<!-- /Change Plane Modal -->
@endif

@if (Route::is(['all-blogs']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="deleteModal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Blogs</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['inactive-blog']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="deleteModal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Blogs</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if(Route::is(['customers-ledger']))
<!-- Delete Items Modal -->
<div class="modal custom-modal fade" id="delete_modal" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-body">
                <div class="form-header">
                    <h3>Delete Ledger</h3>
                    <p>Are you sure want to delete?</p>
                </div>
                <div class="modal-btn delete-action">
                    <div class="row">
                        <div class="col-6">
                            <button type="reset" data-bs-dismiss="modal" class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                        </div>
                        <div class="col-6">
                            <button type="submit" data-bs-dismiss="modal" class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Delete Items Modal -->
@endif

@if (Route::is(['subscription']))
<!-- Filter -->
<div class="toggle-sidebar">
    <div class="sidebar-layout-filter">
        <div class="sidebar-header">
            <h5>Filter</h5>
            <a href="#" class="sidebar-closes"><i class="fa-regular fa-circle-xmark"></i></a>
        </div>
        <div class="sidebar-body">						
            <form action="#" autocomplete="off">
                <!-- Customer -->
                <div class="accordion" id="accordionMain1">
                    <div class="card-header-new" id="headingOne">
                        <h6 class="filter-title">
                        <a href="javascript:void(0);" class="w-100"  data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                            Subscriber
                            <span class="float-end"><i class="fa-solid fa-chevron-down"></i></span>
                        </a> 
                        </h6>
                    </div>
                
                    <div id="collapseOne" class="collapse show" aria-labelledby="headingOne"  data-bs-parent="#accordionExample1">
                        <div class="card-body-chat">
                            <div class="row">
                                <div class="col-md-12">
                                    <div id="checkBoxes">
                                        <div class="form-custom">														
                                            <input type="text" class="form-control  member-search-dropdown" id="member_search" placeholder="Search Subscriber">
                                            <span><img src="{{ URL::asset('/assets/img/icons/search.svg')}}" alt="img"></span>
                                        </div>
                                        <div class="selectBox-cont search-dropdown-item">
                                            <label class="custom_check w-100">
                                                <input type="checkbox" name="bystatus">
                                                <span class="checkmark"></span> Hermann Groups
                                            </label>
                                            <label class="custom_check w-100">
                                                <input type="checkbox" name="bystatus">
                                                <span class="checkmark"></span> Skiles LLC
                                            </label>
                                            <label class="custom_check w-100">
                                                <input type="checkbox" name="bystatus">
                                                <span class="checkmark"></span> Kerluke Group
                                            </label>
                                            <label class="custom_check w-100">
                                                <input type="checkbox" name="bystatus">
                                                <span class="checkmark"></span>Schowalter Group
                                            </label>
                                            <label class="custom_check w-100">
                                                <input type="checkbox" name="bystatus">
                                                <span class="checkmark"></span> Accentric Global
                                            </label>
                                            <label class="custom_check w-100">
                                                <input type="checkbox" name="bystatus">
                                                <span class="checkmark"></span> Dexter Matrix
                                            </label>
                                            <label class="custom_check w-100">
                                                <input type="checkbox" name="bystatus">
                                                <span class="checkmark"></span> Emporis Technologies
                                            </label>
                                            <label class="custom_check w-100">
                                                <input type="checkbox" name="bystatus">
                                                <span class="checkmark"></span>High Tech Lead
                                            </label>
                                            <label class="custom_check w-100">
                                                <input type="checkbox" name="bystatus">
                                                <span class="checkmark"></span> Beacon Softwares
                                            </label>
                                            <label class="custom_check w-100">
                                                <input type="checkbox" name="bystatus">
                                                <span class="checkmark"></span>Global tech
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /Customer -->

                <!-- By Package -->
                <div class="accordion" id="accordionMain4">
                    <div class="card-header-new" id="headingFive">
                        <h6 class="filter-title">
                        <a href="javascript:void(0);" class="w-100"  data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="true" aria-controls="collapseFour">
                            Plans
                            <span class="float-end"><i class="fa-solid fa-chevron-down"></i></span>
                        </a> 
                        </h6>
                    </div>
                
                    <div id="collapseFour" class="collapse" aria-labelledby="headingFive"  data-bs-parent="#accordionExample3">
                        <div class="card-body-chat">
                            <div id="checkBoxes2">
                                <div class="selectBox-cont">
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bypackage">
                                        <span class="checkmark"></span> All Plans
                                    </label>
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bypackage">
                                        <span class="checkmark"></span>  Advanced
                                    </label>
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bypackage">
                                        <span class="checkmark"></span>  Basic
                                    </label>
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bypackage">
                                        <span class="checkmark"></span>  Enterprise
                                    </label>
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bypackage">
                                        <span class="checkmark"></span> Premium
                                    </label>
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bypackage">
                                        <span class="checkmark"></span> Free
                                    </label>
                                </div>
                            </div>	
                        </div>
                    </div>
                </div>
                <!-- /By Package -->

                <!-- By Status -->
                <div class="accordion" id="accordionMain3">
                    <div class="card-header-new" id="headingThree">
                        <h6 class="filter-title">
                        <a href="javascript:void(0);" class="w-100"  data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="true" aria-controls="collapseThree">
                            Plans Type
                            <span class="float-end"><i class="fa-solid fa-chevron-down"></i></span>
                        </a> 
                        </h6>
                    </div>
                
                    <div id="collapseThree" class="collapse" aria-labelledby="headingThree"  data-bs-parent="#accordionExample3">
                        <div class="card-body-chat">
                            <div id="checkBoxes3">
                                <div class="selectBox-cont">
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bystatus">
                                        <span class="checkmark"></span> All Plan Type
                                    </label>
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bystatus">
                                        <span class="checkmark"></span>  Monthly
                                    </label>
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bystatus">
                                        <span class="checkmark"></span>Weekly
                                    </label>
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bystatus">
                                        <span class="checkmark"></span> Lifetime
                                    </label>
                                </div>
                            </div>	
                        </div>
                    </div>
                </div>
                <!-- /By Status -->

                <!-- Payment Gateway -->
                <div class="accordion" id="accordionMain5">
                    <div class="card-header-new" id="headingfive">
                        <h6 class="filter-title">
                        <a href="javascript:void(0);" class="w-100"  data-bs-toggle="collapse" data-bs-target="#collapsefive" aria-expanded="true" aria-controls="collapsefive">
                            Payment Gateway
                            <span class="float-end"><i class="fa-solid fa-chevron-down"></i></span>
                        </a> 
                        </h6>
                    </div>
                
                    <div id="collapsefive" class="collapse" aria-labelledby="headingfive"  data-bs-parent="#accordionExample5">
                        <div class="card-body-chat">
                            <div id="checkBoxes4">
                                <div class="selectBox-cont">
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bystatus">
                                        <span class="checkmark"></span> All 
                                    </label>
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bystatus">
                                        <span class="checkmark"></span>  Paypal
                                    </label>
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bystatus">
                                        <span class="checkmark"></span>Credit Card
                                    </label>
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bystatus">
                                        <span class="checkmark"></span> Debit Card
                                    </label>
                                </div>
                            </div>	
                        </div>
                    </div>
                </div>
                <!-- /Payment Gateway -->

                <!-- Select Date -->
                <div class="accordion accordion-last" id="accordionMain2">
                    <div class="card-header-new" id="headingTwo">
                        <h6 class="filter-title">
                        <a href="javascript:void(0);" class="w-100"  data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">
                            Created Date
                            <span class="float-end"><i class="fa-solid fa-chevron-down"></i></span>
                        </a> 
                        </h6>
                    </div>
                
                    <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo"  data-bs-parent="#accordionExample2">
                        <div class="card-body-chat">
                            <div class="input-block mb-3">
                                <label class="form-control-label">From</label>
                                <div class="cal-icon">
                                    <input type="email" class="form-control datetimepicker" placeholder="From Date">
                                </div>
                            </div>
                            <div class="input-block mb-3">
                                <label class="form-control-label">To</label>
                                <div class="cal-icon">
                                    <input type="email" class="form-control datetimepicker" placeholder="To Date">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /Select Date -->

                

                <div class="filter-buttons">
                    <button type="submit" class="d-inline-flex align-items-center justify-content-center btn w-100 btn-primary">
                        Apply
                    </button>
                    <button type="submit" class="d-inline-flex align-items-center justify-content-center btn w-100 btn-secondary">
                        Reset
                    </button>
                </div>
            </form>
            
        </div>
    </div>
</div>
<!-- /Filter -->

  <!-- Edit Supplier Modal -->
  <div class="modal custom-modal fade" id="edit_contact" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div class="form-header modal-header-title text-start mb-0">
                    <h4 class="mb-0">Edit Contact Messages</h4>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-lg-12 col-md-12">
                        <div class="input-block mb-3">
                            <label>Name</label>
                            <input type="text" class="form-control" value="John Smith" placeholder="Enter Name">
                        </div>
                    </div>
                    <div class="col-lg-12 col-md-12">
                        <div class="input-block mb-3">
                            <label>Email</label>
                            <input type="text" class="form-control" value="john@example.com" placeholder="Email">
                        </div>
                    </div>
                    <div class="col-lg-12 col-md-12">
                        <div class="input-block mb-3">
                            <label>Mobile</label>
                            <input type="text" class="form-control" value="989-438-3131" placeholder="Enter Mobile Number">
                        </div>
                    </div>
                    <div class="col-lg-12 col-md-12">
                        <div class="input-block mb-3">
                            <label>Message</label>
                            <input type="text" class="form-control" value="Lorem ipsum dolor" placeholder="message">
                        </div>
                    </div>
                    <div class="col-lg-12 col-md-12">
                        <div class="input-block mb-0">
                            <label>Date</label>
                            <input type="text" class="form-control" value="15 Dec 2023, 04:35 PM" placeholder="message">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" data-bs-dismiss="modal" class="btn btn-back cancel-btn me-2">Cancel</a>
                <a href="#" data-bs-dismiss="modal" class="btn btn-primary paid-continue-btn">Update</a>
            </div>
        </div>
    </div>
</div>
<!-- /Edit Supplier Modal -->

<!-- Delete Items Modal -->
<div class="modal custom-modal fade modal-delete" id="delete_modal" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-body">
                <div class="form-header">
                    <div class="delete-modal-icon">
                        <span><i class="fe fe-check-circle"></i></span>
                    </div>
                    <h3>Are You Sure?</h3>
                    <p>You want delete plan</p>
                </div>
                <div class="modal-btn delete-action">
                    <div class="modal-footer justify-content-center p-0">
                        <button type="submit" data-bs-dismiss="modal" class="btn btn-primary paid-continue-btn me-2">Yes, Delete</button>
                        <button type="button" data-bs-dismiss="modal" class="btn btn-back cancel-btn">No, Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Delete Items Modal -->

@endif

@if (Route::is(['packages']))
   <!-- Add Asset -->
   <div class="toggle-sidebar">
    <div class="sidebar-layout-filter">
        <div class="sidebar-header">
            <h5>Filter</h5>
            <a href="#" class="sidebar-closes"><i class="fa-regular fa-circle-xmark"></i></a>
        </div>
        <div class="sidebar-body">
            <form action="#" autocomplete="off">

                <!-- By Plane -->
                <div class="accordion" id="accordionMain3">
                    <div class="card-header-new" id="headingThree">
                        <h6 class="filter-title">
                            <a href="javascript:void(0);" class="w-100 collapsed" data-bs-toggle="collapse"
                                data-bs-target="#collapseThree" aria-expanded="true"
                                aria-controls="collapseThree">
                                Plans
                                <span class="float-end"><i class="fa-solid fa-chevron-down"></i></span>
                            </a>
                        </h6>
                    </div>

                    <div id="collapseThree" class="collapse" aria-labelledby="headingThree"
                        data-bs-parent="#accordionExample3">
                        <div class="card-body-chat">
                            <div id="checkBoxes2">
                                <div class="selectBox-cont">
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bystatus">
                                        <span class="checkmark"></span> All Plans
                                    </label>
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bystatus">
                                        <span class="checkmark"></span> Advanced
                                    </label>
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bystatus">
                                        <span class="checkmark"></span> Basic
                                    </label>
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bystatus">
                                        <span class="checkmark"></span> Enterprise
                                    </label>
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bystatus">
                                        <span class="checkmark"></span>Premium
                                    </label>
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bystatus">
                                        <span class="checkmark"></span>Free
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /By Plane -->

                <!-- By Plane -->
                <div class="accordion" id="accordionMain4">
                    <div class="card-header-new" id="headingFour">
                        <h6 class="filter-title">
                            <a href="javascript:void(0);" class="w-100 collapsed" data-bs-toggle="collapse"
                                data-bs-target="#collapseFour" aria-expanded="true"
                                aria-controls="collapseFour">
                                Plans Type
                                <span class="float-end"><i class="fa-solid fa-chevron-down"></i></span>
                            </a>
                        </h6>
                    </div>

                    <div id="collapseFour" class="collapse" aria-labelledby="headingFour"
                        data-bs-parent="#accordionExample4">
                        <div class="card-body-chat">
                            <div id="checkBoxes3">
                                <div class="selectBox-cont">
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bystatus">
                                        <span class="checkmark"></span> All Plan Type
                                    </label>
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bystatus">
                                        <span class="checkmark"></span> Monthly
                                    </label>
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bystatus">
                                        <span class="checkmark"></span> Weekly
                                    </label>
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bystatus">
                                        <span class="checkmark"></span>Lifetime
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /By Plane -->

                <!-- Select Date -->
                <div class="accordion accordion-last" id="accordionMain2">
                    <div class="card-header-new" id="headingTwo">
                        <h6 class="filter-title">
                            <a href="javascript:void(0);" class="w-100 collapsed" data-bs-toggle="collapse"
                                data-bs-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">
                                Created Date
                                <span class="float-end"><i class="fa-solid fa-chevron-down"></i></span>
                            </a>
                        </h6>
                    </div>

                    <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo"
                        data-bs-parent="#accordionExample2">
                        <div class="card-body-chat">
                            <div class="input-block mb-3">
                                <label class="form-control-label">From</label>
                                <div class="cal-icon">
                                    <input type="email" class="form-control datetimepicker"
                                        placeholder="DD-MM-YYYY">
                                </div>
                            </div>
                            <div class="input-block mb-3">
                                <label class="form-control-label">To</label>
                                <div class="cal-icon">
                                    <input type="email" class="form-control datetimepicker"
                                        placeholder="DD-MM-YYYY">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /Select Date -->

                

                <div class="filter-buttons">
                    <button type="submit" class="d-inline-flex align-items-center justify-content-center btn w-100 btn-primary">
                        Apply
                    </button>
                    <button type="submit" class="d-inline-flex align-items-center justify-content-center btn w-100 btn-secondary">
                        Reset
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>
<!--/Add Asset -->

<!-- Delete Items Modal -->
<div class="modal custom-modal fade modal-delete" id="delete_modal" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-body">
                <div class="form-header">
                    <div class="delete-modal-icon">
                        <span><i class="fe fe-check-circle"></i></span>
                    </div>
                    <h3>Are You Sure?</h3>
                    <p>You want delete plan</p>
                </div>
                <div class="modal-btn delete-action">
                    <div class="modal-footer justify-content-center p-0">
                        <button type="submit" data-bs-dismiss="modal" class="btn btn-primary paid-continue-btn me-2">Yes, Delete</button>
                        <button type="button" data-bs-dismiss="modal" class="btn btn-back cancel-btn">No, Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Delete Items Modal -->

@endif

@if (Route::is(['domain-request']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Domain Request</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['domain']))
   <!-- Add Asset -->
   <div class="toggle-sidebar">
    <div class="sidebar-layout-filter">
        <div class="sidebar-header">
            <h5>Filter</h5>
            <a href="#" class="sidebar-closes"><i class="fa-regular fa-circle-xmark"></i></a>
        </div>
        <div class="sidebar-body">						
            <form action="#" autocomplete="off">
                <!-- Company -->
                <div class="accordion" id="accordionMain1">
                    <div class="card-header-new" id="headingOne">
                        <h6 class="filter-title">
                        <a href="javascript:void(0);" class="w-100" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                            Company Name
                            <span class="float-end"><i class="fa-solid fa-chevron-down"></i></span>
                        </a> 
                        </h6>
                    </div>
                
                    <div id="collapseOne" class="collapse show" aria-labelledby="headingOne"  data-bs-parent="#accordionExample1">
                        <div class="card-body-chat">
                            <div class="row">
                                <div class="col-md-12">
                                    <div id="checkBoxes1">
                                        <div class="form-custom">														
                                            <input type="text" class="form-control member-search-dropdown" id="member_search1" placeholder="Search Company">
                                            <span><img src="{{URL::asset('/assets/img/icons/search.svg')}}" alt="img"></span>
                                        </div>
                                        <div class="selectBox-cont search-dropdown-item">
                                            <label class="custom_check w-100">
                                                <input type="checkbox" name="bystatus">
                                                <span class="checkmark"></span> Hermann Groups
                                            </label>
                                            <label class="custom_check w-100">
                                                <input type="checkbox" name="bystatus">
                                                <span class="checkmark"></span> Skiles LLC
                                            </label>
                                            <label class="custom_check w-100">
                                                <input type="checkbox" name="bystatus">
                                                <span class="checkmark"></span> Kerluke Group
                                            </label>
                                            <label class="custom_check w-100">
                                                <input type="checkbox" name="bystatus">
                                                <span class="checkmark"></span>Schowalter Group
                                            </label>
                                            <label class="custom_check w-100">
                                                <input type="checkbox" name="bystatus">
                                                <span class="checkmark"></span> Accentric Global
                                            </label>
                                            <label class="custom_check w-100">
                                                <input type="checkbox" name="bystatus">
                                                <span class="checkmark"></span> Dexter Matrix
                                            </label>
                                            <label class="custom_check w-100">
                                                <input type="checkbox" name="bystatus">
                                                <span class="checkmark"></span> Emporis Technologies
                                            </label>
                                            <label class="custom_check w-100">
                                                <input type="checkbox" name="bystatus">
                                                <span class="checkmark"></span>High Tech Lead
                                            </label>
                                            <label class="custom_check w-100">
                                                <input type="checkbox" name="bystatus">
                                                <span class="checkmark"></span> Beacon Softwares
                                            </label>
                                            <label class="custom_check w-100">
                                                <input type="checkbox" name="bystatus">
                                                <span class="checkmark"></span>Global tech
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /Company -->

                <!-- Domain -->
                <div class="accordion" id="accordionMain5">
                    <div class="card-header-new" id="headingFive">
                        <h6 class="filter-title">
                        <a href="javascript:void(0);" class="w-100" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="true" aria-controls="collapseFive">
                            Domain
                            <span class="float-end"><i class="fa-solid fa-chevron-down"></i></span>
                        </a> 
                        </h6>
                    </div>
                
                    <div id="collapseFive" class="collapse" aria-labelledby="headingFive"  data-bs-parent="#accordionExample5">
                        <div class="card-body-chat">
                            <div class="row">
                                <div class="col-md-12">
                                    <div id="checkBoxes4">
                                        <div class="form-custom">														
                                            <input type="text" class="form-control " id="member_search2" placeholder="Search Domain">
                                            <span><img src="{{URL::asset('assets/img/icons/search.svg')}}" alt="img"></span>
                                        </div>
                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /Domain -->

                <!-- By Package -->
                <div class="accordion" id="accordionMain4">
                    <div class="card-header-new" id="headingFour">
                        <h6 class="filter-title">
                        <a href="javascript:void(0);" class="w-100"  data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="true" aria-controls="collapseFour">
                            Plans
                            <span class="float-end"><i class="fa-solid fa-chevron-down"></i></span>
                        </a> 
                        </h6>
                    </div>
                
                    <div id="collapseFour" class="collapse" aria-labelledby="headingFour"  data-bs-parent="#accordionExample3">
                        <div class="card-body-chat">
                            <div id="checkBoxes3">
                                <div class="selectBox-cont">
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bypackage">
                                        <span class="checkmark"></span> All Plans
                                    </label>
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bypackage">
                                        <span class="checkmark"></span> Advanced
                                    </label>
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bypackage">
                                        <span class="checkmark"></span>  Basic
                                    </label>
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bypackage">
                                        <span class="checkmark"></span>  Enterprise
                                    </label>
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bypackage">
                                        <span class="checkmark"></span> Premium
                                    </label>
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bypackage">
                                        <span class="checkmark"></span> Free
                                    </label>
                                </div>
                            </div>	
                        </div>
                    </div>
                </div>
                <!-- /By Package -->

                <!-- Plans Type -->
                <div class="accordion" id="accordionMain3">
                    <div class="card-header-new" id="headingThree">
                        <h6 class="filter-title">
                        <a href="javascript:void(0);" class="w-100 collapsed"  data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="true" aria-controls="collapseThree">
                            Plans Type
                            <span class="float-end"><i class="fa-solid fa-chevron-down"></i></span>
                        </a> 
                        </h6>
                    </div>
                
                    <div id="collapseThree" class="collapse" aria-labelledby="headingThree"  data-bs-parent="#accordionExample3">
                        <div class="card-body-chat">
                            <div id="checkBoxes2">
                                <div class="selectBox-cont">
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bystatus">
                                        <span class="checkmark"></span> All Plan Type
                                    </label>
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bystatus">
                                        <span class="checkmark"></span> Monthly
                                    </label>
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bystatus">
                                        <span class="checkmark"></span> Weekly
                                    </label>
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bystatus">
                                        <span class="checkmark"></span> Lifetime
                                    </label>
                                </div>
                            </div>	
                        </div>
                    </div>
                </div>
                <!-- /Plans Type -->

                <!-- Select Date -->
                <div class="accordion accordion-last" id="accordionMain2">
                    <div class="card-header-new" id="headingTwo">
                        <h6 class="filter-title">
                        <a href="javascript:void(0);" class="w-100 collapsed"  data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">
                            Created Date
                            <span class="float-end"><i class="fa-solid fa-chevron-down"></i></span>
                        </a> 
                        </h6>
                    </div>
                
                    <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo"  data-bs-parent="#accordionExample2">
                        <div class="card-body-chat">
                            <div class="input-block mb-3">
                                <div class="cal-icon">
                                    <input type="email" class="form-control datetimepicker" placeholder="From Date">
                                </div>
                            </div>
                            <div class="input-block mb-3">
                                <div class="cal-icon">
                                    <input type="email" class="form-control datetimepicker" placeholder="To Date">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /Select Date -->

                <div class="filter-buttons">
                    <button type="submit" class="d-inline-flex align-items-center justify-content-center btn w-100 btn-primary">
                        Apply
                    </button>
                    <button type="submit" class="d-inline-flex align-items-center justify-content-center btn w-100 btn-secondary">
                        Reset
                    </button>
                </div>
            </form>
            
        </div>
    </div>
</div>	
<!-- /Add Asset -->

<!-- View Companies Modal -->
<div class="modal custom-modal custom-lg-modal fade p-20" id="view_doamin" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header border-0">
                <div class="form-header modal-header-title text-start d-flex align-items-center mb-0">
                    <h4 class="mb-0">Domain Details</h4>
                    <span class="badge bg-info-light ms-3">Pending</span>
                </div>
                <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close">
                </button>
                
            </div>
            <div class="modal-body pb-0">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-field-item">
                            <div class="profile-picture company-detail-head">
                                <div class="upload-profile">
                                    <div class="profile-img company-profile-img">
                                        <img id="view-company-img" class="img-fluid me-0" src="{{URL::asset('/assets/img/companies/company-01.svg')}}" alt="profile-img">
                                    </div>
                                    <div class="add-profile">
                                        <h5>Hermann Groups</h5>
                                    </div>
                                </div>		
                                <div>
                                    <span class="badge bg-success d-inline-flex align-items-center me-2"><i class="fe fe-check me-1"></i>Approve</span>
                                    <span class="badge bg-danger d-inline-flex align-items-center"><i class="fe fe-x me-1"></i>Reject</span>								
                                </div>
                                
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="plane-basic-info">
                            <h5>Basic Info</h5>
                            <div class="row">
                                <div class="col-md-4 col-sm-6">
                                    <div class="basic-info-detail">
                                        <h6>Account URL</h6>
                                        <p>hru.example.com</p>
                                    </div>
                                    
                                </div>
                            </div>
                        </div>
                        
                    </div>
                    <div class="col-md-12">
                        <div class="plane-basic-info plane-detail">
                            <h5>Plan Details</h5>
                            <div class="row">
                                <div class="col-md-4 col-sm-6">
                                    <div class="basic-info-detail">
                                        <h6>Plan Name</h6>
                                        <p>Enterprise</p>
                                    </div>
                                    
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <div class="basic-info-detail">
                                        <h6>Plan Type</h6>
                                        <p>Yearly</p>
                                    </div>
                                    
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <div class="basic-info-detail">
                                        <h6>Price</h6>
                                        <p>$200</p>
                                    </div>
                                    
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <div class="basic-info-detail">
                                        <h6>Register Date</h6>
                                        <p>15 Jan 2024</p>
                                    </div>
                                    
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <div class="basic-info-detail">
                                        <h6>Expiring On</h6>
                                        <p>15 Jan 2025</p>
                                    </div>
                                    
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>
<!-- /View Companies Modal -->

<!-- Delete Items Modal -->
<div class="modal custom-modal fade modal-delete" id="delete_modal" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-body">
                <div class="form-header">
                    <div class="delete-modal-icon">
                        <span><i class="fe fe-check-circle"></i></span>
                    </div>
                    <h3>Are You Sure?</h3>
                    <p>You want delete domain</p>
                </div>
                <div class="modal-btn delete-action">
                    <div class="modal-footer justify-content-center p-0">
                        <button type="submit" data-bs-dismiss="modal" class="btn btn-primary paid-continue-btn me-2">Yes, Delete</button>
                        <button type="button" data-bs-dismiss="modal" class="btn btn-back cancel-btn">No, Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Delete Items Modal -->

@endif

@if (Route::is(['customers']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Customer</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['ledger']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Ledger</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['vendors']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Supplier</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['customer-details']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Customer Details</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-continue-btn">Delete</a>
                            </div>
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-cancel-btn">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['product-list']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Products / Services</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['category']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Category</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['units']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Units</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['states']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Countries</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['signature-invoice']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_discount" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 justify-content-center pb-0">
                    <div class="form-header modal-header-title text-center mb-0">
                        <h4 class="mb-2">Delete Product / Services</h4>
                        <p>Are you sure want to delete?</p>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-continue-btn">Delete</a>
                            </div>
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-cancel-btn">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['active-customers']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Customer</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['deactive-customers']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Customer</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['purchase-return']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Supplier</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['purchase-orders']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Purchases</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['quotations']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Quotations</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <a href="#" class="btn btn-primary paid-continue-btn">Delete</a>
                            </div>
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-cancel-btn">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['bank-account']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Account Details</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['cities']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete City</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['contact-messages']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Contact Messages</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['countries']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Countries</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['credit-notes']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Credit Notes</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['custom-filed']))
        <!-- Delete Items Modal -->
        <div class="modal custom-modal fade" id="delete_modal" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-md">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="form-header">
                            <h3>Delete Custom Fields</h3>
                            <p>Are you sure want to delete?</p>
                        </div>
                        <div class="modal-btn delete-action">
                            <div class="row">
                                <div class="col-6">
                                    <button type="reset" data-bs-dismiss="modal"
                                        class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                                </div>
                                <div class="col-6">
                                    <button type="submit" data-bs-dismiss="modal"
                                        class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /Delete Items Modal -->
@endif

@if (Route::is(['customer-ledger']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Customer</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['debit-notes']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Debit Notes</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['delete-account-request']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Account Request</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <a href="#" class="btn btn-primary paid-continue-btn">Delete</a>
                            </div>
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-cancel-btn">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['purchase-report']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Supplier</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['purchases']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Purchases</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['quotation-report']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Customer</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['rexpense-report']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Supplier</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['expenses']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Expenses</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['faq']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete FAQ</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['income-report']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Customer</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is([
        'invoices-cancelled',
        'invoices-draft',
        'invoices-overdue',
        'invoices-paid',
        'invoices-recurring',
        'invoices-refunded',
        'invoices-unpaid',
        'invoices',
    ]))
    <!-- Delete Paid Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Invoice</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <a href="javascript:void(0);" class="btn btn-primary paid-continue-btn">Delete</a>
                            </div>
                            <div class="col-6">
                                <a href="javascript:void(0);" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-cancel-btn">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Paid Modal -->
@endif

@if (Route::is(['stock-report']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Customer</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['low-stock-report']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Customer</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['payment-report']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Customer</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['payments']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Payments</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['profit-loss-list']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Customer</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['sales-report']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Supplier</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['sales-return-report']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Customer</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['tax-purchase']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Companies</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['tax-rates']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Account Details</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['tax-sales']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Companies</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['testimonials']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Testimonials</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-continue-btn">Delete</a>
                            </div>
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-cancel-btn">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['users']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Users</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <a href="#" class="btn btn-primary paid-continue-btn">Delete</a>
                            </div>
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-cancel-btn">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['purchase-transcation']))
		<!-- Add Asset -->
		<div class="toggle-sidebar">
			<div class="sidebar-layout-filter">
				<div class="sidebar-header">
					<h5>Filter</h5>
					<a href="#" class="sidebar-closes"><i class="fa-regular fa-circle-xmark"></i></a>
				</div>
				<div class="sidebar-body">
					<form action="#" autocomplete="off">
						<!-- Product -->
						<div class="accordion" id="accordionMain1">
							<div class="card-header-new" id="headingOne">
								<h6 class="filter-title">
									<a href="javascript:void(0);" class="w-100" data-bs-toggle="collapse"
										data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
										Customer
										<span class="float-end"><i class="fa-solid fa-chevron-down"></i></span>
									</a>
								</h6>
							</div>

							<div id="collapseOne" class="collapse show" aria-labelledby="headingOne"
								data-bs-parent="#accordionExample1">
								<div class="card-body-chat">
									<div class="row">
										<div class="col-md-12">
											<div id="checkBoxes1">
												<div class="selectBox-cont">
													<label class="custom_check w-100">
														<input type="checkbox" name="username">
														<span class="checkmark"></span> Orn LLC
													</label>
													<label class="custom_check w-100">
														<input type="checkbox" name="username">
														<span class="checkmark"></span> Accent Technology
													</label>
													<label class="custom_check w-100">
														<input type="checkbox" name="username">
														<span class="checkmark"></span> Express Advertising
													</label>
													<label class="custom_check w-100">
														<input type="checkbox" name="username">
														<span class="checkmark"></span> Sumo Soft Limited
													</label>
													<!-- View All -->
													<div class="view-content">
														<div class="viewall-One">
															<label class="custom_check w-100">
																<input type="checkbox" name="username">
																<span class="checkmark"></span> Repair Group Co
															</label>
															<label class="custom_check w-100">
																<input type="checkbox" name="username">
																<span class="checkmark"></span> Lexicon Technologies
															</label>
														</div>
														<div class="view-all">
															<a href="javascript:void(0);"
																class="viewall-button-One"><span class="me-2">View
																	All</span><span><i
																		class="fa fa-circle-chevron-down"></i></span></a>
														</div>
													</div>
													<!-- /View All -->
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<!-- /Product -->

						<!-- Product Code -->
						<div class="accordion" id="accordionMain4">
							<div class="card-header-new" id="headingFour">
								<h6 class="filter-title">
									<a href="javascript:void(0);" class="w-100 collapsed" data-bs-toggle="collapse"
										data-bs-target="#collapseFour" aria-expanded="true"
										aria-controls="collapseFour">
										Mode
										<span class="float-end"><i class="fa-solid fa-chevron-down"></i></span>
									</a>
								</h6>
							</div>

							<div id="collapseFour" class="collapse" aria-labelledby="headingFour"
								data-bs-parent="#accordionExample4">
								<div class="card-body-chat">
									<div id="checkBoxes3">
										<div class="selectBox-cont">
											<label class="custom_check w-100">
												<input type="checkbox" name="category">
												<span class="checkmark"></span> Cash
											</label>
											<label class="custom_check w-100">
												<input type="checkbox" name="category">
												<span class="checkmark"></span> UPI
											</label>
											<label class="custom_check w-100">
												<input type="checkbox" name="category">
												<span class="checkmark"></span> Cheque
											</label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<!-- /Product Code -->

						<!-- Unts -->
						<div class="accordion accordion-last" id="accordionMain3">
							<div class="card-header-new" id="headingThree">
								<h6 class="filter-title">
									<a href="javascript:void(0);" class="w-100 collapsed" data-bs-toggle="collapse"
										data-bs-target="#collapseThree" aria-expanded="true"
										aria-controls="collapseThree">
										Status
										<span class="float-end"><i class="fa-solid fa-chevron-down"></i></span>
									</a>
								</h6>
							</div>

							<div id="collapseThree" class="collapse" aria-labelledby="headingThree"
								data-bs-parent="#accordionExample3">
								<div class="card-body-chat">
									<div id="checkBoxes2">
										<div class="selectBox-cont">
											<label class="custom_check w-100">
												<input type="checkbox" name="bystatus">
												<span class="checkmark"></span> Paid
											</label>
											<label class="custom_check w-100">
												<input type="checkbox" name="bystatus">
												<span class="checkmark"></span> Pending
											</label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<!-- /Units -->

						<div class="filter-buttons">
							<button type="submit"
								class="d-inline-flex align-items-center justify-content-center btn w-100 btn-primary">
								Apply
							</button>
							<button type="submit"
								class="d-inline-flex align-items-center justify-content-center btn w-100 btn-secondary">
								Reset
							</button>
						</div>
					</form>

				</div>
			</div>
		</div>
		<!-- /Add Asset -->

		<!-- Add Stock in Modal -->
		<div class="modal custom-modal fade" id="stock_in" role="dialog">
			<div class="modal-dialog modal-dialog-centered modal-md">
				<div class="modal-content">
					<div class="modal-header border-0 pb-0">
						<div class="form-header modal-header-title text-start mb-0">
							<h4 class="mb-0">Add Stock in</h4>
						</div>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

						</button>
					</div>
					<div class="modal-body">
						<div class="row">
							<div class="col-lg-12 col-md-12">
								<div class="input-block mb-3">
									<label>Name</label>
									<input type="text" class="bg-white-smoke form-control" placeholder="SEO Service">
								</div>
							</div>
							<div class="col-lg-6 col-md-12">
								<div class="input-block mb-3">
									<label>Quantity</label>
									<input type="number" class="form-control" placeholder="0">
								</div>
							</div>
							<div class="col-lg-6 col-md-12">
								<div class="input-block mb-0">
									<label>Units</label>
									<select class="select">
										<option>Pieces</option>
										<option>Inches</option>
										<option>Kilograms</option>
										<option>Inches</option>
										<option>Box</option>
									</select>
								</div>
							</div>
							<div class="col-lg-12">
								<div class="input-block mb-0">
									<label>Notes</label>
									<textarea rows="3" cols="3" class="form-control"
										placeholder="Enter Notes"></textarea>
								</div>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<a href="#" data-bs-dismiss="modal" class="btn btn-back cancel-btn me-2">Cancel</a>
						<a href="#" data-bs-dismiss="modal" class="btn btn-primary paid-continue-btn">Add Quantity</a>
					</div>
				</div>
			</div>
		</div>
		<!-- /Add Stock in Modal -->

		<!-- Remove Stock Modal -->
		<div class="modal custom-modal fade" id="stock_out" role="dialog">
			<div class="modal-dialog modal-dialog-centered modal-md">
				<div class="modal-content">
					<div class="modal-header border-0 pb-0">
						<div class="form-header modal-header-title text-start mb-0">
							<h4 class="mb-0">Remove Stock</h4>
						</div>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

						</button>
					</div>
					<div class="modal-body">
						<div class="row">
							<div class="col-lg-12 col-md-12">
								<div class="input-block mb-3">
									<label>Name</label>
									<input type="text" class="bg-white-smoke form-control" placeholder="SEO Service">
								</div>
							</div>
							<div class="col-lg-6 col-md-12">
								<div class="input-block mb-3">
									<label>Quantity</label>
									<input type="number" class="form-control" placeholder="0">
								</div>
							</div>
							<div class="col-lg-6 col-md-12">
								<div class="input-block mb-0">
									<label>Units</label>
									<select class="select">
										<option>Pieces</option>
										<option>Inches</option>
										<option>Kilograms</option>
										<option>Inches</option>
										<option>Box</option>
									</select>
								</div>
							</div>
							<div class="col-lg-12">
								<div class="input-block mb-0">
									<label>Notes</label>
									<textarea rows="3" cols="3" class="form-control"
										placeholder="Enter Notes"></textarea>
								</div>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<a href="#" data-bs-dismiss="modal" class="btn btn-back cancel-btn me-2">Cancel</a>
						<a href="#" data-bs-dismiss="modal" class="btn btn-primary paid-continue-btn">Remove
							Quantity</a>
					</div>
				</div>
			</div>
		</div>
		<!-- /Remove Stock Modal -->

		<!-- Edit Stock in Modal -->
		<div class="modal custom-modal fade" id="edit_inventory" role="dialog">
			<div class="modal-dialog modal-dialog-centered modal-lg">
				<div class="modal-content">
					<div class="modal-header border-0 pb-0">
						<div class="form-header modal-header-title text-start mb-0">
							<h4 class="mb-0">Edit Inventory</h4>
						</div>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

						</button>
					</div>
					<div class="modal-body">
						<div class="row">
							<div class="col-lg-6 col-md-12">
								<div class="input-block mb-3">
									<label>Name</label>
									<input type="text" class="form-control" value="Lorem ipsum dolor sit">
								</div>
							</div>
							<div class="col-lg-6 col-md-12">
								<div class="input-block mb-3">
									<label>Code</label>
									<input type="text" class="form-control" value="P125389">
								</div>
							</div>
							<div class="col-lg-6 col-md-12">
								<div class="input-block mb-3">
									<label>Units</label>
									<input type="text" class="form-control" value="Box">
								</div>
							</div>
							<div class="col-lg-6 col-md-12">
								<div class="input-block mb-3">
									<label>Quantity</label>
									<input type="text" class="form-control" value="3">
								</div>
							</div>
							<div class="col-lg-6 col-md-12">
								<div class="input-block mb-3">
									<label>Selling Price</label>
									<input type="text" class="form-control" value="$155.00">
								</div>
							</div>
							<div class="col-lg-6 col-md-12">
								<div class="input-block mb-3">
									<label>Purchase Price</label>
									<input type="text" class="form-control" value="$150.00">
								</div>
							</div>
							<div class="col-lg-6 col-md-12">
								<div class="input-block mb-0">
									<label>Status</label>
									<input type="text" class="form-control" value="Stock in">
								</div>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<a href="#" data-bs-dismiss="modal" class="btn btn-back cancel-btn me-2">Cancel</a>
						<a href="#" data-bs-dismiss="modal" class="btn btn-primary paid-continue-btn">Update</a>
					</div>
				</div>
			</div>
		</div>
		<!-- /Edit Stock in Modal -->

		<!-- Delete Items Modal -->
		<div class="modal custom-modal fade" id="delete_modal" role="dialog">
			<div class="modal-dialog modal-dialog-centered modal-md">
				<div class="modal-content">
					<div class="modal-body">
						<div class="form-header">
							<h3>Delete Purchase Transaction</h3>
							<p>Are you sure want to delete?</p>
						</div>
						<div class="modal-btn delete-action">
							<div class="row">
								<div class="col-6">
									<button type="reset" data-bs-dismiss="modal" class="w-100 btn btn-primary paid-continue-btn">Delete</button>
								</div>
								<div class="col-6">
									<button type="submit" data-bs-dismiss="modal" class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- /Delete Items Modal -->
@endif

@if (Route::is(['inventory']))
    <!-- Remove Stock Modal -->
    <div class="modal custom-modal fade" id="stock_out" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0">Remove Stock</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-lg-12 col-md-12">
                                <div class="input-block mb-3">
                                    <label>Name</label>
                                    <input type="text" class="bg-white-smoke form-control"
                                        placeholder="SEO Service">
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <div class="input-block mb-3">
                                    <label>Quantity</label>
                                    <input type="number" class="form-control" placeholder="0">
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <div class="input-block mb-0">
                                    <label>Units</label>
                                    <select class="select">
                                        <option>Pieces</option>
                                        <option>Inches</option>
                                        <option>Kilograms</option>
                                        <option>Inches</option>
                                        <option>Box</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="input-block mb-0">
                                    <label>Notes</label>
                                    <textarea rows="3" cols="3" class="form-control" placeholder="Enter Notes"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Cancel</button>
                        <button type="submit" data-bs-dismiss="modal"
                            class="btn btn-primary paid-continue-btn">Remove Quantity</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Remove Stock Modal -->

    <!-- Delete Stock Modal -->
    <div class="modal custom-modal fade" id="delete_stock" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Inventory</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <a href="#" class="btn btn-primary paid-continue-btn">Delete</a>
                            </div>
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-cancel-btn">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Stock Modal -->
@endif

@if (Route::is(['inventory-history']))
    <!-- Remove Stock Modal -->
    <div class="modal custom-modal fade" id="stock_out" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0">Remove Stock</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-lg-12 col-md-12">
                                <div class="input-block mb-3">
                                    <label>Name</label>
                                    <input type="text" class="bg-white-smoke form-control"
                                        placeholder="SEO Service">
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <div class="input-block mb-3">
                                    <label>Quantity</label>
                                    <input type="number" class="form-control" placeholder="0">
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <div class="input-block mb-0">
                                    <label>Units</label>
                                    <select class="select">
                                        <option>Pieces</option>
                                        <option>Inches</option>
                                        <option>Kilograms</option>
                                        <option>Inches</option>
                                        <option>Box</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="input-block mb-0">
                                    <label>Notes</label>
                                    <textarea rows="3" cols="3" class="form-control" placeholder="Enter Notes"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Cancel</button>
                        <button type="submit" data-bs-dismiss="modal"
                            class="btn btn-primary paid-continue-btn">Remove Quantity</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Remove Stock Modal -->

    <!-- Delete Stock Modal -->
    <div class="modal custom-modal fade" id="delete_stock" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Inventory History</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <a href="#" class="btn btn-primary paid-continue-btn">Delete</a>
                            </div>
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-cancel-btn">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Stock Modal -->
@endif

@if (Route::is(['signature-list']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade signature-delete-modal" id="warning_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <div class="mb-2"><i class="fe fe-alert-circle text-warning"></i></div>
                        <h3>Are you Sure?</h3>
                        <p>You won’t be able to revert this!</p>
                    </div>
                    <div class="modal-btn delete-action text-center modal-footer pb-0 justify-content-center">
                        <button type="reset" data-bs-toggle="modal" data-bs-target="#delete_modal"
                            class="btn btn-primary me-2">Yes, delete it!</button>
                        <button type="reset" data-bs-dismiss="modal" class="btn btn-back">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->

    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade signature-delete-modal" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <div class="mb-2"><i class="fe fe-trash-2"></i></div>
                        <h3>Signature Deleted</h3>
                        <p>The signature deleted successfully!</p>
                    </div>
                    <div class="modal-btn delete-action text-center">
                        <button type="reset" data-bs-dismiss="modal" class="btn btn-primary">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['add-credit-notes']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_discount" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 justify-content-center pb-0">
                    <div class="form-header modal-header-title text-center mb-0">
                        <h4 class="mb-2">Delete Product / Services</h4>
                        <p>Are you sure want to delete?</p>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-continue-btn">Delete</a>
                            </div>
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-cancel-btn">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['delivery-challans']))
    <!-- View Modal -->
    <div class="modal custom-modal fade" id="view_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content view-delivery-challans">
                <div class="modal-header border-0 pb-0 justify-content-end d-flex align-items-center">
                    <div class="file-link mb-0 ">
                        <button class="download_btn download-link">
                            <i class="fa fa-cloud-download me-1" aria-hidden="true"></i> <span>Download</span>
                        </button>
                        <a href="javascript:window.print()" class="print-link">
                            <i class="fa fa-print me-1" aria-hidden="true"></i> <span class="">Print</span>
                        </a>
                    </div>
                </div>
                <div class="modal-body invoice-one">
                    <div class="invoice-wrapper download_section">
                        <div class="inv-content">
                            <div class="invoice-header p-0">
                                <div class="inv-header-left">
                                    <h4>DELIVERY CHALLAN</h4>
                                    <h5>Dreamguys Technologies Pvt Ltd</h5>
                                    <h6>Mobile 8072687299</h6>
                                </div>
                                <div class="inv-header-right">
                                    <h3 class="mb-3">Delivery Challan</h3>
                                    <h6 class="mb-1">Delivery Challan : <span> #005</span></h6>
                                    <h6 class="mb-1">Delivery Challan Date :<span> 07-10-2023</span></h6>
                                    <p>Due Date : <span>07-12-2023</span></p>
                                </div>

                            </div>
                            <span class="line my-4"></span>
                            <div class="patient-infos">
                                <div class="row">
                                    <div class="col-sm-12 col-md-6 col-lg-4">
                                        <div class=" patient-detailed">
                                            <div class="bill-add">
                                                Customer Details :
                                            </div>
                                            <div class="customer-name">
                                                <h4 class="mb-1">Test Customer</h4>
                                                <h4 class="mb-1">Test Company</h4>
                                                <p><span>GSTIN : ACWR000054321</span></p>
                                                <p><span>Phone Number : +91 1234567890</span></p>
                                                <p><span>Email : delive@example.com</span></p>
                                            </div>

                                        </div>
                                    </div>
                                    <div class="col-sm-12 col-md-6 col-lg-4">
                                        <div class=" patient-detailed">
                                            <div class="bill-add">
                                                Billing Address :
                                            </div>
                                            <div class="add-details">
                                                Walter Roberson <br> 299 Star Trek Drive, Panama City,<br> Florida,
                                                32405,<br> USA
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 col-md-6 col-lg-4">
                                        <div class=" patient-detailed">
                                            <div class="bill-add">
                                                Shipping Address :
                                            </div>
                                            <div class="add-details">
                                                Walter Roberson <br> 299 Star Trek Drive, Panama City,<br> Florida,
                                                32405,<br> USA
                                            </div>

                                        </div>
                                    </div>
                                </div>
                                <div class="reference">
                                    <h4>Reference</h4>
                                    <p>Keep the Delivery Challan for reference in the future. Now it's simple to create
                                        an invoice, download it</p>
                                    <hr>
                                </div>
                            </div>
                            <div class="invoice-table">
                                <div class="table-responsive">
                                    <table>
                                        <thead>
                                            <tr class="ecommercetable">
                                                <th class="table_width_1">#</th>
                                                <th>Item</th>
                                                <th class="text-end">Quantity</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>1</td>
                                                <td class="text-start">Accounting Software Maintainence</td>
                                                <td class="text-end">3</td>
                                            </tr>
                                            <tr>
                                                <td>2</td>
                                                <td class="text-start">Man Power Support</td>
                                                <td class="text-end">1</td>
                                            </tr>
                                            <tr>
                                                <td>3</td>
                                                <td class="text-start">Transportation Fee</td>
                                                <td class="text-end">2</td>
                                            </tr>
                                            <tr>
                                                <td>4</td>
                                                <td class="text-start">Spars Replacement Charges</td>
                                                <td class="text-end">5</td>
                                            </tr>
                                            <tr>
                                                <td>5</td>
                                                <td class="text-start">Materials Handling</td>
                                                <td class="text-end">1</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <h5 class="text-end">Total Quantity : 12</h5>
                                </div>
                            </div>

                            <div class="authorization text-end">
                                <h6>For YOUR BUSINESS NAME</h6>
                                <img src="{{ URL::asset('/assets/img/signature.png')}}" class="my-3" alt="signature-img">
                                <h6>Authorized Signatory</h6>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <div class="terms-condition p-0">
                                        <span>Terms & Conditions:</span>
                                        <ol>
                                            <li>This is a GST based invoice bill, Which is applicable for TDS Deduction
                                            </li>
                                            <li>We are not the manufactures, company will stand for warranty as per
                                                their terms and conditions.</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="text-center powered">
                            <h6 class="mt-4 mb-3">Powered By</h6>
                            <a href="#">
                                <img class="logo-lightmode" src="{{ URL::asset('/assets/img/logos.png')}}" alt="Logo">
                                <img class="logo-darkmode" src="{{ URL::asset('/assets/img/logos.png')}}" alt="Logo">
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /View Modal -->

    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Delivery Challans</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <a href="#" class="btn btn-primary paid-continue-btn">Delete</a>
                            </div>
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-cancel-btn">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['add-delivery-challans']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_discount" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Product / Services</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-continue-btn">Delete</a>
                            </div>
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-cancel-btn">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['add-invoice']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_discount" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 justify-content-center pb-0">
                    <div class="form-header modal-header-title text-center mb-0">
                        <h4 class="mb-2">Delete Product / Services</h4>
                        <p>Are you sure want to delete?</p>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-continue-btn">Delete</a>
                            </div>
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-cancel-btn">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['add-purchase-return']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_discount" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Product / Services</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <a href="#" class="btn btn-primary paid-continue-btn">Delete</a>
                            </div>
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-cancel-btn">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['add-purchase-order']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_discount" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Product / Services</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <a href="#" class="btn btn-primary paid-continue-btn">Delete</a>
                            </div>
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-cancel-btn">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif
@if (Route::is(['add-purchases']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_discount" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Product / Services</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <a href="#" class="btn btn-primary paid-continue-btn">Delete</a>
                            </div>
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-cancel-btn">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['add-quotations']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_discount" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Products</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-continue-btn">Delete</a>
                            </div>
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-cancel-btn">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['categories']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="blog-categories" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 ">Add Category</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-lg-4 col-md-12">
                                    <div class="input-block mb-3">
                                        <label>Category Name<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control"
                                            placeholder="Enter Category Name">
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-12">
                                    <div class="input-block mb-3">
                                        <label>Slug<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" placeholder="Enter Slug">
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-12">
                                    <div class="input-block mb-3">
                                        <label>Description (Meta Tag)<span class="text-danger">*</span></label>
                                        <input type="text" data-role="tagsinput"
                                            class="input-tags form-control" placeholder="Enter Tags"
                                            name="services">
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-12">
                                    <div class="input-block">
                                        <label>Keywords (Meta Tag)<span class="text-danger">*</span></label>
                                        <input type="text" data-role="tagsinput"
                                            class="input-tags form-control" placeholder="Enter Tags"
                                            name="services">
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-12">
                                    <div class="input-block">
                                        <label>Order<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" placeholder="Enter Order">
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-12">
                                    <div class="input-block status-grid">
                                        <label>Status</label>
                                        <div class="status-toggle">
                                            <input id="rating_9" class="check" type="checkbox" checked>
                                            <label for="rating_9" class="checktoggle checkbox-bg "></label>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Cancel</button>
                        <button type="submit" data-bs-dismiss="modal"
                            class="btn btn-primary paid-continue-btn">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade contentmodal" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content doctor-profile">
                <div class="modal-header pb-0 border-bottom-0  justify-content-end">
                    <button type="button" class="close-btn" data-bs-dismiss="modal" aria-label="Close"><i
                            class="feather-x-circle"></i></button>
                </div>
                <div class="modal-body">
                    <div class="delete-wrap text-center">
                        <div class="del-icon"><i class="feather-x-circle"></i></div>
                        <h2>Sure you want to delete</h2>
                        <div class="submit-section">
                            <a href="javascript:void(0);" data-bs-dismiss="modal"
                                class="btn btn-success me-2">Yes</a>
                            <a href="#" class="btn btn-danger" data-bs-dismiss="modal">No</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Modal -->
@endif

@if (Route::is(['edit-credit-notes']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_discount" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 justify-content-center pb-0">
                    <div class="form-header modal-header-title text-center mb-0">
                        <h4 class="mb-2">Delete Product / Services</h4>
                        <p>Are you sure want to delete?</p>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-continue-btn">Delete</a>
                            </div>
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-cancel-btn">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['edit-delivery-challans']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_discount" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 justify-content-center pb-0">
                    <div class="form-header modal-header-title text-center mb-0">
                        <h4 class="mb-2">Delete Product / Services</h4>
                        <p>Are you sure want to delete?</p>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-continue-btn">Delete</a>
                            </div>
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-cancel-btn">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['edit-invoice']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_discount" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 justify-content-center pb-0">
                    <div class="form-header modal-header-title text-center mb-0">
                        <h4 class="mb-2">Delete Product / Services</h4>
                        <p>Are you sure want to delete?</p>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-continue-btn">Delete</a>
                            </div>
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-cancel-btn">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['edit-purchase-return']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_discount" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Product / Services</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <a href="#" class="btn btn-primary paid-continue-btn">Delete</a>
                            </div>
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-cancel-btn">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['edit-purchases-order']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_discount" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 justify-content-center pb-0">
                    <div class="form-header modal-header-title text-center mb-0">
                        <h4 class="mb-2">Delete Product / Services</h4>
                        <p>Are you sure want to delete?</p>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-continue-btn">Delete</a>
                            </div>
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-cancel-btn">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['edit-purchases']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_discount" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 justify-content-center pb-0">
                    <div class="form-header modal-header-title text-center mb-0">
                        <h4 class="mb-2">Delete Product / Services</h4>
                        <p>Are you sure want to delete?</p>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-continue-btn">Delete</a>
                            </div>
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-cancel-btn">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['edit-quotations']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_discount" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 justify-content-center pb-0">
                    <div class="form-header modal-header-title text-center mb-0">
                        <h4 class="mb-2">Delete Product / Services</h4>
                        <p>Are you sure want to delete?</p>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-continue-btn">Delete</a>
                            </div>
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-cancel-btn">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['email-settings']))
    <!-- PHP Mail Modal -->
    <div class="modal custom-modal fade" id="bank_details" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title php-mail-modal text-start mb-0">
                        <h4 class="mb-0">PHP Mail</h4>
                        <div class="status-toggle">
                            <input id="rating_3" class="check" type="checkbox" checked="">
                            <label for="rating_3" class="checktoggle checkbox-bg">checkbox</label>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Email From Address</label>
                                <input type="text" class="form-control" placeholder="demo2">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Email Password</label>
                                <input type="password" class="form-control" placeholder="*******">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Email From Name</label>
                                <input type="text" class="form-control" placeholder="John Smith">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" data-bs-dismiss="modal" class="btn btn-back cancel-btn me-2">Cancel</a>
                    <a href="#" data-bs-dismiss="modal"
                        class="btn btn-primary paid-continue-btn">Save</a>
                </div>
            </div>
        </div>
    </div>
    <!-- /PHP Mail Modal -->
@endif

@if (Route::is(['form-wizard']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="save_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Confirm Save Changes</h3>
                        <p>Are you sure want to Confirm Save Changes?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="reset" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-continue-btn">Save Changes</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" data-bs-dismiss="modal"
                                    class="w-100 btn btn-primary paid-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['inventory-list']))
    <!-- Remove Stock Modal -->
    <div class="modal custom-modal fade" id="stock_out" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0">Remove Stock</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Name</label>
                                <input type="text" class="bg-white-smoke form-control"
                                    placeholder="SEO Service">
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-12">
                            <div class="input-block mb-3">
                                <label>Quantity</label>
                                <input type="number" class="form-control" placeholder="0">
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-12">
                            <div class="input-block mb-0">
                                <label>Units</label>
                                <select class="select">
                                    <option>Pieces</option>
                                    <option>Inches</option>
                                    <option>Kilograms</option>
                                    <option>Inches</option>
                                    <option>Box</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="input-block mb-0">
                                <label>Notes</label>
                                <textarea rows="3" cols="3" class="form-control" placeholder="Enter Notes"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" data-bs-dismiss="modal" class="btn btn-back cancel-btn me-2">Cancel</a>
                    <a href="#" data-bs-dismiss="modal" class="btn btn-primary paid-continue-btn">Remove
                        Quantity</a>
                </div>
            </div>
        </div>
    </div>
    <!-- /Remove Stock Modal -->

    <!-- Delete Stock Modal -->
    <div class="modal custom-modal fade" id="delete_stock" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Inventory</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <a href="#" class="btn btn-primary paid-continue-btn">Delete</a>
                            </div>
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-cancel-btn">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Stock Modal -->
@endif

@if (Route::is(['invoice-details']))
    <!-- Pay Now Modal -->
    <div class="modal custom-modal fade pay-modal" id="paynow_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="modal-title">Invoice <span>#INV 00001</span></h4>
                        <h5><span>Due Date : </span> 03 Jun 2023</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                    </button>
                </div>
                <form action="#">
                    <div class="modal-body ">
                        <div class="row">
                            <div class="payment-heading">
                                <h5>Select a Payment Method</h5>
                            </div>
                            <div class="input-block mb-3 paynow-tab">
                                <ul class="nav nav-pills d-flex row" id="pills-tab" role="tablist">
                                    <li class="nav-item col-sm-4" role="presentation">
                                        <button class="nav-link active cash" id="pills-home-tab"
                                            data-bs-toggle="pill" data-bs-target="#pills-home" type="button"
                                            role="tab" aria-controls="pills-home"
                                            aria-selected="true">Cash<i class="fe fe-dollar-sign"></i></button>
                                    </li>
                                    <li class="nav-item col-sm-4" role="presentation">
                                        <button class="nav-link cheque" id="pills-profile-tab"
                                            data-bs-toggle="pill" data-bs-target="#pills-profile"
                                            type="button" role="tab" aria-controls="pills-profile"
                                            aria-selected="false">Cheque<i class="fe fe-file-text"></i></button>
                                    </li>
                                    <li class="nav-item col-sm-4" role="presentation">
                                        <button class="nav-link cheque" data-bs-toggle="pill"
                                            data-bs-target="#pills-profile" type="button" role="tab"
                                            aria-controls="pills-profile" aria-selected="false">CreditCard<i
                                                class="fe fe-file-text"></i></button>
                                    </li>
                                </ul>
                            </div>
                            <div class="tab-content pt-0" id="pills-tabContent">
                                <div class="tab-pane fade show active" id="pills-home" role="tabpanel"
                                    aria-labelledby="pills-home-tab">
                                    <div class="input-block mb-3">
                                        <label>Amount <span class="text-danger"> *</span></label>
                                        <input type="text" class="form-control" placeholder="Enter Amount">
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="pills-profile" role="tabpanel"
                                    aria-labelledby="pills-profile-tab">
                                    <div class="input-block mb-3">
                                        <label>Amount <span class="text-danger"> *</span></label>
                                        <input type="text" class="form-control" placeholder="Enter Amount">
                                    </div>
                                    <div class="input-block mb-3">
                                        <label>Cheque Number <span class="text-danger"> *</span></label>
                                        <input type="text" class="form-control"
                                            placeholder="Enter Cheque Number">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Cancel</button>
                        <button type="submit" data-bs-dismiss="modal"
                            class="btn btn-primary paid-continue-btn">pay Now</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Pay Now Modal -->
@endif

@if (Route::is(['invoices']))
    <!-- View Modal -->
    <div class="modal custom-modal fade" id="view_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content view-delivery-challans">
                <div class="modal-header border-0 pb-0 justify-content-end d-flex align-items-center">
                    <div class="file-link mb-0 ">
                        <button class="download_btn download-link">
                            <i class="fa fa-cloud-download me-1" aria-hidden="true"></i> <span>Download</span>
                        </button>
                        <a href="javascript:window.print()" class="print-link">
                            <i class="fa fa-print me-1" aria-hidden="true"></i> <span
                                class="">Print</span>
                        </a>
                    </div>
                </div>
                <div class="modal-body invoice-one">
                    <div class="invoice-wrapper download_section">
                        <div class="inv-content">
                            <div class="invoice-header p-0">
                                <div class="inv-header-left">
                                    <h4>DELIVERY CHALLAN</h4>
                                    <h5>Dreamguys Technologies Pvt Ltd</h5>
                                    <h6>Mobile 8072687299</h6>
                                </div>
                                <div class="inv-header-right">
                                    <h3 class="mb-3">Delivery Challan</h3>
                                    <h6 class="mb-1">Invoice # : <span> INV-1</span></h6>
                                    <h6 class="mb-1">Invoice Date :<span> 07-10-2023</span></h6>
                                    <p>Due Date : <span>07-12-2023</span></p>
                                </div>

                            </div>
                            <span class="line my-4"></span>
                            <div class="patient-infos">
                                <div class="row">
                                    <div class="col-sm-4">
                                        <div class=" patient-detailed">
                                            <div class="bill-add">
                                                Customer Details :
                                            </div>
                                            <div class="customer-name">
                                                <h4 class="mb-1">Test Customer</h4>
                                                <h4 class="mb-1">Test Company</h4>
                                                <p><span>GSTIN : ACWR000054321</span></p>
                                                <p><span>Phone Number : +91 1234567890</span></p>
                                                <p><span>Email : delive@example.com</span></p>
                                            </div>

                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class=" patient-detailed">
                                            <div class="bill-add">
                                                Billing Address :
                                            </div>
                                            <div class="add-details">
                                                Walter Roberson <br> 299 Star Trek Drive, Panama City,<br> Florida,
                                                32405,<br> USA
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class=" patient-detailed">
                                            <div class="bill-add">
                                                Shipping Address :
                                            </div>
                                            <div class="add-details">
                                                Walter Roberson <br> 299 Star Trek Drive, Panama City,<br> Florida,
                                                32405,<br> USA
                                            </div>

                                        </div>
                                    </div>
                                </div>
                                <div class="reference">
                                    <h4>Reference</h4>
                                    <p>Keep the Delivery Challan for reference in the future. Now it's simple to create
                                        an invoice, download it</p>
                                    <hr>
                                </div>
                            </div>
                            <div class="invoice-table">
                                <div class="table-responsive">
                                    <table>
                                        <thead>
                                            <tr class="ecommercetable">
                                                <th class="table_width_1">#</th>
                                                <th>Item</th>
                                                <th class="text-end">Quantity</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>1</td>
                                                <td class="text-start">Accounting Software Maintainence</td>
                                                <td class="text-end">3</td>
                                            </tr>
                                            <tr>
                                                <td>2</td>
                                                <td class="text-start">Man Power Support</td>
                                                <td class="text-end">1</td>
                                            </tr>
                                            <tr>
                                                <td>3</td>
                                                <td class="text-start">Transportation Fee</td>
                                                <td class="text-end">2</td>
                                            </tr>
                                            <tr>
                                                <td>4</td>
                                                <td class="text-start">Spars Replacement Charges</td>
                                                <td class="text-end">5</td>
                                            </tr>
                                            <tr>
                                                <td>5</td>
                                                <td class="text-start">Materials Handling</td>
                                                <td class="text-end">1</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <h5 class="text-end">Total Quantity : 12</h5>
                                </div>
                            </div>

                            <div class="authorization text-end">
                                <h6>For YOUR BUSINESS NAME</h6>
                                <img src="{{ URL::asset('/assets/img/signature.png')}}" class="my-3" alt="signature-img">
                                <h6>Authorized Signatory</h6>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <div class="terms-condition p-0">
                                        <span>Terms & Conditions:</span>
                                        <ol>
                                            <li>This is a GST based invoice bill, Which is applicable for TDS Deduction
                                            </li>
                                            <li>We are not the manufactures, company will stand for warranty as per
                                                their terms and conditions.</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="text-center powered">
                            <h6 class="mt-4 mb-3">Powered By</h6>
                            <a href="#">
                                <img class="logo-lightmode" src="{{ URL::asset('/assets/img/logos.png')}}" alt="Logo">
                                <img class="logo-darkmode" src="{{ URL::asset('/assets/img/logos.png')}}" alt="Logo">
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /View Modal -->
@endif

@if (Route::is(['notifications']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade signature-delete-modal" id="notification-delete" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <div class="mb-2"><i class="fe fe-trash-2"></i></div>
                        <h3>Deleted</h3>
                        <p>This Notification Successfully Deleted!</p>
                    </div>
                    <div class="modal-btn delete-action text-center">
                        <button type="reset" data-bs-dismiss="modal" class="btn btn-primary">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is(['pages']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="page_delete" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Pages list</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <a href="#" class="btn btn-primary paid-continue-btn">Delete</a>
                            </div>
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-cancel-btn">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->
@endif

@if (Route::is([
        'recurring-cancelled',
        'recurring-draft',
        'recurring-invoices',
        'recurring-overdue',
        'recurring-paid',
        'recurring-pending',
        'recurring',
    ]))
    <!-- Delete Paid Modal -->
    <div class="modal custom-modal fade" id="delete_paid" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Invoice Paid</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <a href="javascript:void(0);" class="btn btn-primary paid-continue-btn">Delete</a>
                            </div>
                            <div class="col-6">
                                <a href="javascript:void(0);" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-cancel-btn">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Paid Modal -->
@endif

@if (Route::is(['pay-online']))
    <!-- Success Items Modal -->
    <div class="modal custom-modal fade signature-success-modal" id="success_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <div class="mb-2"><i class="feather-check-circle"></i></div>
                        <h3>Payment Successful </h3>
                        <p>You will receive an email on the payment you have made.</p>
                    </div>
                    <div class="modal-btn delete-action text-center">
                        <button type="reset" data-bs-dismiss="modal" class="btn btn-primary">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Success Items Modal -->
@endif

@if (Route::is(['purchase-details']))
    <!-- Pay Now Modal -->
    <div class="modal custom-modal fade pay-modal" id="paynow_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="modal-title">Invoice <span>#INV 00001</span></h4>
                        <h5><span>Due Date : </span> 03 Jun 2023</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                    </button>
                </div>
                <div class="modal-body ">
                    <div class="row">
                        <div class="payment-heading">
                            <h5>Select a Payment Method</h5>
                        </div>
                        <div class="input-block mb-3 paynow-tab">
                            <ul class="nav nav-pills d-flex row" id="pills-tab" role="tablist">
                                <li class="nav-item col-sm-4" role="presentation">
                                    <button class="nav-link active cash" id="pills-home-tab" data-bs-toggle="pill"
                                        data-bs-target="#pills-home" type="button" role="tab"
                                        aria-controls="pills-home" aria-selected="true">Cash<i
                                            class="fe fe-dollar-sign"></i></button>
                                </li>
                                <li class="nav-item col-sm-4" role="presentation">
                                    <button class="nav-link cheque" id="pills-profile-tab" data-bs-toggle="pill"
                                        data-bs-target="#pills-profile" type="button" role="tab"
                                        aria-controls="pills-profile" aria-selected="false">Cheque<i
                                            class="fe fe-file-text"></i></button>
                                </li>
                                <li class="nav-item col-sm-4" role="presentation">
                                    <button class="nav-link cheque" data-bs-toggle="pill"
                                        data-bs-target="#pills-profile" type="button" role="tab"
                                        aria-controls="pills-profile" aria-selected="false">CreditCard<i
                                            class="fe fe-file-text"></i></button>
                                </li>
                            </ul>
                        </div>
                        <div class="tab-content pt-0" id="pills-tabContent">
                            <div class="tab-pane fade show active" id="pills-home" role="tabpanel"
                                aria-labelledby="pills-home-tab">
                                <div class="input-block mb-3">
                                    <label>Amount <span class="text-danger"> *</span></label>
                                    <input type="text" class="form-control" placeholder="Enter Amount">
                                </div>
                            </div>
                            <div class="tab-pane fade" id="pills-profile" role="tabpanel"
                                aria-labelledby="pills-profile-tab">
                                <div class="input-block mb-3">
                                    <label>Amount <span class="text-danger"> *</span></label>
                                    <input type="text" class="form-control" placeholder="Enter Amount">
                                </div>
                                <div class="input-block mb-3">
                                    <label>Cheque Number <span class="text-danger"> *</span></label>
                                    <input type="text" class="form-control" placeholder="Enter Cheque Number">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" data-bs-dismiss="modal" class="btn btn-back cancel-btn me-2">Cancel</a>
                    <a href="#" data-bs-dismiss="modal" class="btn btn-primary paid-continue-btn">pay
                        Now</a>
                </div>
            </div>
        </div>
    </div>
    <!-- /Pay Now Modal -->
@endif

@if(Route::is(['plans-list']))
<!-- Add Asset -->
<div class="toggle-sidebar">
    <div class="sidebar-layout-filter">
        <div class="sidebar-header">
            <h5>Filter</h5>
            <a href="#" class="sidebar-closes"><i class="fa-regular fa-circle-xmark"></i></a>
        </div>
        <div class="sidebar-body">
            <form action="#" autocomplete="off">

                <!-- By Plane -->
                <div class="accordion" id="accordionMain3">
                    <div class="card-header-new" id="headingThree">
                        <h6 class="filter-title">
                            <a href="javascript:void(0);" class="w-100 collapsed" data-bs-toggle="collapse"
                                data-bs-target="#collapseThree" aria-expanded="true"
                                aria-controls="collapseThree">
                                Plans
                                <span class="float-end"><i class="fa-solid fa-chevron-down"></i></span>
                            </a>
                        </h6>
                    </div>

                    <div id="collapseThree" class="collapse" aria-labelledby="headingThree"
                        data-bs-parent="#accordionExample3">
                        <div class="card-body-chat">
                            <div id="checkBoxes2">
                                <div class="selectBox-cont">
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bystatus">
                                        <span class="checkmark"></span> All Plans
                                    </label>
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bystatus">
                                        <span class="checkmark"></span> Advanced
                                    </label>
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bystatus">
                                        <span class="checkmark"></span> Basic
                                    </label>
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bystatus">
                                        <span class="checkmark"></span> Enterprise
                                    </label>
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bystatus">
                                        <span class="checkmark"></span>Premium
                                    </label>
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bystatus">
                                        <span class="checkmark"></span>Free
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /By Plane -->

                <!-- By Plane -->
                <div class="accordion" id="accordionMain4">
                    <div class="card-header-new" id="headingFour">
                        <h6 class="filter-title">
                            <a href="javascript:void(0);" class="w-100 collapsed" data-bs-toggle="collapse"
                                data-bs-target="#collapseFour" aria-expanded="true"
                                aria-controls="collapseFour">
                                Plans Type
                                <span class="float-end"><i class="fa-solid fa-chevron-down"></i></span>
                            </a>
                        </h6>
                    </div>

                    <div id="collapseFour" class="collapse" aria-labelledby="headingFour"
                        data-bs-parent="#accordionExample4">
                        <div class="card-body-chat">
                            <div id="checkBoxes3">
                                <div class="selectBox-cont">
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bystatus">
                                        <span class="checkmark"></span> All Plan Type
                                    </label>
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bystatus">
                                        <span class="checkmark"></span> Monthly
                                    </label>
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bystatus">
                                        <span class="checkmark"></span> Weekly
                                    </label>
                                    <label class="custom_check w-100">
                                        <input type="checkbox" name="bystatus">
                                        <span class="checkmark"></span>Lifetime
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /By Plane -->

                <!-- Select Date -->
                <div class="accordion accordion-last" id="accordionMain2">
                    <div class="card-header-new" id="headingTwo">
                        <h6 class="filter-title">
                            <a href="javascript:void(0);" class="w-100 collapsed" data-bs-toggle="collapse"
                                data-bs-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">
                                Created Date
                                <span class="float-end"><i class="fa-solid fa-chevron-down"></i></span>
                            </a>
                        </h6>
                    </div>

                    <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo"
                        data-bs-parent="#accordionExample2">
                        <div class="card-body-chat">
                            <div class="input-block mb-3">
                                <label class="form-control-label">From</label>
                                <div class="cal-icon">
                                    <input type="email" class="form-control datetimepicker"
                                        placeholder="DD-MM-YYYY">
                                </div>
                            </div>
                            <div class="input-block mb-3">
                                <label class="form-control-label">To</label>
                                <div class="cal-icon">
                                    <input type="email" class="form-control datetimepicker"
                                        placeholder="DD-MM-YYYY">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /Select Date -->

                

                <div class="filter-buttons">
                    <button type="submit" class="d-inline-flex align-items-center justify-content-center btn w-100 btn-primary">
                        Apply
                    </button>
                    <button type="submit" class="d-inline-flex align-items-center justify-content-center btn w-100 btn-secondary">
                        Reset
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>
<!--/Add Asset -->

<!-- Add New Package Modal -->
<div class="modal custom-modal fade p-20" id="add_newpackage" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header border-0">
                <div class="form-header modal-header-title text-start mb-0">
                    <h4 class="mb-0">Add New</h4>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                </button>
            </div>
            <form action="#">
                <div class="modal-body">
                    <h5 class="form-title">Plan Image</h5>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="profile-picture">
                                <div class="upload-profile">
                                    <div class="profile-img company-profile-img">
                                        <img id="company-img" class="img-fluid me-0" src="assets/img/companies/company-add-img.svg" alt="profile-img">
                                    </div>
                                    <div class="add-profile">
                                        <h5>Upload a New Photo</h5>
                                        <span>Profile-pic.jpg</span>
                                    </div>
                                </div>
                                <div class="img-upload">
                                    <label class="btn btn-upload">
                                        Upload <input type="file">
                                    </label>
                                    <a class="btn btn-remove">Remove</a>
                                </div>										
                            </div>
                        </div>
                        <div class="col-md-12">
                            <h5 class="form-title mb-3">Plan Info</h5>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            
                            <div class="input-block mb-3">
                                <label>Plan Name</label>
                                <input type="text" class="form-control" placeholder="Enter Plan Name" value="">
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <div class="input-block mb-3">
                                <label>Plan Type</label>
                                <select class="select">
                                    <option>Monthly</option>
                                    <option>Yearly</option>
                                    <option>free Trail</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <div class="input-block mb-3">
                                <label>Plan Position</label>
                                <select class="select">
                                    <option>1</option>
                                    <option>2</option>
                                    <option>3</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <div class="input-block mb-3">
                                <label>Plan Currency</label>
                                <select class="select">
                                    <option>USD</option>
                                    <option>$</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <div class="input-block mb-3">
                                <label class="d-flex justify-content-between">
                                    <span>Price</span>
                                    <span class="text-primary"><i class="fa-solid fa-circle-exclamation me-2"></i>Set 0 for free</span>
                                </label>
                                <input type="text" class="form-control" placeholder="Enter Package Price">
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-3">
                            <div class="input-block mb-3">
                                <label>Discount Type</label>
                                <select class="select">
                                    <option>Fixed</option>
                                    <option>Percentage</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-3">
                            <div class="input-block mb-3">
                                <label>Discount</label>
                                <input type="text" class="form-control" placeholder="Enter Discount">
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-6 col-lg-3">
                            <div class="input-block mb-3">
                                <label>Limitations Invoices</label>
                                <input type="number" class="form-control" placeholder="0">
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-6 col-lg-3">
                            <div class="input-block mb-3">
                                <label>Max Customers</label>
                                <input type="number" class="form-control" placeholder="0">
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-6 col-lg-3">
                            <div class="input-block mb-3">
                                <label>Product</label>
                                <input type="number" class="form-control" placeholder="0">
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-6 col-lg-3">
                            <div class="input-block mb-3">
                                <label>Supplier</label>
                                <input type="number" class="form-control" placeholder="0">
                            </div>
                        </div>
                    </div>
                    <h6>Plan Modules</h6>
                    <div class="input-block mb-3">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="checkboxes">
                                    <label>
                                        <input type="checkbox" name="checkbox" checked> Select All
                                    </label>
                                </div>										
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="checkboxes">
                                    <label>
                                        <input type="checkbox" name="checkbox" checked> Invoices
                                    </label>
                                </div>										
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="checkboxes">
                                    <label>
                                        <input type="checkbox" name="checkbox" checked> Payments
                                    </label>
                                </div>										
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="checkboxes">
                                    <label>
                                        <input type="checkbox" name="checkbox" checked> Payment Summary
                                    </label>
                                </div>										
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="checkboxes">
                                    <label>
                                        <input type="checkbox" name="checkbox" checked>Suppliers
                                    </label>
                                </div>										
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="checkboxes">
                                    <label>
                                        <input type="checkbox" name="checkbox" checked> Estimates
                                    </label>
                                </div>										
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="checkboxes">
                                    <label>
                                        <input type="checkbox" name="checkbox" checked> Quotations
                                    </label>
                                </div>										
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="checkboxes">
                                    <label>
                                        <input type="checkbox" name="checkbox" checked> Memberships
                                    </label>
                                </div>										
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="checkboxes">
                                    <label>
                                        <input type="checkbox" name="checkbox" checked>Customers
                                    </label>
                                </div>										
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="checkboxes">
                                    <label>
                                        <input type="checkbox" name="checkbox" checked> Recurring Invoices
                                    </label>
                                </div>										
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="checkboxes">
                                    <label>
                                        <input type="checkbox" name="checkbox" checked> Delivery Challans
                                    </label>
                                </div>										
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="checkboxes">
                                    <label>
                                        <input type="checkbox" name="checkbox" checked> Products
                                    </label>
                                </div>										
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="checkboxes">
                                    <label>
                                        <input type="checkbox" name="checkbox" checked>Suppliers
                                    </label>
                                </div>										
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="checkboxes">
                                    <label>
                                        <input type="checkbox" name="checkbox" checked> Expenses
                                    </label>
                                </div>										
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="checkboxes">
                                    <label>
                                        <input type="checkbox" name="checkbox" checked> Reports
                                    </label>
                                </div>										
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="checkboxes">
                                    <label>
                                        <input type="checkbox" name="checkbox" checked> Inventory
                                    </label>
                                </div>										
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="checkboxes">
                                    <label>
                                        <input type="checkbox" name="checkbox" checked>Signature
                                    </label>
                                </div>										
                            </div>
                        </div>
                        
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <h6 class="mb-0">Access Trial</h6>
                        <div class="status-toggle">
                            <input id="access-trail" class="check" type="checkbox" checked="">
                            <label for="access-trail" class="checktoggle checkbox-bg">checkbox</label>
                        </div>
                    </div>
                    <div class="row align-items-center">
                        <div class="col-sm-12 col-md-8">
                            <div class="input-block mb-3">
                                <label>Trial Days</label>
                                <input type="number" class="form-control" placeholder="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center mb-3">
                                <h6 class="mb-0">Is Recommended</h6>
                                <div class="status-toggle">
                                    <input id="recommended" class="check" type="checkbox">
                                    <label for="recommended" class="checktoggle checkbox-bg">checkbox</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="input-block mb-3">
                                <label>Description</label>
                                <textarea rows="3" cols="5" class="form-control" placeholder="Enter Description"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <h6 class="mb-0">Status</h6>
                        <div class="status-toggle">
                            <input id="status-2" class="check" type="checkbox">
                            <label for="status-2" class="checktoggle checkbox-bg">checkbox</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" data-bs-dismiss="modal" class="btn btn-back cancel-btn me-2">Cancel</button>
                    <button type="submit" data-bs-dismiss="modal" class="btn btn-primary paid-continue-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Add New Package Modal -->

<!-- Edit Package Modal -->
<div class="modal custom-modal custom-lg-modal fade p-20" id="edit_package" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header border-0">
                <div class="form-header modal-header-title text-start mb-0">
                    <h4 class="mb-0">Edit Package</h4>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                </button>
            </div>
            <form action="#">
                <div class="modal-body">
                    <h5 class="form-title">Plan Image</h5>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="profile-picture">
                                <div class="upload-profile">
                                    <div class="profile-img company-profile-img">
                                        <img id="company-img-2" class="img-fluid me-0" src="assets/img/icons/price-02.svg" alt="profile-img">
                                    </div>
                                    <div class="add-profile">
                                        <h5>Upload a New Photo</h5>
                                        <span>Profile-pic.jpg</span>
                                    </div>
                                </div>
                                <div class="img-upload">
                                    <label class="btn btn-upload">
                                        Upload <input type="file">
                                    </label>
                                    <a class="btn btn-remove">Remove</a>
                                </div>										
                            </div>
                        </div>
                        <div class="col-md-12">
                            <h5 class="form-title mb-3">Plan Info</h5>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            
                            <div class="input-block mb-3">
                                <label>Plan Name</label>
                                <input type="text" class="form-control" placeholder="Enter Plan Name" value="Enterprise">
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <div class="input-block mb-3">
                                <label>Plan Type</label>
                                <select class="select">
                                    <option>Monthly</option>
                                    <option>Yearly</option>
                                    <option>free Trail</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <div class="input-block mb-3">
                                <label>Plan Position</label>
                                <select class="select">
                                    <option>1</option>
                                    <option>2</option>
                                    <option>3</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <div class="input-block mb-3">
                                <label>Plan Currency</label>
                                <select class="select">
                                    <option>USD</option>
                                    <option>$</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <div class="input-block mb-3">
                                <label class="d-flex justify-content-between">
                                    <span>Price</span>
                                    <span class="text-primary"><i class="fa-solid fa-circle-exclamation me-2"></i>Set 0 for free</span>
                                </label>
                                <input type="text" class="form-control" placeholder="Enter Package Price" value="4545">
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-3">
                            <div class="input-block mb-3">
                                <label>Discount Type</label>
                                <select class="select">
                                    <option>Fixed</option>
                                    <option>Percentage</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-3">
                            <div class="input-block mb-3">
                                <label>Discount</label>
                                <input type="text" class="form-control" placeholder="Enter Discount" value="5%">
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-6 col-lg-3">
                            <div class="input-block mb-3">
                                <label>Limitations Invoices</label>
                                <input type="number" class="form-control" placeholder="0" value="50">
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-6 col-lg-3">
                            <div class="input-block mb-3">
                                <label>Max Customers</label>
                                <input type="number" class="form-control" placeholder="0" value="250">
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-6 col-lg-3">
                            <div class="input-block mb-3">
                                <label>Product</label>
                                <input type="number" class="form-control" placeholder="0" value="110">
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-6 col-lg-3">
                            <div class="input-block mb-3">
                                <label>Supplier</label>
                                <input type="number" class="form-control" placeholder="0" value="14">
                            </div>
                        </div>
                    </div>
                    <h6>Plan Modules</h6>
                    <div class="input-block mb-3">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="checkboxes">
                                    <label>
                                        <input type="checkbox" name="checkbox" checked> Select All
                                    </label>
                                </div>										
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="checkboxes">
                                    <label>
                                        <input type="checkbox" name="checkbox" checked> Invoices
                                    </label>
                                </div>										
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="checkboxes">
                                    <label>
                                        <input type="checkbox" name="checkbox" checked> Payments
                                    </label>
                                </div>										
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="checkboxes">
                                    <label>
                                        <input type="checkbox" name="checkbox" checked> Payment Summary
                                    </label>
                                </div>										
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="checkboxes">
                                    <label>
                                        <input type="checkbox" name="checkbox" checked>Suppliers
                                    </label>
                                </div>										
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="checkboxes">
                                    <label>
                                        <input type="checkbox" name="checkbox" checked> Estimates
                                    </label>
                                </div>										
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="checkboxes">
                                    <label>
                                        <input type="checkbox" name="checkbox" checked> Quotations
                                    </label>
                                </div>										
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="checkboxes">
                                    <label>
                                        <input type="checkbox" name="checkbox" checked> Memberships
                                    </label>
                                </div>										
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="checkboxes">
                                    <label>
                                        <input type="checkbox" name="checkbox" checked>Customers
                                    </label>
                                </div>										
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="checkboxes">
                                    <label>
                                        <input type="checkbox" name="checkbox" checked> Recurring Invoices
                                    </label>
                                </div>										
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="checkboxes">
                                    <label>
                                        <input type="checkbox" name="checkbox" checked> Delivery Challans
                                    </label>
                                </div>										
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="checkboxes">
                                    <label>
                                        <input type="checkbox" name="checkbox" checked> Products
                                    </label>
                                </div>										
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="checkboxes">
                                    <label>
                                        <input type="checkbox" name="checkbox" checked>Suppliers
                                    </label>
                                </div>										
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="checkboxes">
                                    <label>
                                        <input type="checkbox" name="checkbox" checked> Expenses
                                    </label>
                                </div>										
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="checkboxes">
                                    <label>
                                        <input type="checkbox" name="checkbox" checked> Reports
                                    </label>
                                </div>										
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="checkboxes">
                                    <label>
                                        <input type="checkbox" name="checkbox" checked> Inventory
                                    </label>
                                </div>										
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="checkboxes">
                                    <label>
                                        <input type="checkbox" name="checkbox" checked>Signature
                                    </label>
                                </div>										
                            </div>
                        </div>
                        
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <h6 class="mb-0">Access Trial</h6>
                        <div class="status-toggle">
                            <input id="access-trail-2" class="check" type="checkbox" checked="">
                            <label for="access-trail-2" class="checktoggle checkbox-bg">checkbox</label>
                        </div>
                    </div>
                    <div class="row align-items-center">
                        <div class="col-sm-12 col-md-8">
                            <div class="input-block mb-3">
                                <label>Trial Days</label>
                                <input type="number" class="form-control" placeholder="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center mb-3">
                                <h6 class="mb-0">Is Recommended</h6>
                                <div class="status-toggle">
                                    <input id="recommended-2" class="check" type="checkbox">
                                    <label for="recommended-2" class="checktoggle checkbox-bg">checkbox</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="input-block mb-3">
                                <label>Description</label>
                                <textarea rows="3" cols="5" class="form-control" placeholder="Enter Description"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <h6 class="mb-0">Status</h6>
                        <div class="status-toggle">
                            <input id="status" class="check" type="checkbox">
                            <label for="status" class="checktoggle checkbox-bg">checkbox</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" data-bs-dismiss="modal" class="btn btn-back cancel-btn me-2">Cancel</button>
                    <button type="submit" data-bs-dismiss="modal" class="btn btn-primary paid-continue-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Edit Package Modal -->

<!-- Delete Items Modal -->
<div class="modal custom-modal fade modal-delete" id="delete_modal" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-body">
                <div class="form-header">
                    <div class="delete-modal-icon">
                        <span><i class="fe fe-check-circle"></i></span>
                    </div>
                    <h3>Are You Sure?</h3>
                    <p>You want delete plan</p>
                </div>
                <div class="modal-btn delete-action">
                    <div class="modal-footer justify-content-center p-0">
                        <button type="submit" data-bs-dismiss="modal" class="btn btn-primary paid-continue-btn me-2">Yes, Delete</button>
                        <button type="button" data-bs-dismiss="modal" class="btn btn-back cancel-btn">No, Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Delete Items Modal -->
@endif
