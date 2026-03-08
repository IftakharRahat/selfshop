<?php
$ids = [1,3,4,5,6,7];
foreach($ids as $id) {
    $order = App\Models\Order::find($id);
    if($order) {
        $inv = $order->invoiceID;
        App\Models\Orderproduct::where('order_id', $id)->delete();
        App\Models\Customer::where('order_id', $id)->delete();
        $order->delete();
        echo "Deleted order $inv (ID: $id)\n";
    }
}
echo "Cleanup complete.\n";
