<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Crypto;
use App\Models\CryptoQuote;
use Illuminate\Support\Facades\Cache;

class CryptoController extends Controller
{

    public function quotes()
    {

        return Cache::remember('crypto_quotes', 30, function () {
            $cryptos = Crypto::all();

            if ($cryptos->isEmpty()) {
                return [];
            }

            $ids = $cryptos->pluck('cmc_id')->implode(',');

            $response = Http::withHeaders([
                'X-CMC_PRO_API_KEY' => env('COINMARKETCAP_API_KEY')
            ])->get('https://pro-api.coinmarketcap.com/v1/cryptocurrency/quotes/latest', [
                'id' => $ids
            ]);

            $data = $response->json()['data'];

            $result = [];

            foreach ($data as $item) {

                $crypto = Crypto::where('cmc_id', $item['id'])->first();

                $quote = $item['quote']['USD'];

                CryptoQuote::create([
                    'crypto_id' => $crypto->id,
                    'price' => $quote['price'],
                    'volume_24h' => $quote['volume_24h'],
                    'percent_change_24h' => $quote['percent_change_24h'],
                    'market_cap' => $quote['market_cap'],
                    'source_timestamp' => now()
                ]);

                $result[] = [
                    'id' => $crypto->id,
                    'symbol' => $item['symbol'],
                    'name' => $item['name'],
                    'price' => $quote['price'],
                    'change' => $quote['percent_change_24h'],
                    'market_cap' => $quote['market_cap'],
                    'volume' => $quote['volume_24h']
                ];
            }

            return $result;
        });
    }

    public function history(Request $req, $id)
    {
        $range = $req->range ?? '24h';

        $days = [
            '24h' => 1,
            '7d' => 7,
            '30d' => 30
        ];

        return CryptoQuote::where('crypto_id', $id)
            ->where('source_timestamp', '>=', now()->subDays($days[$range]))
            ->orderBy('source_timestamp')
            ->get();
    }

    public function search(Request $request)
    {

        $q = strtolower($request->q);

        $response = Http::withHeaders([
            'X-CMC_PRO_API_KEY' => env('COINMARKETCAP_API_KEY')
        ])->get('https://pro-api.coinmarketcap.com/v1/cryptocurrency/map');

        $data = collect($response->json()['data'])
            ->filter(function ($c) use ($q) {
                return str_contains(strtolower($c['symbol']), $q)
                    || str_contains(strtolower($c['name']), $q);
            })
            ->take(10)
            ->values();

        return $data;
    }

    public function add(Request $request)
    {
        $symbol = strtoupper($request->symbol);

        $crypto = Crypto::firstOrCreate(
            ['cmc_id' => $request->cmc_id],
            [
                'symbol' => $request->symbol,
                'name' => $request->name
            ]
        );
        Cache::forget('crypto_quotes');
        return response()->json($crypto);
    }

    public function delete($id)
    {
        Crypto::destroy($id);
        Cache::forget('crypto_quotes');
        return response()->json([
            'status' => 'deleted'
        ]);
    }
}
