<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OrderExport implements FromQuery, WithHeadings,WithMapping
{




    use Exportable;
    private $status;

    public function __construct($status)
    {
        $this->status = $status;
    }


    public function map($order): array
    {
        if(isset($order->cities) && isset($order->zones)){
            return [
                "Marchent ID",
                $order->invoiceID,
                "standard",
                "regular",
                implode(', ', $order->orderproducts->pluck('productName')->toArray()),
                $order->subTotal,
                $order->customers->customerName,
                $order->customers->customerAddress,
                $order->cities->cityName,
                $order->zones->zoneName,
                $order->customers->customerPhone,

            ];
        }elseif(isset($order->cities) && empty($order->zones)){
            return [
                "Marchent ID",
                $order->invoiceID,
                "standard",
                "regular",
                implode(', ', $order->orderproducts->pluck('productName')->toArray()),
                $order->subTotal,
                $order->customers->customerName,
                $order->customers->customerAddress,
                $order->cities->cityName,
                '',
                $order->customers->customerPhone,
            ];
        }else{
            return [
                "Marchent ID",
                $order->invoiceID,
                "standard",
                "regular",
                implode(', ', $order->orderproducts->pluck('productName')->toArray()),
                $order->subTotal,
                $order->customers->customerName,
                $order->customers->customerAddress,
                '',
                '',
                $order->customers->customerPhone,

            ];
        }
    }

    public function query()
    {
        $status=$this->status;
        return Order::with(['orderproducts', 'customers', 'cities', 'zones'])->where('status', $status);

    }


    public function headings(): array
    {
        return ["Merchant Code", "Merchant order reference", "Package option", "Delivery option", "Product breif", "Product Price", "Customer Name", "Customer Adress", "Customer districe name", "Customer Thana name", "Customer phone number"];

    }



}