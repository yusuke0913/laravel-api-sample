<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class LeaderBoardController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function scorePost(Request $request)
    {
	$param = json_decode($request->getContent(), true);
        $userId = (int) $param['UserId'];
        $score = (int) $param['Score'];
        $leaderBoardId = (int) $param['LeaderBoardId'];
	
	$highScore = $score;
	DB::transaction(function () use ($userId, $leaderBoardId, $score, $highScore){
                $leaderBoard = DB::table('leader_boards')
                        ->where('user_id', $userId)
                        ->where('leader_board_id', $userId)
                        ->lockForUpdate()
                        ->get()
                        ->first();

                if ($leaderBoard !== null) {
			$prevScore = $leaderBoard->score;
			if ($prevScore > $highScore) {
				$highScore = $prevScore;
                        } else {
                        	DB::table('leader_boards')
					->where('user_id', $userId)
					->where('leader_board_id', $leaderBoardId)	
					->update(['score' => $highScore]);
			}
                } else {
                        DB::table('leader_boards')->insert(
                                [
                                        [
                                                'user_id' => $userId,
						'leader_board_id' => $leaderBoardId,
						'score' => $score,
                                        ]
                                ]
                        );
                }
        });

	$myRank = $this->getUserRank($userId, $leaderBoardId, $highScore);		

        //
	return response()->json([
		'UserId' => $userId,
		'LeaderboardId' => $leaderBoardId,
		'Score' => $highScore,
		'Rank' => $myRank,
		]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function get(Request $request)
    {
	$param = json_decode($request->getContent(), true);
        $userId = (int) $param['UserId'];
        $leaderBoardId = (int) $param['LeaderBoardId'];
        $offset = (int) $param['Offset'];
        $limit = (int) $param['Limit'];

        $result = DB::table('leader_boards')
                ->select(DB::raw('user_id, score'))
                ->where('leader_board_id', $leaderBoardId)
		->orderBy('score', 'DESC')
		->offset($offset)
		->limit($limit)
                ->get();

	$entries = [];
	$rank = $offset;
	
	$myScore = 0;
	$myRank = 0;
	foreach($result as $row) {
		$rank ++;
		$entries[] = [
				'UserId' => $row->user_id,
				'Score' => $row->score,
				'Rank' => $rank,
			];
		if ($row->user_id == $userId) {
			$myScore = $row->score;
			$myRank = $rank;
		}
	}

	if ($myScore <= 0 || $myRank <= 0) {
	//if (true) {
		$user = DB::table('leader_boards')
                	->select(DB::raw('score'))
                	->where('user_id', $userId)
                	->where('leader_board_id', $leaderBoardId)
			->get()
			->first();

		if ($user !== null) {
			$myScore = $user->score;
			$myRank = $this->getUserRank($userId, $leaderBoardId, $myScore);		
		}
	}

	return response()->json([
		'UserId' => $userId,
		'LeaderboardId' => $leaderBoardId,
		'Score' => $myScore,
		'Rank' => $myRank,
		'Entries' => $entries,
		]);
    }

    private function getUserRank($userId, $leaderBoardId, $score) {
	$rank = DB::table('leader_boards')
                ->where('leader_board_id', $leaderBoardId)
		->where('score', '>', $score)
		->where('user_id', '!=', $userId)
		->count();
	return $rank + 1;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
