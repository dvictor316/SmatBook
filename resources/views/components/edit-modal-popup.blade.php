@if (Route::is(['subscription']))
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
                                <input type="text" class="form-control" value="989-438-3131"
                                    placeholder="Enter Mobile Number">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Message</label>
                                <input type="text" class="form-control" value="Lorem ipsum dolor"
                                    placeholder="message">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-0">
                                <label>Date</label>
                                <input type="text" class="form-control" value="15 Dec 2023, 04:35 PM"
                                    placeholder="message">
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
@endif

@if(Route::is(['customers-ledger']))
<!-- Edit Ledger Modal -->
<div class="modal custom-modal fade" id="edit_ledger" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div class="form-header modal-header-title text-start mb-0">
                    <h4 class="mb-0">Edit Ledger</h4>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-lg-12 col-md-12">
                        <div class="input-block mb-3">
                            <label>Amount</label>
                            <input type="text" class="form-control" placeholder="$300">
                        </div>
                    </div>
                    <div class="col-lg-12 col-md-12">
                        <div class="input-block mb-3">
                            <label>Date</label>
                            <div class="cal-icon cal-icon-info">
                                <input type="text" class="datetimepicker form-control" placeholder="16 Aug 2023, 06:12 PM">
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12 col-md-12">
                        <div class="input-block d-inline-flex align-center mb-0">
                            <label class="me-5 mb-0">Mode</label>
                            <div>
                                <label class="custom_radio me-3 mb-0">
                                    <input type="radio" name="payment" checked>
                                    <span class="checkmark"></span> Credit
                                </label>
                                <label class="custom_radio mb-0">
                                    <input type="radio" name="payment">
                                    <span class="checkmark"></span> Debit
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" data-bs-dismiss="modal" class="btn btn-back cancel-btn me-2">Cancel</a>
                <a href="#" data-bs-dismiss="modal" class="btn btn-primary paid-continue-btn">Submit</a>
            </div>
        </div>
    </div>
</div>
<!-- /Edit Ledger Modal -->
@endif

@if (Route::is(['packages']))
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
                                        <img id="company-img-2" class="img-fluid me-0" src="{{URL::asset('/assets/img/icons/price-02.svg')}}" alt="profile-img">
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
                                    <option>Lifetime</option>
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
<!-- /Edit Package Modal -->
@endif

@if (Route::is(['domain']))
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
                                <input type="text" class="form-control" value="John Smith"
                                    placeholder="Enter Name">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Email</label>
                                <input type="text" class="form-control" value="john@example.com"
                                    placeholder="Email">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Mobile</label>
                                <input type="text" class="form-control" value="989-438-3131"
                                    placeholder="Enter Mobile Number">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Message</label>
                                <input type="text" class="form-control" value="Lorem ipsum dolor"
                                    placeholder="message">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-0">
                                <label>Date</label>
                                <input type="text" class="form-control" value="15 Dec 2023, 04:35 PM"
                                    placeholder="message">
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
@endif

@if (Route::is(['purchase-transcation']))
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
@endif

@if (Route::is(['ledger']))
    <!-- Edit Ledger Modal -->
    <div class="modal custom-modal fade" id="edit_ledger" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0">Edit Ledger</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Amount</label>
                                <input type="text" class="form-control" placeholder="$400">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Date</label>
                                <div class="cal-icon cal-icon-info">
                                    <input type="text" class="datetimepicker form-control"
                                        placeholder="16 Aug 2023, 06:12 PM">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Reference</label>
                                <input type="text" class="form-control" placeholder="#25689825">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block d-inline-flex align-center mb-0">
                                <label class="me-5 mb-0">Mode</label>
                                <div>
                                    <label class="custom_radio me-3 mb-0">
                                        <input type="radio" name="payment" checked>
                                        <span class="checkmark"></span> Credit
                                    </label>
                                    <label class="custom_radio mb-0">
                                        <input type="radio" name="payment">
                                        <span class="checkmark"></span> Debit
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" data-bs-dismiss="modal" class="btn btn-back cancel-btn me-2">Cancel</a>
                    <a href="#" data-bs-dismiss="modal" class="btn btn-primary paid-continue-btn">Submit</a>
                </div>
            </div>
        </div>
    </div>
    <!-- /Edit Ledger Modal -->
@endif

@if (Route::is(['vendors']))
    <!-- Edit Supplier Modal -->
    <div class="modal custom-modal fade" id="edit_vendor" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0">Edit Supplier</h4>
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
                                    <input type="text" class="form-control" value="John Smith"
                                        placeholder="Enter Name">
                                </div>
                            </div>
                            <div class="col-lg-12 col-md-12">
                                <div class="input-block mb-3">
                                    <label>Email</label>
                                    <input type="text" class="form-control" value="john@example.com"
                                        placeholder="Select Date">
                                </div>
                            </div>
                            <div class="col-lg-12 col-md-12">
                                <div class="input-block mb-3">
                                    <label>Phone Number</label>
                                    <input type="text" class="form-control" value="+1 989-438-3131"
                                        placeholder="Enter Reference Number">
                                </div>
                            </div>
                            <div class="col-lg-12 col-md-12">
                                <div class="input-block mb-0">
                                    <label>Balance</label>
                                    <input type="text" class="form-control" value="$4200"
                                        placeholder="Enter Reference Number">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-primary paid-cancel-btn me-2">Cancel</button>
                        <button type="submit" data-bs-dismiss="modal"
                            class="btn btn-primary paid-continue-btn">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Edit Supplier Modal -->
