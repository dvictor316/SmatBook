<div class="card shadow-sm h-100">
    <div class="card-header bg-white border-0">
        <h6 class="mb-0 text-uppercase fw-bold text-primary">Global Distribution</h6>
    </div>
    <div class="card-body">
        <div id="world-map" style="height: 300px;"></div>
        <table class="table table-sm mt-3">
            @foreach($countryHeatMap as $country => $count)
            <tr>
                <td class="small">{{ $country ?: 'Unknown' }}</td>
                <td class="text-end fw-bold">{{ $count }}</td>
            </tr>
            @endforeach
        </table>
    </div>
</div>