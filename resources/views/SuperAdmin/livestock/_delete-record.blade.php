<form method="POST" action="{{ route('super_admin.livestock.records.destroy', [$type, $id]) }}" onsubmit="return confirm('Remove this {{ $type }} record?')" class="d-inline">
    @csrf
    @method('DELETE')
    <button class="btn btn-sm btn-outline-danger" title="Delete {{ $type }} record"><i class="fas fa-trash"></i></button>
</form>