@endif

@if (Route::is(['inventory']))
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
                <form action="#">
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
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Cancel</button>
                        <button type="submit" data-bs-dismiss="modal"
                            class="btn btn-primary paid-continue-btn">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Edit Stock in Modal -->
@endif

@if (Route::is(['inventory-history']))
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
                <form action="#">
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
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Cancel</button>
                        <button type="submit" data-bs-dismiss="modal"
                            class="btn btn-primary paid-continue-btn">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Edit Stock in Modal -->
@endif

@if (Route::is(['states']))
    <!-- Edit Items Modal -->
    <div class="modal custom-modal fade" id="edit_state" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0">Edit State</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card-body">
                                    <div class="form-group-item border-0 pb-0">
                                        <div class="row">
                                            <div class="col-lg-12 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>Country Code</label>
                                                    <select class="select">
                                                        <option>Select Country Code</option>
                                                        <option>Country Code 1</option>
                                                        <option>Country Code 2</option>
                                                        <option>Country Code 3</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-12 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>Country Name</label>
                                                    <select class="select">
                                                        <option>Select Country Name</option>
                                                        <option>Country Name 1</option>
                                                        <option>Country Name 2</option>
                                                        <option>Country Name 3</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-12 col-md-6 col-sm-12">
                                                <div class="input-block">
                                                    <label>State Name</label>
                                                    <input type="text" class="form-control" value="Swains Island"
                                                        placeholder="Enter State Name">
                                                </div>
                                            </div>
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
                            class="btn btn-primary paid-continue-btn">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Edit Items Modal -->
@endif

@if (Route::is(['signature-list']))
    <!-- Edit Modal -->
    <div class="modal custom-modal signature-add-modal fade" id="edit_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0">Edit Signature</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Signature Name</label>
                                <input type="text" class="form-control" value="Shirley">
                            </div>
                        </div>
                        <div class="col-lg-9 col-md-12">
                            <div class="input-block mb-3">
                                <label>Upload</label>
                                <div class="input-block service-upload service-upload-info mb-0">
                                    <span><i class="fe fe-upload-cloud me-1"></i>Upload Signature</span>
                                    <input type="file" multiple="">
                                    <div id="frames2"></div>
                                </div>
                                <p>Image format should be png and jpg</p>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-12 ps-0">
                            <div class="input-block mb-3">
                                <label>&nbsp;</label>
                                <div class="signature-preview">
                                    <a href="javascript:void(0);"><i class="fe fe-trash-2"></i></a>
                                    <img src="{{ URL::asset('/assets/img/edit-signature.png') }}" class="img-fluid"
                                        alt="img">
                                </div>
                            </div>

                        </div>
                        <div class="col-lg-12 col-md-12">
                            <label class="custom_check">
                                <input type="checkbox" name="invoice">
                                <span class="checkmark"></span>
                                Mark as default
                            </label>

                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="javascript:void(0);" data-bs-dismiss="modal" class="btn btn-back me-2">Cancel</a>
                    <a href="javascript:void(0);" data-bs-dismiss="modal" class="btn btn-primary">Update</a>
                </div>
            </div>
        </div>
    </div>
    <!-- / Edit Modal -->
@endif

@if (Route::is(['signature-invoice']))
    <!-- Edit Bank Details Modal -->
    <div class="modal custom-modal fade" id="bank_details" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0">Edit Bank Details</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Bank Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" placeholder="Enter Bank Name">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Account Number <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" placeholder="Enter Account Number">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Branch Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" placeholder="Enter Branch Name">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-0">
                                <label>IFSC Code <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" placeholder="Enter IFSC COde">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" data-bs-dismiss="modal" class="btn btn-primary paid-cancel-btn me-2">Back</a>
                    <a href="#" data-bs-dismiss="modal" class="btn btn-primary paid-continue-btn">Update</a>
                </div>
            </div>
        </div>
    </div>
    <!-- /Edit Bank Details Modal -->
@endif

@if (Route::is(['purchase-return']))
    <!-- Edit Supplier Modal -->
    <div class="modal custom-modal fade" id="edit_vendor" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0">Edit Supplier</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Name</label>
                                <input type="text" class="form-control" value="John Smith"
                                    placeholder="Enter Name">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Email</label>
                                <input type="text" class="form-control" value="john@example.com"
                                    placeholder="Select Date">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Phone Number</label>
                                <input type="text" class="form-control" value="+1 989-438-3131"
                                    placeholder="Enter Reference Number">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-0">
                                <label>Balance</label>
                                <input type="text" class="form-control" value="$4200"
                                    placeholder="Enter Reference Number">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" data-bs-dismiss="modal" class="btn btn-primary paid-cancel-btn me-2">Cancel</a>
                    <a href="#" data-bs-dismiss="modal" class="btn btn-primary paid-continue-btn">Update</a>
                </div>
            </div>
        </div>
    </div>
    <!-- /Edit Supplier Modal -->
@endif

