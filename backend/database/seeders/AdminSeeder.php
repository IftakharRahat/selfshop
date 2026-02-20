<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $checkadmin =Admin::where('email','md.muraiem@gmail.com')->first();
        if(is_null($checkadmin)){
            $user = new Admin();
            $user->name= 'Abdullah Md. Muraiem(Super Admin)';
            $user->phone= '01928558628';
            $user->email= 'md.muraiem@gmail.com';
            $user->status= 'Active';
            $user->password= Hash::make('password');
            $user->save();
            if (Role::where('name', 'admin')->where('guard_name', 'admin')->exists()) {
                $user->assignRole('admin');
            }
        }

        $checkShorno = Admin::where('email', 'fb.shorno@gmail.com')->first();
        if (is_null($checkShorno)) {
            $shorno = new Admin();
            $shorno->name = 'Shorno';
            $shorno->phone = '01841151827';
            $shorno->email = 'fb.shorno@gmail.com';
            $shorno->status = 'Active';
            $shorno->password = Hash::make('shorno@admin1');
            $shorno->save();
            if (Role::where('name', 'admin')->where('guard_name', 'admin')->exists()) {
                $shorno->assignRole('admin');
            }
        }
    }
}
