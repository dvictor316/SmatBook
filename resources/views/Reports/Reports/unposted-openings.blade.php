@if($openings->isNotEmpty())
    <div class="alert alert-warning mt-3">
        <strong>Opening balances awaiting review</strong>
        <p class="mb-1">These saved amounts will enter the report totals when an opening journal is posted.</p>
        <ul class="mb-0">
            @foreach($openings as $opening)
                <li>{{ $opening['code'] }} · {{ $opening['name'] }} (account #{{ $opening['id'] }}) · {{ $formatOpening($opening['amount']) }}</li>
            @endforeach
        </ul>
    </div>
@endif