@if (Route::is(['all-blogs']))
    <!-- Edit Blog -->
    <div class="modal custom-modal modal-lg fade" id="edit_blog" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0">Edit Blog</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card-body">
                                    <div class="form-groups-item border-0 pb-0">
                                        <div class="row">
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>Title <span class="text-danger">*</span></label>
                                                    <input type="text"
                                                        value="All the Lorem Ipsum generators on the Internet"
                                                        class="form-control" placeholder="Enter Title">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>Category</label>
                                                    <select class="select">
                                                        <option>Select Category</option>
                                                        <option selected>Category 1</option>
                                                        <option>Category 2</option>
                                                        <option>Category 3</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>Tag</label>
                                                    <input type="text" data-role="tagsinput"
                                                        class="input-tags form-control" placeholder="Enter Tags"
                                                        name="services" value="Marketing ">
                                                </div>
                                            </div>
                                            <div class="col-lg-12 col-md-12 description-box">
                                                <div class="input-block mb-3" id = "summernote_container">
                                                    <label class="form-control-label">Description</label>
                                                    <textarea class="summernote form-control" placeholder="Type your message">Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor.</textarea>
                                                </div>
                                            </div>
                                            <div class="col-lg-12 col-md-12">
                                                <div class="input-block">
                                                    <label>Image</label>
                                                    <div class="input-block service-upload mb-0">
                                                        <span><img
                                                                src="{{ URL::asset('/assets/img/icons/drop-icon.svg') }}"
                                                                alt="upload"></span>
                                                        <h6 class="drop-browse align-center">Drop your files here
                                                            or<span class="text-primary ms-1">browse</span></h6>
                                                        <p class="text-muted">Maximum size: 50MB</p>
                                                        <input type="file" multiple="" id="image_sign2">
                                                        <div id="frames2"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-primary paid-cancel-btn me-2">Cancel</button>
                        <button type="submit" data-bs-dismiss="modal"
                            class="btn btn-primary paid-continue-btn">Update Post</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Edit Blog -->
@endif

@if (Route::is(['inactive-blog']))
    <!-- Edit Blog -->
    <div class="modal custom-modal modal-lg fade" id="edit_blog" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0">Edit Blog</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card-body">
                                    <div class="form-groups-item border-0 pb-0">
                                        <div class="row">
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>Title <span class="text-danger">*</span></label>
                                                    <input type="text"
                                                        value="All the Lorem Ipsum generators on the Internet"
                                                        class="form-control" placeholder="Enter Title">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>Category</label>
                                                    <select class="select">
                                                        <option>Select Category</option>
                                                        <option selected>Category 1</option>
                                                        <option>Category 2</option>
                                                        <option>Category 3</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>Tag</label>
                                                    <input type="text" data-role="tagsinput"
                                                        class="input-tags form-control" placeholder="Enter Tags"
                                                        name="services" value="Marketing ">
                                                </div>
                                            </div>
                                            <div class="col-lg-12 col-md-12 description-box">
                                                <div class="input-block mb-3" id = "summernote_container">
                                                    <label class="form-control-label">Description</label>
                                                    <textarea class="summernote form-control" placeholder="Type your message">Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor.</textarea>
                                                </div>
                                            </div>
                                            <div class="col-lg-12 col-md-12">
                                                <div class="input-block">
                                                    <label>Image</label>
                                                    <div class="input-block service-upload mb-0">
                                                        <span><img
                                                                src="{{ URL::asset('/assets/img/icons/drop-icon.svg') }}"
                                                                alt="upload"></span>
                                                        <h6 class="drop-browse align-center">Drop your files here
                                                            or<span class="text-primary ms-1">browse</span></h6>
                                                        <p class="text-muted">Maximum size: 50MB</p>
                                                        <input type="file" multiple="" id="image_sign2">
                                                        <div id="frames2"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-primary paid-cancel-btn me-2">Cancel</button>
                        <button type="submit" data-bs-dismiss="modal"
                            class="btn btn-primary paid-continue-btn">Update Post</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Edit Blog -->
@endif

