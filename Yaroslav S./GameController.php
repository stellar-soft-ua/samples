<?php

namespace App\Http\Controllers;

use App\Services\Helper;
use Illuminate\Support\Facades\Validator;
use App\Repositories\Game;
use App\Repositories\Odds;
use App\Services\CacheHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use PhpParser\Node\Stmt\Case_;

class GameController extends Controller
{
    protected $game;

    protected $odds;

    public function __construct(Game $game, Odds $odds)
    {
        $this->game = $game;

        $this->odds = $odds;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getGames(Request $request)
    {
        $params = $request->all();

        $validator = Validator::make($params['data'], [
            'team_id' => [
                'integer',
                'required_with:home,away,opponent_id,team_odds,team_odds_from,team_odds_to,goals_result,goals_result_stats,team_goals,team_goals_stats,opponent_goals,opponent_goals_stats,match_intervals',
                'required_without:referee_id'
            ],
            'opponent_id' => ['integer'],
            'referee_id' => ['integer', 'required_without:team_id'],
            'stats' => ['boolean'],
            'events' => ['int'],
            'stats_id' => ['array'],
            'home' => ['integer'],
            'away' => ['integer'],
            'tournaments' => ['array'],
            'tournament_groups' => ['array'],
            'stages' => ['array'],
            'seasons' => ['array'],
            'period' => [
                function ($attribute, $value, $fail) {
                    if ($value != 'match' && $value != '1h' && $value != '2h' && $value != '1e' && $value != '2e') {
                        $fail('The '. $attribute.' must be one of follow values: match, 1h, 2h, 1e or 2e.');
                    }
                }
            ],
            'team_odds' => ['numeric'],
            'team_odds_from' => ['numeric'],
            'team_odds_to' => ['numeric'],
            'from_date' => ['date'],
            'to_date' => ['date'],
            'event_time_from' => ['integer', 'required_with:event_period_from'],
            'event_time_to' => ['integer', 'required_with:event_period_to'],
            'event_period_from' => ['required_with:event_time_from',
                function ($attribute, $value, $fail) {
                    if ($value != 'match' && $value != '1h' && $value != '2h' && $value != '1e' && $value != '2e') {
                        $fail('The '. $attribute.' must be one of follow values: match, 1h, 2h, 1e or 2e.');
                    }
                }
            ],
            'event_period_to' => ['required_with:event_time_to',
                function ($attribute, $value, $fail) {
                    if ($value != 'match' && $value != '1h' && $value != '2h' && $value != '1e' && $value != '2e') {
                        $fail('The '. $attribute.' must be one of follow values: match, 1h, 2h, 1e or 2e.');
                    }
                }
            ],
            'goals_result' => [
                function ($attribute, $value, $fail) {
                    if ($value != 'win' && $value != 'draw' && $value != 'lose') {
                        $fail('The '. $attribute.' must be one of follow values: win, draw or lose.');
                    }
                }
            ],
            'goals_result_stats' => [
                function ($attribute, $value, $fail) {
                    if ($value != 'win' && $value != 'draw' && $value != 'lose') {
                        $fail('The '. $attribute.' must be one of follow values: win, draw or lose.');
                    }
                }
            ],
            'team_goals' => ['numeric'],
            'team_goals_stats' => ['numeric'],
            'opponent_goals' => ['numeric'],
            'opponent_goals_stats' => ['numeric'],
            'match_intervals' => ['array'],
            'match_intervals.goals_diff' => ['integer'],
            'match_intervals.after_minute' => ['integer'],
            'match_intervals.red_card' => ['bool'],
            'match_intervals.red_card_opponent' => ['bool'],
        ]);

        if (!empty($validator->errors()->all())) {
            return response()->json([
                'message' => implode('. ', $validator->errors()->all()),
            ], Response::HTTP_BAD_REQUEST);
        }

        $games = $this->game->all($params['data']);

        return response()->json([
            'message' => __FUNCTION__,
            'access' => $params['access'] ?? 'default',
            'result' => $games
        ], Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @param $bookmaker
     * @return JsonResponse
     */
    public function getOdds(Request $request, $bookmaker)
    {
        $params = $request->all();

        $params['data']['bookmaker'] = $bookmaker;

        $validator = Validator::make($params['data'], [
            'bookmaker' => ['required'],
            'game_id' => ['required', 'integer'],
        ]);

        if (!empty($validator->errors()->all())) {
            return response()->json([
                'message' => implode('. ', $validator->errors()->all()),
            ], Response::HTTP_BAD_REQUEST);
        }

        if(empty($params['data']['no_cache'])) {
            $odds = CacheHelper::remember('game_odds_' . $params['data']['game_id'], config('api.cache.ttl.odds'), function () use ($params) {
                return $this->odds->game($params['data']);
            });
        } else {
            CacheHelper::forget('game_odds_' . $params['data']['game_id']);

            $odds = $this->odds->game($params['data']);
        }

        return response()->json([
            'message' => __FUNCTION__,
            'result' => $odds
        ], Response::HTTP_OK);
    }
}
