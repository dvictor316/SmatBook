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