@if (Route::is(['categories']))
    <!-- modal -->
    <div class="modal custom-modal fade bank-details" id="edit-blog-categories" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="form-header text-start mb-0">
                        <h4 class="mb-0 text-dark fw-bold">Edit Category</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="bank-inner-details">
                            <div class="row">
                                <div class="col-lg-4 col-md-12">
                                    <div class="input-block mb-3">
                                        <label>Category Name<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" value="Expenses">
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-12">
                                    <div class="input-block mb-3">
                                        <label>Slug<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" value="Offer">
                                        <small class="form-text text-muted"> (If you leave it empty, it will be
                                            generated automatically.)</small>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-12">
                                    <div class="input-block mb-3">
                                        <label>Description (Meta Tag)<span class="text-danger">*</span></label>
                                        <input type="text" data-role="tagsinput" class="input-tags form-control"
                                            placeholder="Enter Tags" name="services" value="Expenses ">
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-12">
                                    <div class="input-block mb-3">
                                        <label>Keywords (Meta Tag)<span class="text-danger">*</span></label>
                                        <input type="text" data-role="tagsinput" class="input-tags form-control"
                                            placeholder="Enter Tags" name="services" value="Expenses ">
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-12">
                                    <div class="input-block mb-3">
                                        <label>Order<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" value="3">
                                    </div>
                                </div>
                                <div class="input-block mb-3">
                                    <label>Status</label>
                                    <div class="status-toggle">
                                        <input id="rating_10" class="check" type="checkbox" checked>
                                        <label for="rating_10" class="checktoggle checkbox-bg "></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer blog-categories-btn">
                        <div class="bank-details-btn ">
                            <button type="submit" data-bs-dismiss="modal" class="btn btn-primary me-2">Add
                                Category</button>

                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if (Route::is(['cities']))
    <!-- Edit Items Modal -->
    <div class="modal custom-modal fade" id="edit_city" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0">Edit City</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card-body">
                                    <div class="form-groups-item border-0 pb-0">
                                        <div class="row">
                                            <div class="col-lg-12 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>Country Name</label>
                                                    <select class="select">
                                                        <option>Select Country Name</option>
                                                        <option>Country Name 1</option>
                                                        <option>Country Name 2</option>
                                                        <option>Country Name 3</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-12 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>State Name</label>
                                                    <select class="select">
                                                        <option>Select State Name</option>
                                                        <option>State Name 1</option>
                                                        <option>State Name 2</option>
                                                        <option>State Name 3</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-12 col-md-6 col-sm-12">
                                                <div class="input-block">
                                                    <label>City Name</label>
                                                    <input type="text" class="form-control" value="Gandzasar"
                                                        placeholder="Enter City Name">
                                                </div>
                                            </div>
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
                            class="btn btn-primary paid-continue-btn">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Edit Items Modal -->
@endif

@if (Route::is(['contact-messages']))
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
                <form action="#">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-lg-12 col-md-12">
                                <div class="input-block mb-3">
                                    <label>Name</label>
                                    <input type="text" class="form-control" value="John Smith"
                                        placeholder="Enter Name">
                                </div>
                            </div>
                            <div class="col-lg-12 col-md-12">
                                <div class="input-block mb-3">
                                    <label>Email</label>
                                    <input type="text" class="form-control" value="john@example.com"
                                        placeholder="Email">
                                </div>
                            </div>
                            <div class="col-lg-12 col-md-12">
                                <div class="input-block mb-3">
                                    <label>Mobile</label>
                                    <input type="text" class="form-control" value="989-438-3131"
                                        placeholder="Enter Mobile Number">
                                </div>
                            </div>
                            <div class="col-lg-12 col-md-12">
                                <div class="input-block mb-3">
                                    <label>Message</label>
                                    <input type="text" class="form-control" value="Lorem ipsum dolor"
                                        placeholder="message">
                                </div>
                            </div>
                            <div class="col-lg-12 col-md-12">
                                <div class="input-block mb-0">
                                    <label>Date</label>
                                    <input type="text" class="form-control" value="15 Dec 2023, 04:35 PM"
                                        placeholder="message">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Cancel</button>
                        <button type="submit" data-bs-dismiss="modal"
                            class="btn btn-primary paid-continue-btn">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Edit Supplier Modal -->
@endif

@if (Route::is(['countries']))
    <!-- Edit Items Modal -->
    <div class="modal custom-modal fade" id="edit_country" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0">Edit Country</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card-body">
                                    <div class="form-groups-item border-0 pb-0">
                                        <div class="row">
                                            <div class="col-lg-12 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>Country Code</label>
                                                    <input type="text" class="form-control" value="684"
                                                        placeholder="Enter Country Code">
                                                </div>
                                            </div>
                                            <div class="col-lg-12 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>Country ID</label>
                                                    <input type="text" class="form-control" value="AS"
                                                        placeholder="Enter Country Id">
                                                </div>
                                            </div>
                                            <div class="col-lg-12 col-md-6 col-sm-12">
                                                <div class="input-block">
                                                    <label>Country Name</label>
                                                    <input type="text" class="form-control" value="American Samoa"
                                                        placeholder="Enter Country Name">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Cancel</button>
                        <button type="submit" data-bs-dismiss="modal" class="btn btn-primary paid-continue-btn">Edit
                            Country</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Edit Items Modal -->
@endif

@if (Route::is(['custom-filed']))
    <!-- Edit Modal -->
    <div class="modal custom-modal fade" id="edit" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0">Edit Custom Fields</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body add">
                    <div class="addnew-modal">
                        <div class="row">
                            <div class="col-lg-12 col-sm-12">
                                <div class="input-block mb-3">
                                    <label>Custom Fields For <span class="text-danger">*</span> </label>
                                    <ul class="form-group">
                                        <li>
                                            <select class="select ">
                                                <option>Plan</option>
                                                <option>Companies</option>
                                                <option>Ledger</option>
                                                <option>Packages</option>
                                            </select>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="col-lg-12 col-sm-12">
                                <div class="input-block mb-3">
                                    <label>Label <span class="text-danger">*</span> </label>
                                    <input type="text" class="form-control" placeholder="Enter Label"
                                        value="Email">
                                </div>
                            </div>
                            <div class="col-lg-12 col-sm-12">
                                <div class="input-block mb-3">
                                    <label>Default Value</label>
                                    <input type="text" class="form-control" placeholder="Enter Value"
                                        value="Enter Email">
                                </div>
                            </div>
                            <div class="col-lg-12 col-sm-12">
                                <div class="input-block mb-3">
                                    <label>Input Type <span class="text-danger">*</span> </label>
                                    <ul class="form-group">
                                        <li>
                                            <select class="select ">
                                                <option>Text</option>
                                                <option>Text Area</option>
                                                <option>Select</option>
                                                <option>Phone Number</option>
                                                <option>Email</option>
                                            </select>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="input-block add mb-3 row">
                        <div class="col-md-3">
                            <div class="checkbox">
                                <label class="mb-0 d-flex align-items-center">
                                    <input type="checkbox" name="checkbox" checked> Required
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="checkbox">
                                <label class="mb-0 d-flex align-items-center">
                                    <input type="checkbox" name="checkbox">Disable
                                </label>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-12 d-flex align-items-center justify-content-between">
                            <div class="two-factor content p-0">
                                <h6>Status</h6>
                            </div>
                            <div class="addnew-checkbox">
                                <div class="status-toggle">
                                    <input id="rating_1" class="check" type="checkbox" checked="">
                                    <label for="rating_1" class="checktoggle checkbox-bg factor">checkbox</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer add">
                        <a href="#" data-bs-dismiss="modal" class="btn btn-back cancel-btn me-2">Cancel</a>
                        <a href="#" data-bs-dismiss="modal" class="btn btn-primary paid-continue-btn">Save
                            Changes</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Edit Modal -->
