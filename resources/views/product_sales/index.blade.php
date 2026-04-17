
<h1>Sales for {{ $product->name }}</h1>

<table>
    <thead>
        <tr>
            <th>Quantity</th>
            <th>Price</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        @foreach($sales as $sale)
            <tr>
                <td>{{ $sale->quantity }}</td>
                <td>{{ $sale->price }}</td>
                <td>{{ $sale->created_at }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
