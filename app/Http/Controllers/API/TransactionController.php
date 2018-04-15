<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class TransactionController extends Controller
{

    const VERIFIER_SECRET_KEY = 'NwvprhfBkGuPJnjJp77UPJWJUpgC7mLz';

    /**
     *
     * @return \Illuminate\Http\Response
     */
    public function record(Request $request)
    {
	$param = json_decode($request->getContent(), true);
	$transactionId = (int) $param['TransactionId'];
	$userId = (int) $param['UserId'];
	$currencyAmount = (int) $param['CurrencyAmount'];
	$verifier = $param['Verifier'];

	// Secret Key, Transaction ID, UserId, CurrencyAmount
	$sha1 = sha1(self::VERIFIER_SECRET_KEY."${transactionId}${userId}${currencyAmount}");

	if ( $sha1 !== $verifier) {
		Log::debug("INVALID_VERIFIER	$sha1	$verifier");
        	return response()->json([
			'Error' => true,
			'ErrorMessage' => 'INVALID_PARAMETER',
		]);
	}

	$prevUserTransaction = DB::table('transactions')->where('transaction_id', $transactionId)->get()->first();
	if (!empty($prevUserTransaction)) {
        	return response()->json([
			'Error' => true,
			'ErrorMessage' => 'DPULICATE_TRANSACTION_ID',
		]);
	}

	DB::transaction(function () use($userId, $transactionId, $currencyAmount){

		$user = DB::table('users')
                        ->where('user_id', $userId)
                        ->lockForUpdate()
                        ->get()
                        ->first();

		$transactionCount = 1;
		if ($user  !== null) {
			$transactionCount = $user->transactionCount + 1;
			$currency = $user->currency + $currencyAmount;
			DB::table('users')->where('user_id', $userId)->update(['user_id' => $userId, 'currency' => $currency, 'transactionCount' => $transactionCount, ]);
		} else {
			DB::table('users')->insert(
                                [
                                        [
                                                'user_id' => $userId,
						'currency' => $currencyAmount,
						'transactionCount' => $transactionCount,
                                        ]
                                ]
                        );
		}

		DB::table('transactions')->insert(
			[['user_id' => $userId, 'transaction_id' => $transactionId, 'currency' => $currencyAmount]]
		);
	});

        return response()->json([
		'Success' => true,
	]);
    }

    /**
     *
     * @return \Illuminate\Http\Response
     */
    public function stats(Request $request)
    {
	$param = json_decode($request->getContent(), true);
	$userId = (int) $param['UserId'];


        $user = DB::table('users')
                ->select(DB::raw('currency, transactionCount'))
                ->where('user_id', $userId)
                ->get()
                ->first();

	$currencySum = 0;
	$transactionCount = 0;

	if ($user !== null) {
		$currencySum = (int) $user->currency;
		$transactionCount = (int) $user->transactionCount;
	}
		
        return response()->json([
		'UserId' => $userId,
		'TransactionCount' => $transactionCount,
		'CurrencySum' => $currencySum,

	]);
    }
}