@endif

@if (Route::is(['edit-credit-notes']))
    <!-- Edit Bank Details Modal -->
    <div class="modal custom-modal fade" id="bank_details" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0">Edit Bank Details</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Bank Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" placeholder="Enter Bank Name">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Account Number <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" placeholder="Enter Account Number">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Branch Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" placeholder="Enter Branch Name">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-0">
                                <label>IFSC Code <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" placeholder="Enter IFSC COde">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" data-bs-dismiss="modal" class="btn btn-primary paid-cancel-btn me-2">Back</a>
                    <a href="#" data-bs-dismiss="modal" class="btn btn-primary paid-continue-btn">Update</a>
                </div>
            </div>
        </div>
    </div>
    <!-- /Edit Bank Details Modal -->
@endif

@if (Route::is(['edit-delivery-challans']))
    <!-- Edit Bank Details Modal -->
    <div class="modal custom-modal fade" id="bank_details" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0">Edit Bank Details</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Bank Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" placeholder="Enter Bank Name">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Account Number <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" placeholder="Enter Account Number">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Branch Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" placeholder="Enter Branch Name">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-0">
                                <label>IFSC Code <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" placeholder="Enter IFSC COde">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" data-bs-dismiss="modal"
                        class="btn btn-primary paid-cancel-btn me-2">Back</a>
                    <a href="#" data-bs-dismiss="modal" class="btn btn-primary paid-continue-btn">Update</a>
                </div>
            </div>
        </div>
    </div>
    <!-- /Edit Bank Details Modal -->
@endif

@if (Route::is(['edit-invoice']))
    <!-- Edit Bank Details Modal -->
    <div class="modal custom-modal fade" id="bank_details" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0">Edit Bank Details</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Bank Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" placeholder="Enter Bank Name">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Account Number <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" placeholder="Enter Account Number">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Branch Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" placeholder="Enter Branch Name">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-0">
                                <label>IFSC Code <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" placeholder="Enter IFSC COde">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" data-bs-dismiss="modal"
                        class="btn btn-primary paid-cancel-btn me-2">Back</a>
                    <a href="#" data-bs-dismiss="modal" class="btn btn-primary paid-continue-btn">Update</a>
                </div>
            </div>
        </div>
    </div>
    <!-- /Edit Bank Details Modal -->
@endif

@if (Route::is(['purchase-report']))
    <!-- Edit Supplier Modal -->
    <div class="modal custom-modal fade" id="edit_vendor" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0">Edit Supplier</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Name</label>
                                <input type="text" class="form-control" value="John Smith"
                                    placeholder="Enter Name">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Email</label>
                                <input type="text" class="form-control" value="john@example.com"
                                    placeholder="Select Date">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Phone Number</label>
                                <input type="text" class="form-control" value="+1 989-438-3131"
                                    placeholder="Enter Reference Number">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-0">
                                <label>Balance</label>
                                <input type="text" class="form-control" value="$4200"
                                    placeholder="Enter Reference Number">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" data-bs-dismiss="modal"
                        class="btn btn-primary paid-cancel-btn me-2">Cancel</a>
                    <a href="#" data-bs-dismiss="modal" class="btn btn-primary paid-continue-btn">Update</a>
                </div>
            </div>
        </div>
    </div>
    <!-- /Edit Supplier Modal -->
@endif

@if (Route::is(['edit-purchases-order']))
    <!-- Edit Bank Details Modal -->
    <div class="modal custom-modal fade" id="bank_details" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0">Edit Bank Details</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Bank Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" placeholder="Enter Bank Name">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Account Number <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" placeholder="Enter Account Number">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Branch Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" placeholder="Enter Branch Name">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-0">
                                <label>IFSC Code <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" placeholder="Enter IFSC COde">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" data-bs-dismiss="modal"
                        class="btn btn-primary paid-cancel-btn me-2">Back</a>
                    <a href="#" data-bs-dismiss="modal" class="btn btn-primary paid-continue-btn">Update</a>
                </div>
            </div>
        </div>
    </div>
    <!-- /Edit Bank Details Modal -->
@endif

