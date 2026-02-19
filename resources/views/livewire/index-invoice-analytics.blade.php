<div>
    <h2>Invoice Analytics</h2>
    <p>Sent Estimates: {{ $sent }}</p>
    <p>Draft Estimates: {{ $draft }}</p>
    <p>Expired Estimates: {{ $expired }}</p>

    <h3>Recent Estimates</h3>
    @if($estimates->isEmpty())
        <p>No recent estimates found.</p>
    @else
        <ul>
            @foreach($estimates as $estimate)
                <li>
                    {{ $estimate->customer->name }} - {{ $estimate->created_at->format('Y-m-d') }}
                </li>
            @endforeach
        </ul>
    @endif
</div>