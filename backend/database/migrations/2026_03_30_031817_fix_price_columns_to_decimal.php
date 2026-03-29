<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class FixPriceColumnsToDecimal extends Migration
{
    /**
     * Fix integer price columns to decimal(10,2) so fractional prices
     * (e.g. 100.80) are stored accurately instead of being rounded.
     */
    public function up()
    {
        // orderproducts.productPrice was int(11) — caused 100.80 → 101
        DB::statement('ALTER TABLE orderproducts MODIFY COLUMN productPrice DECIMAL(10,2) DEFAULT 0');

        // orders.subTotal was int(11) — caused 403.20 → 404
        DB::statement('ALTER TABLE orders MODIFY COLUMN subTotal DECIMAL(10,2) DEFAULT 0');

        // orders.paymentAmount was int(11)
        DB::statement('ALTER TABLE orders MODIFY COLUMN paymentAmount DECIMAL(10,2) DEFAULT 0');
    }

    public function down()
    {
        DB::statement('ALTER TABLE orderproducts MODIFY COLUMN productPrice INT(11) DEFAULT 0');
        DB::statement('ALTER TABLE orders MODIFY COLUMN subTotal INT(11) DEFAULT 0');
        DB::statement('ALTER TABLE orders MODIFY COLUMN paymentAmount INT(11) DEFAULT 0');
    }
}