@if (Route::is(['edit-purchases']))
    <!-- Edit Bank Details Modal -->
    <div class="modal custom-modal fade" id="bank_details" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0">Edit Bank Details</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Bank Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" placeholder="Enter Bank Name">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Account Number <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" placeholder="Enter Account Number">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Branch Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" placeholder="Enter Branch Name">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-0">
                                <label>IFSC Code <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" placeholder="Enter IFSC COde">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" data-bs-dismiss="modal"
                        class="btn btn-primary paid-cancel-btn me-2">Back</a>
                    <a href="#" data-bs-dismiss="modal" class="btn btn-primary paid-continue-btn">Update</a>
                </div>
            </div>
        </div>
    </div>
    <!-- /Edit Bank Details Modal -->
@endif

@if (Route::is(['edit-quotations']))
    <!-- Edit Bank Details Modal -->
    <div class="modal custom-modal fade" id="bank_details" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0">Edit Bank Details</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Bank Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" placeholder="Enter Bank Name">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Account Number <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" placeholder="Enter Account Number">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Branch Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" placeholder="Enter Branch Name">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-0">
                                <label>IFSC Code <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" placeholder="Enter IFSC COde">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" data-bs-dismiss="modal"
                        class="btn btn-primary paid-cancel-btn me-2">Back</a>
                    <a href="#" data-bs-dismiss="modal" class="btn btn-primary paid-continue-btn">Update</a>
                </div>
            </div>
        </div>
    </div>
    <!-- /Edit Bank Details Modal -->
@endif

@if (Route::is(['rexpense-report']))
    <!-- Edit Supplier Modal -->
    <div class="modal custom-modal fade" id="edit_vendor" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0">Edit Supplier</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Name</label>
                                <input type="text" class="form-control" value="John Smith"
                                    placeholder="Enter Name">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Email</label>
                                <input type="text" class="form-control" value="john@example.com"
                                    placeholder="Select Date">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Phone Number</label>
                                <input type="text" class="form-control" value="+1 989-438-3131"
                                    placeholder="Enter Reference Number">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-0">
                                <label>Balance</label>
                                <input type="text" class="form-control" value="$4200"
                                    placeholder="Enter Reference Number">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" data-bs-dismiss="modal"
                        class="btn btn-primary paid-cancel-btn me-2">Cancel</a>
                    <a href="#" data-bs-dismiss="modal" class="btn btn-primary paid-continue-btn">Update</a>
                </div>
            </div>
        </div>
    </div>
    <!-- /Edit Supplier Modal -->
@endif

@if (Route::is(['expenses']))
    <!-- Edit Expenses Modal -->
    <div class="modal custom-modal modal-lg fade" id="edit_expenses" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0">Edit Expenses</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card-body">
                                    <div class="border-0 pb-0">
                                        <div class="row">
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>Expense ID</label>
                                                    <input type="text" class="form-control"
                                                        placeholder="Enter Expense ID">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>Reference</label>
                                                    <input type="text" class="form-control"
                                                        placeholder="Enter Reference No">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>Amount </label>
                                                    <input type="number" class="form-control"
                                                        placeholder="Enter Amount">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>Payment Mode</label>
                                                    <select class="select">
                                                        <option>Select Payment Mode</option>
                                                        <option>Cash</option>
                                                        <option>Cheque</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>Expense Date </label>
                                                    <input type="text" class="form-control datetimepicker"
                                                        placeholder="Select Expense Date">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>Payment Status</label>
                                                    <select class="select">
                                                        <option>Select Payment Status</option>
                                                        <option>Paid</option>
                                                        <option>Pending</option>
                                                        <option>Cancelled</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-12 col-md-12 description-box">
                                                <div class="input-block mb-3" id = "summernote_container2">
                                                    <label class="form-control-label">Description</label>
                                                    <textarea class="summernote form-control" placeholder="Type your message"></textarea>
                                                </div>
                                            </div>
                                            <div class="col-lg-12 col-md-12">
                                                <div class="input-block">
                                                    <label>Attachment</label>
                                                    <div class="input-block service-upload mb-0">
                                                        <span><img
                                                                src="{{ URL::asset('/assets/img/icons/drop-icon.svg') }}"
                                                                alt="upload"></span>
                                                        <h6 class="drop-browse align-center">Drop your files here
                                                            or<span class="text-primary ms-1">browse</span></h6>
                                                        <p class="text-muted">Maximum size: 50MB</p>
                                                        <input type="file" multiple="" id="image_sign2">
                                                        <div id="frames2"></div>
                                                    </div>
                                                </div>
                                            </div>
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
                            class="btn btn-primary paid-continue-btn">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Edit Expenses Modal -->
@endif

@if (Route::is(['faq']))
    <!-- Edit FAQ Modal -->
    <div class="modal custom-modal fade" id="edit_faq" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0">Edit FAQ</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="input-block mb-3">
                                    <label>Title Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" placeholder="Enter Title">
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="input-block mb-3">
                                    <label>Category <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" placeholder="Enter Category">
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="input-block mb-3">
                                    <label>Details <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" placeholder="Enter Details">
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="input-block mb-0">
                                    <div class="check-mark-status d-flex justify-content-between">
                                        <h6 class="label-text">Status</h6>
                                        <div class="status-toggle">
                                            <input id="rating_2" class="check" type="checkbox" checked="">
                                            <label for="rating_2" class="checktoggle checkbox-bg">checkbox</label>
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
                            class="btn btn-primary paid-continue-btn">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Edit FAQ Modal -->
