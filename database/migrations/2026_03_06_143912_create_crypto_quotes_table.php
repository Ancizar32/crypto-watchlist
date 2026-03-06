<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCryptoQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crypto_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crypto_id');
            $table->decimal('price', 18, 8);
            $table->decimal('volume_24h', 18, 2)->nullable();
            $table->decimal('percent_change_24h', 8, 2)->nullable();
            $table->decimal('market_cap', 18, 2)->nullable();
            $table->timestamp('source_timestamp');
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
        Schema::dropIfExists('crypto_quotes');
    }
}
