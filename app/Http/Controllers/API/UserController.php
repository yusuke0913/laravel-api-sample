<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class UserController extends Controller
{

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function save(Request $request)
    {
        $param = json_decode($request->getContent(), true);
        $userId = (int) $param['UserId'];
        $data = $param['Data'];

	DB::transaction(function () use ($userId, $data){
		$user = DB::table('users')
              		->where('user_id', $userId)
			->lockForUpdate()
                	->get()
                	->first();

		if ($user !== null) {
			$previousData = json_decode($user->data, true);
			foreach($previousData as $k => $v) {
				if (!isset($data[$k])) {
					$data[$k] = $v;
				}
			}
        		$dataJson = json_encode($data);
			DB::table('users')->where('user_id', $userId)->update(['user_id' => $userId, 'data' => $dataJson, ]);
		} else {
        		$dataJson = json_encode($data);
			DB::table('users')->insert(
				[
					[
						'user_id' => $userId,
						'data' => $dataJson,
					]
				]
			);
		}
        });

	return response()->json([
                'Success' => true,
        ]);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function load(Request $request)
    {
        $param = json_decode($request->getContent(), true);
        $userId = (int) $param['UserId'];

	$result = DB::table('users')
                ->select(DB::raw('data'))
                ->where('user_id', $userId)
                ->get()
                ->first();

	$data = [];
	if ($result !== null) {
		$data = json_decode($result->data, true);
	}
        return response()->json($data);
    }
}