@endif

@if (Route::is(['inventory-list']))
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
@endif

@if (Route::is(['pages']))
    <!-- Modal -->
    <div class="modal custom-modal fade" id="pages_edit" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0">Edit Page</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-lg-12 col-md-12">
                                <div class="input-block mb-3">
                                    <label>Page Title(English)</label>
                                    <input type="text" class="form-control" placeholder="About Us">
                                </div>
                            </div>
                            <div class="col-lg-12 col-md-12">
                                <div class="input-block mb-3">
                                    <label>Page Slug</label>
                                    <input type="text" class="form-control" placeholder="about-us">
                                </div>
                            </div>
                            <div class="col-lg-12 col-md-12">
                                <div class="input-block mb-0">
                                    <label>Page Content(English)</label>
                                    <textarea rows="5" cols="5" class="form-control" placeholder="about us page contents"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal" class="btn btn-back me-2">Back</button>
                        <button type="submit" data-bs-dismiss="modal" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Modal -->
@endif

@if (Route::is(['roles-permission']))
    <!-- Edit Role Modal -->
    <div class="modal custom-modal fade" id="edit_role" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0">Edit Role</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-lg-12 col-md-12">
                                <div class="input-block mb-0">
                                    <label>Role Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" Value="Admin"
                                        placeholder="Enter Role Name">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Close</button>
                        <button type="submit" data-bs-dismiss="modal"
                            class="btn btn-primary paid-continue-btn">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Edit Role Modal -->
@endif

@if (Route::is(['sales-report']))
    <!-- Edit Supplier Modal -->
    <div class="modal custom-modal fade" id="edit_vendor" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0">Edit Supplier</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Name</label>
                                <input type="text" class="form-control" value="John Smith"
                                    placeholder="Enter Name">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Email</label>
                                <input type="text" class="form-control" value="john@example.com"
                                    placeholder="Select Date">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-3">
                                <label>Phone Number</label>
                                <input type="text" class="form-control" value="+1 989-438-3131"
                                    placeholder="Enter Reference Number">
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="input-block mb-0">
                                <label>Balance</label>
                                <input type="text" class="form-control" value="$4200"
                                    placeholder="Enter Reference Number">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" data-bs-dismiss="modal"
                        class="btn btn-primary paid-cancel-btn me-2">Cancel</a>
                    <a href="#" data-bs-dismiss="modal" class="btn btn-primary paid-continue-btn">Update</a>
                </div>
            </div>
        </div>
    </div>
    <!-- /Edit Supplier Modal -->
@endif

@if (Route::is(['testimonials']))
    <!-- Edit Testmonials Modal -->
    <div class="modal custom-modal modal-lg fade" id="edit_testimonial" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0">Edit Testimonials</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-lg-12 col-md-8 m-auto">
                                <form action="#">
                                    <div class="card-body">
                                        <div class="form-groups-item border-0 p-0">
                                            <h5 class="form-title">Profile Picture</h5>
                                            <div class="profile-picture">
                                                <div class="upload-profile">
                                                    <div class="profile-img">
                                                        <img id="blah1" class="avatar"
                                                            src="{{ URL::asset('/assets/img/profiles/avatar-10.jpg') }}"
                                                            alt="profile-img">
                                                    </div>
                                                    <div class="add-profile">
                                                        <h5>Upload a New Photo</h5>
                                                        <span>Profile-pic.jpg</span>
                                                    </div>
                                                </div>
                                                <div class="img-upload">
                                                    <label class="btn btn-primary">
                                                        Upload <input type="file">
                                                    </label>
                                                    <a class="btn btn-remove">Remove</a>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="input-block mb-3">
                                                        <label>Name</label>
                                                        <input type="text" class="form-control"
                                                            value="John Smith" placeholder="Enter Name">
                                                    </div>
                                                </div>
                                                <div class="col-lg-12">
                                                    <div class="input-block mb-3">
                                                        <label>Job Title</label>
                                                        <input type="text" class="form-control"
                                                            value="Lorem ipsum dolor sit amet, consectetur"
                                                            placeholder="Enter Job Title">
                                                    </div>
                                                </div>
                                                <div class="col-lg-4 col-md-6 col-sm-12">
                                                    <div class="input-block mb-3">
                                                        <label>Rating</label>
                                                        <div>
                                                            <span class="mail-important"><i
                                                                    class="fas fa-star starred text-warning"></i></span>
                                                            <span class="mail-important"><i
                                                                    class="fas fa-star starred text-warning"></i></span>
                                                            <span class="mail-important"><i
                                                                    class="fas fa-star starred text-warning"></i></span>
                                                            <span class="mail-important"><i
                                                                    class="fas fa-star starred text-warning"></i></span>
                                                            <span class="mail-important"><i
                                                                    class="fas fa-star starred text-warning"></i></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-12">
                                                    <div class="input-block">
                                                        <label>Comments</label>
                                                        <input type="text" class="form-control"
                                                            value="Lorem ipsum dolor sit amet, consectetur"
                                                            placeholder="Enter Comments">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Cancel</button>
                        <button type="submit" data-bs-dismiss="modal"
                            class="btn btn-primary paid-continue-btn">Add Testimonials</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Edit Testmonials -->
