<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CryptoSeeder extends Seeder
{
    /**ß
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('cryptos')->insert([
            [
                'cmc_id' => 1,
                'symbol' => 'BTC',
                'name' => 'Bitcoin'
            ],
            [
                'cmc_id' => 1027,
                'symbol' => 'ETH',
                'name' => 'Ethereum'
            ],
            [
                'cmc_id' => 1839,
                'symbol' => 'BNB',
                'name' => 'BNB'
            ]
        ]);
    }
}
