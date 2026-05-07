<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add "Bank" payment type if it doesn't already exist
        $exists = DB::table('paymenttypes')
            ->whereRaw('LOWER(paymentTypeName) = ?', ['bank'])
            ->exists();

        if (!$exists) {
            DB::table('paymenttypes')->insert([
                'paymentTypeName' => 'Bank',
                'status'          => 'Active',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('paymenttypes')
            ->whereRaw('LOWER(paymentTypeName) = ?', ['bank'])
            ->delete();
    }
};