@endif

@if (Route::is([
        'tickets-closed',
        'tickets-kanban',
        'tickets-list-closed',
        'tickets-list-open',
        'tickets-list-pending',
        'tickets-list-resolved',
        'tickets-list',
        'tickets-open',
        'tickets-pending',
        'tickets-resolved',
        'tickets',
    ]))
    <!-- Edit Ticket Modal -->
    <div class="modal custom-modal modal-lg fade" id="edit_ticket" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0">Edit Ticket</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-4 col-md-12">
                            <div class="input-block mb-3">
                                <label>Subject</label>
                                <input type="text" class="form-control" value="Support for theme"
                                    placeholder="Enter Subject">
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-12">
                            <div class="input-block mb-3">
                                <label>Assigned Name</label>
                                <input type="text" class="form-control" value="Richard"
                                    placeholder="Enter Assigned Name">
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-12">
                            <div class="input-block mb-3">
                                <label>Assigned Date</label>
                                <div class="cal-icon cal-icon-info">
                                    <input type="text" class="datetimepicker form-control" value="17 Dec 2023"
                                        placeholder="Select Date">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-12">
                            <div class="input-block mb-3">
                                <label>Created Date</label>
                                <div class="cal-icon cal-icon-info">
                                    <input type="text" class="datetimepicker form-control" value="19 Dec 2023"
                                        placeholder="Select Date">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-12">
                            <div class="input-block mb-3">
                                <label>Due Date</label>
                                <div class="cal-icon cal-icon-info">
                                    <input type="text" class="datetimepicker form-control" value="16 Jan 2023"
                                        placeholder="Select Date">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-12">
                            <div class="input-block mb-3">
                                <label>Assignee Name</label>
                                <input type="text" class="form-control" value="John Smith"
                                    placeholder="Enter Assignee Name">
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="input-block mb-3">
                                <label>Priority</label>
                                <select class="select">
                                    <option>Select Priority</option>
                                    <option>Medium</option>
                                    <option>Low</option>
                                    <option>High</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="input-block mb-0">
                                <label>Status</label>
                                <select class="select">
                                    <option>Select Status</option>
                                    <option>Open</option>
                                    <option>Resolved</option>
                                    <option>Pending</option>
                                    <option>Closed</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" data-bs-dismiss="modal"
                        class="btn btn-primary paid-cancel-btn me-2">Cancel</a>
                    <a href="#" data-bs-dismiss="modal" class="btn btn-primary paid-continue-btn">Update</a>
                </div>
            </div>
        </div>
    </div>
    <!-- /Edit Ticket Modal -->
@endif

@if (Route::is(['users']))
    <!-- Edit User -->
    <div class="modal custom-modal modal-lg fade" id="edit_user" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0">Edit User</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card-body">
                                    <div class="form-groups-item">
                                        <h5 class="form-title">Profile Picture</h5>
                                        <div class="profile-picture">
                                            <div class="upload-profile">
                                                <div class="profile-img">
                                                    <img id="blah2" class="avatar"
                                                        src="{{ URL::asset('/assets/img/profiles/avatar-10.jpg') }}"
                                                        alt="profile-img">
                                                </div>
                                                <div class="add-profile">
                                                    <h5>Upload a New Photo</h5>
                                                    <span>Profile-pic.jpg</span>
                                                </div>
                                            </div>
                                            <div class="img-upload">
                                                <label class="btn btn-primary">
                                                    Upload <input type="file">
                                                </label>
                                                <a class="btn btn-remove">Remove</a>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>First Name</label>
                                                    <input type="text" value="John" class="form-control"
                                                        placeholder="Enter First Name">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>Last Name</label>
                                                    <input type="text" class="form-control" value="Smith"
                                                        placeholder="Enter Last Name">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>User Name</label>
                                                    <input type="text" class="form-control" value="John Smith"
                                                        placeholder="Enter User Name">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>Email</label>
                                                    <input type="email" class="form-control"
                                                        value="john@example.com" placeholder="Enter Email Address">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>Phone Number</label>
                                                    <input type="text" class="form-control"
                                                        value="+1 989-438-3131" placeholder="Enter Phone Number"
                                                        name="name">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>Role</label>
                                                    <select class="select">
                                                        <option>Select Role</option>
                                                        <option selected>Role 1</option>
                                                        <option>Role 2</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="pass-group" id="passwordInput1">
                                                    <div class="input-block">
                                                        <label>Password</label>
                                                        <input type="password" class="form-control pass-input"
                                                            value="12345678" placeholder="">
                                                        <span class="toggle-password feather-eye"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="pass-group" id="passwordInput4">
                                                    <div class="input-block">
                                                        <label>Confirm Password</label>
                                                        <input type="password" class="form-control pass-input"
                                                            value="12345678" placeholder="">
                                                        <span class="toggle-password feather-eye"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block ">
                                                    <label>Status</label>
                                                    <select class="select">
                                                        <option>Select Status</option>
                                                        <option selected>Active</option>
                                                        <option>Inactive</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-primary paid-cancel-btn me-2">Cancel</button>
                        <button type="submit" data-bs-dismiss="modal"
                            class="btn btn-primary paid-continue-btn">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Edit User -->
@endif
