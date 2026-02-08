<div class="table-container">
    <table class="table">
        <thead>
            <tr>
                <th>Courier</th>
                <th>Total</th>
                <th>Delivered</th>
                <th>Returned</th>
                <th>Success Ratio</th>
            </tr>
        </thead>
        <tbody>

                @foreach($response->courierData as  $ind=>$spon)
                    <tr class="delivered-row">
                        <td style="text-transform:uppercase">{{$ind}}</td>
                        <td>{{$spon->total_parcel}}</td>
                        <td>{{$spon->success_parcel}}</td>
                        <td>{{$spon->cancelled_parcel}}</td>
                        <td>{{$spon->success_ratio}}%</td>
                    </tr>
                @endforeach


        </tbody>
    </table>
</div>
