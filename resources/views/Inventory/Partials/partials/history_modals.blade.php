<div class="modal fade" id="edit_history_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div class="form-header modal-header-title text-start mb-0">
                    <h4 class="mb-0">Edit Movement Record</h4>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ url('inventory-history/update') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="id" id="modal_edit_id">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group mb-3">
                                <label class="form-label">Adjustment Quantity</label>
                                <input type="number" name="quantity" id="modal_edit_qty" class="form-control" placeholder="Enter quantity" required>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group mb-3">
                                <label class="form-label">Movement Type</label>
                                <select name="type" id="modal_edit_type" class="form-select">
                                    <option value="in">Stock In</option>
                                    <option value="out">Stock Out</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-back cancel-btn me-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary paid-continue-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="delete_history_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="form-header">
                    <i class="feather-trash-2 text-danger mb-3" style="font-size: 40px;"></i>
                    <h3>Are you sure?</h3>
                    <p class="text-muted">This will delete the history log entry. It will not affect the current stock level.</p>
                </div>
                <form action="{{ url('inventory-history/delete') }}" method="POST">
                    @csrf
                    <input type="hidden" name="id" id="modal_delete_id">
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <button type="submit" class="btn btn-danger w-100">Delete</button>
                            </div>
                            <div class="col-6">
                                <button type="button" data-bs-dismiss="modal" class="btn btn-light w-100">Cancel</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>