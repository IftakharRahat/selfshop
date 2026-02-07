<?php

namespace App\Imports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\Hash;

class UserImport implements ToModel
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $phone = str_replace('-', '', $row[2]);
        $olduseremail = User::where('email', $phone)->first();

        if (isset($olduseremail)) {
            return;
        } else {
            $string = str_replace(' ', '', $row[0]);
            $code = substr($string, 0, 3);
            return new User([
                'name' => $row[0],
                'email' => $phone,
                'phone' => $phone,
                'address' => $row[1],
                'my_referral_code' => strtoupper($code) . $this->uniqueID(),
                'refer_by' => 'Admin123',
                'password' => Hash::make($phone),
            ]);
        }
    }

    public function uniqueID()
    {
        $lastReseller = User::latest('id')->first();
        if ($lastReseller) {
            $resellerID = $lastReseller->id + 1;
        } else {
            $resellerID = 1;
        }

        return 'SS00' . $resellerID;
    }
}
