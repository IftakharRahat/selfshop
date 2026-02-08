<?php

namespace App\Imports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\Hash;
use App\Models\Resellerinvoice;

class ActiveuserImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $olduser =User::where('email',$row[0])->first();

        if(isset($olduser)){
            $invoice=Resellerinvoice::where('user_id',$olduser->id)->first();
            if(isset($invoice)){
                return;
            }else{
                $invoice =new Resellerinvoice();
                $invoice->invoiceID=$this->uniqueinvoiceID();
                $invoice->user_id=$olduser->id;
                $invoice->package_id=1;
                $invoice->resellerid=$olduser->my_referral_code;
                $invoice->amount=999;
                $invoice->payable_amount=999;
                $invoice->invoiceDate=date('Y-m-d');
                $invoice->paymentDate=date('Y-m-d');
                $invoice->expire_date=date('d-m-Y', strtotime('+1 year'));
                $invoice->status='Paid';
                $invoice->save();
                $olduser->status='Active';
                $olduser->membership_status='Paid';
                $olduser->update();
            }
            return;
        }else{
            return;
        }

    }

    public function uniqueinvoiceID()
    {
        $lastOrder = Resellerinvoice::latest('id')->first();
        if ($lastOrder) {
            $orderID = $lastOrder->id + 1;
        } else {
            $orderID = 1;
        }

        return 'SSINV' . $orderID;
    }

}
