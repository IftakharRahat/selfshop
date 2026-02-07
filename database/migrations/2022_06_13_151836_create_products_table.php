<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('category_id')->unsigned();
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->bigInteger('subcategory_id')->unsigned();
            $table->foreign('subcategory_id')->references('id')->on('subcategories')->onDelete('cascade');
            $table->bigInteger('brand_id')->unsigned();
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('cascade');

            $table->string('ProductName');
            $table->string('ProductSlug');
            $table->decimal('product_weight',10,2)->default(0);
            $table->integer('minimum_qty')->default(1);
            $table->string('ProductImage')->default('public/images/product/default.jpg');
            $table->string('ViewProductImage')->default('public/images/product/default.jpg');
            $table->longText('PostImage')->nullable();
            $table->string('youtube_link')->nullable();
            // attributes
            $table->text('color')->nullable();
            $table->text('size')->nullable();
            $table->text('weight')->nullable();

            $table->longText('ProductBreaf')->nullable();
            $table->longText('ProductDetails')->nullable();

            // meta
            $table->string('MetaTitle')->nullable();
            $table->string('MetaKey')->nullable();
            $table->text('MetaDescription')->nullable();
            $table->text('meta_image')->nullable();
            //pricing
            $table->decimal('ProductWholesalePrice',10,2)->default(0);
            $table->decimal('ProductResellerPrice',10,2)->default(0);
            $table->decimal('ProductRegularPrice',10,2)->default(0);
            $table->decimal('ProductSalePrice',10,2)->default(0);
            $table->string('Discount')->default(0);
            $table->decimal('min_sell_price',10,2)->default(0);

            $table->string('ProductSku');
            $table->integer('qty')->default(0);
            $table->integer('low_stock')->default(0);
            $table->string('show_stock')->default('Off');
            $table->string('show_stock_text')->default('Off');
            $table->string('show_new_product')->default('Off');
            $table->string('shipping_days')->nullable();

            $table->decimal('ex_pack',10,2)->default(0);
            $table->decimal('ex_dvc',10,2)->default(0);

            $table->string('mart_status')->default('Off');
            $table->string('reseller_status')->default('Off');
            $table->decimal('reseller_bonus',10,2)->default(0);


            $table->string('status')->default('Active');
            $table->tinyInteger('event')->default(1);
            $table->tinyInteger('frature')->default('0');
            $table->tinyInteger('top_rated')->default('0');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
}
