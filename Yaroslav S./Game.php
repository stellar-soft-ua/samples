<?php

namespace App\Repositories;

use App\Services\CacheHelper;
use App\Services\Helper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class Game
{
    protected $events;

    public $stats_options;

    public $event_types;

    public $event_types_opposite;

    public $team;

    public $referee;

    function __construct(Events $events, Team $team, Referee $referee)
    {
        $this->events = $events;

        $this->stats_options = $this->events->statsOptions();

        $this->event_types = $this->events->eventTypes();

        $this->event_types_opposite = $this->events->eventTypesOpposite();

        $this->team = $team;

        $this->referee = $referee;
    }

    /**
     * @param array $params
     * @return array
     */
    public function all($params = [])
    {
        $lang_sufix = $params['lang'] == 'ru' ? '_ru' : '';

        $select = [
            'game.game_id',
            'game.game_date_start AS gds',
            'game.tournament_id AS tid',
            'game.anonse AS st',
            'game.season_id AS sid',
            'season.short_season_name AS sn',
            'game.round_id AS rid',
            'round.round_short AS rn',
            'game.team_1_id AS t1id',
            'game.team_2_id AS t2id',

            'team_1.name AS t1n',
            'team_2.name AS t2n',
            'team_1.name AS t1_name',
            'team_1.alt_name AS t1_alt_name',
            'team_1.name_ru AS t1_name_ru',
            'team_2.name AS t2_name',
            'team_2.alt_name AS t2_alt_name',
            'team_2.name_ru AS t2_name_ru',

            'game.sec_added_1h',
            'game.sec_added_2h',

            'tournament.tournament_group as tgid',
            'tournament.name' . $lang_sufix . ' AS tn',
            'tournament.shortname AS tns',
            'tournament_groups.name AS tgn',

            'oth_1', 'oth_2', 'oth_3', 'oth_4', 'oth_5', 'oth_6', 'oth_7', 'oth_8',
            'oth_12', 'oth_14', 'oth_15', 'oth_16', 'oth_17', 'oth_18', 'oth_19', 'oth_20',
            'oth_21', 'oth_22', 'oth_23', 'oth_24', 'oth_25', 'oth_26', 'oth_27', 'oth_28', 'oth_29', 'oth_30',
            'oth_31', 'oth_32', 'oth_33'
        ];

        if (!empty($params['additional_fields']) && in_array('all', $params['additional_fields'])) {
            $select = array_merge($select, [
                'tournament.alias AS tl',
                'team_1.alias AS t1l',
                'team_2.alias AS t2l',
                'game.alias AS gl',

                'game.stage_id AS stid',
                'stage.name AS stn',

                'coach_t1_id',
                'coach_1.name AS coach_t1_name',
                'coach_t2_id',
                'coach_2.name AS coach_t2_name',

                'game.odds_1 AS o1',
                'game.odds_x AS ox',
                'game.odds_2 AS o2',

                'game_weather.temperature_current AS wt',
                'game_weather.pressure_current AS wp',
                'game_weather.wind_speed_current AS ww',
                'game_weather.rain_current AS wr',

                'referee.alias AS rfl',
                'game.referee_id AS rfid',
                'referee.name' . $lang_sufix . ' AS rfn',
            ]);
        }

        if (!empty($params['additional_fields']) && in_array('link', $params['additional_fields'])) {
            $select = array_merge($select, [
                'tournament.alias AS tl',
                'team_1.alias AS t1l',
                'team_2.alias AS t2l',
                'game.alias AS gl',
            ]);
        }

        if (!empty($params['additional_fields']) && in_array('coach', $params['additional_fields'])) {
            $select = array_merge($select, [
                'coach_t1_id',
                'coach_1.name AS coach_t1_name',
                'coach_t2_id',
                'coach_2.name AS coach_t2_name',
            ]);
        }

        if (!empty($params['additional_fields']) && in_array('odds', $params['additional_fields'])) {
            $select = array_merge($select, [
                'game.odds_1 AS o1',
                'game.odds_x AS ox',
                'game.odds_2 AS o2',
            ]);
        }

        if (!empty($params['additional_fields']) && in_array('stage', $params['additional_fields'])) {
            $select = array_merge($select, [
                'game.stage_id AS stid',
                'stage.name AS stn',
            ]);
        }

        if ((!empty($params['additional_fields']) && in_array('referee', $params['additional_fields']))
            || !empty($params['referee_id'])
        ) {
            $select = array_merge($select, [
                'referee.alias AS rfl',
                'game.referee_id AS rfid',
                'referee.name' . $lang_sufix . ' AS rfn',
            ]);
        }

        if (!empty($params['additional_fields']) && in_array('weather', $params['additional_fields'])) {
            $select = array_merge($select, [
                'game_weather.temperature_current AS wt',
                'game_weather.pressure_current AS wp',
                'game_weather.wind_speed_current AS ww',
                'game_weather.rain_current AS wr',
            ]);
        }

        $items = DB::table('game')->select($select);

        $items = $items
            ->leftJoin('new_teams AS team_1', 'game.team_1_id', '=', 'team_1.team_id')
            ->leftJoin('new_teams AS team_2', 'game.team_2_id', '=', 'team_2.team_id')
            ->leftJoin('new_tournaments AS tournament', 'game.tournament_id', '=', 'tournament.tournament_id')
            ->leftJoin('tournament_groups', 'tournament.tournament_group', '=', 'tournament_groups.tr_group_id')
            ->leftJoin('season', 'game.season_id', '=', 'season.season_id')
            ->leftJoin('round', 'game.round_id', '=', 'round.round_id');

//        if(!empty($params['additional_fields']) && in_array('stage', $params['additional_fields'])) {
        $items = $items->leftJoin('new_stages AS stage', 'game.stage_id', '=', 'stage.stage_id');
//        }

//        if(!empty($params['additional_fields']) && in_array('referee', $params['additional_fields'])) {
        $items = $items->leftJoin('new_referee AS referee', 'game.referee_id', '=', 'referee.referee_id');
//        }

//        if(!empty($params['additional_fields']) && in_array('weather', $params['additional_fields'])) {
        $items = $items->leftJoin('game_weather', 'game.game_id', '=', 'game_weather.game_id');
//        }

//        if(!empty($params['additional_fields']) && in_array('coach', $params['additional_fields'])) {
        $items = $items->leftJoin('new_coach AS coach_1', 'game.coach_t1_id', '=', 'coach_1.coach_id')
            ->leftJoin('new_coach AS coach_2', 'game.coach_t2_id', '=', 'coach_2.coach_id');
//        }

        $items = $items->where(function ($items) use ($params) {

            if (!empty($params['team_goals'])) {
                $items = $items->where(function ($query) use ($params) {
                    $query->where(function ($q) use ($params) {
                        $q->where('game.team_1_goals_quantity', $params['team_goals'])->where('game.team_1_id', $params['team_id']);
                    })->orWhere(function ($q) use ($params) {
                        $q->where('game.team_2_goals_quantity', $params['team_goals'])->where('game.team_2_id', $params['team_id']);
                    });
                });
            }

            if (!empty($params['opponent_goals'])) {
                $items = $items->where(function ($query) use ($params) {
                    $query->where(function ($q) use ($params) {
                        $q->where('game.team_1_goals_quantity', $params['opponent_goals'])->where('game.team_1_id', '<>', $params['team_id']);
                    })->orWhere(function ($q) use ($params) {
                        $q->where('game.team_2_goals_quantity', $params['opponent_goals'])->where('game.team_2_id', '<>', $params['team_id']);
                    });
                });
            }

            if (!empty($params['goals_difference'])) {
                preg_match('~=|>|<~', $params['goals_difference'], $operator_match);

                $operator = $operator_match[0] ?? '=';

                preg_match('~\-|\+~', $params['goals_difference'], $direction_match);

                $direction = $direction_match[0] ?? '+';

                preg_match('~\d+~', $params['goals_difference'], $value_match);

                $value = $value_match[0] ?? 0;

                $items = $items->where(function ($query) use ($params, $operator, $value, $direction) {
                    $query->where(function ($q) use ($params, $operator, $value, $direction) {

                        $direction = $direction == '+' ? '>=' : '<=';

                        $q->whereRaw('(ABS(game.team_1_goals_quantity - game.team_2_goals_quantity)) ' . $operator . ' ?', [$value])
                            ->whereRaw('game.team_1_goals_quantity ' . $direction . ' game.team_2_goals_quantity')
                            ->where('game.team_1_id', $params['team_id']);

                    })->orWhere(function ($q) use ($params, $operator, $value, $direction) {

                        $direction = $direction == '+' ? '<=' : '>=';

                        $q->whereRaw('(game.team_2_goals_quantity - game.team_1_goals_quantity) ' . $operator . ' ?', [$value])
                            ->whereRaw('game.team_1_goals_quantity ' . $direction . ' game.team_2_goals_quantity')
                            ->where('game.team_2_id', $params['team_id']);
                    });
                });
            }

            if (!empty($params['tournaments'])) $items = $items->whereIn('game.tournament_id', $params['tournaments']);

            if (!empty($params['tournament_groups'])) $items = $items->whereIn('tournament.tournament_group', $params['tournament_groups']);

            if (!empty($params['stages'])) $items = $items->where('stage_id', $params['stages']);

            if (!empty($params['seasons'])) $items = $items->whereIn('game.season_id', $params['seasons']);

            if (!empty($params['round_id'])) $items = $items->where('game.round_id', $params['round_id']);

            if (!empty($params['anonse']) || $params['anonse'] === '0') $items = $items->where('game.anonse', $params['anonse']);

            /**
             * Select where referee_id
             */
            if (!empty($params['referee_id'])) {
                $items = $items->where('game.referee_id', $params['referee_id']);
            }

            /**
             * Select where team_id
             */
            if (!empty($params['team_id'])) $items = $items->where(function ($query) use ($params) {

                if (!empty($params['opponent_id'])) {
                    $query->orWhere(function ($query) use ($params) {
                        $query->where('game.team_1_id', $params['team_id']);
                        $query->where('game.team_2_id', $params['opponent_id']);
                    });
                    $query->orWhere(function ($query) use ($params) {
                        $query->where('game.team_1_id', $params['opponent_id']);
                        $query->where('game.team_2_id', $params['team_id']);
                    });
                } else {
                    $query->orWhere('game.team_1_id', $params['team_id'])->orWhere('game.team_2_id', $params['team_id']);
                }
            });

            if (!empty($params['country_id'])) {
                $items = $items->whereIn('team_1.country_id', $params['country_id']);
                $items = $items->whereIn('team_2.country_id', $params['country_id']);
            }

            if (!empty($params['home'])) $items = $items->where('team_1_id', $params['home']);

            if (!empty($params['away'])) $items = $items->where('team_2_id', $params['away']);

            if (!empty($params['coach_id'])) {
                $items = $items->where(function ($query) use ($params) {
                    $query->where(function ($q) use ($params) {
                        $q->whereIn('coach_t1_id', $params['coach_id'])->where('game.team_1_id', $params['team_id']);
                    })->orWhere(function ($q) use ($params) {
                        $q->whereIn('coach_t2_id', $params['coach_id'])->where('game.team_2_id', $params['team_id']);
                    });
                });
            }

            if (!empty($params['from_date'])) $items = $items->whereDate('game_date_start', '>=', $params['from_date']);

            if (!empty($params['to_date'])) $items = $items->whereDate('game_date_start', '<=', $params['to_date']);

            if (!empty($params['weather']['temperature_from'])) $items = $items->where('temperature_current', '>=', $params['weather']['temperature_from']);
            if (!empty($params['weather']['temperature_to'])) $items = $items->where('temperature_current', '<=', $params['weather']['temperature_to']);

            if (!empty($params['weather']['rain_from'])) $items = $items->where('rain_current', '>=', $params['weather']['rain_from']);
            if (!empty($params['weather']['rain_to'])) $items = $items->where('rain_current', '<=', $params['weather']['rain_to']);

            if (!empty($params['weather']['wind_speed_from'])) $items = $items->where('wind_speed_current', '>=', $params['weather']['wind_speed_from']);
            if (!empty($params['weather']['wind_speed_to'])) $items = $items->where('wind_speed_current', '<=', $params['weather']['wind_speed_to']);

            if (!empty($params['weather']['pressure_from'])) $items = $items->where('pressure_current', '>=', $params['weather']['pressure_from']);
            if (!empty($params['weather']['pressure_to'])) $items = $items->where('pressure_current', '<=', $params['weather']['pressure_to']);

            if (!empty($params['team_odds'])) {
                $odds_from = $params['team_odds'] - ($params['team_odds'] * 0.1);

                $odds_to = $params['team_odds'] + ($params['team_odds'] * 0.1);

                $items->where(function ($query) use ($params, $odds_from, $odds_to) {
                    $query->where([
                        ['game.team_1_id', '=', $params['team_id']],
                        ['game.odds_1', '>=', $odds_from],
                        ['game.odds_1', '<=', $odds_to],
                    ]);
                    $query->orWhere([
                        ['game.team_2_id', '=', $params['team_id']],
                        ['game.odds_2', '>=', $odds_from],
                        ['game.odds_2', '<=', $odds_to],
                    ]);
                });
            }

            if (!empty($params['team_odds_from'])) {
                $odds_from = $params['team_odds_from'];

                $items->where(function ($query) use ($params, $odds_from) {
                    $query->where([
                        ['game.team_1_id', '=', $params['team_id']],
                        ['game.odds_1', '>=', $odds_from],
                    ]);
                    $query->orWhere([
                        ['game.team_2_id', '=', $params['team_id']],
                        ['game.odds_2', '>=', $odds_from],
                    ]);
                });
            }

            if (!empty($params['team_odds_to'])) {
                $odds_to = $params['team_odds_to'];

                $items->where(function ($query) use ($params, $odds_to) {
                    $query->where([
                        ['game.team_1_id', '=', $params['team_id']],
                        ['game.odds_1', '<=', $odds_to],
                    ]);
                    $query->orWhere([
                        ['game.team_2_id', '=', $params['team_id']],
                        ['game.odds_2', '<=', $odds_to],
                    ]);
                });
            }
        });

        if (!empty($params['game_ids'])) $items = $items->orWhereIn('game.game_id', $params['game_ids']);

        if (!empty($params['limit'])) $items = $items->limit($params['limit']);

        $items = $items->orderByDesc('game.game_date_start');

        /**
         * Get games
         */
        if (!empty($params['no_cache'])) {
            $items = $items->get();
        } else {
            $cache_key = 'games_' . md5(http_build_query($params));

            $items = CacheHelper::remember($cache_key, config('api.cache.ttl.games'), function () use ($params, $items) {
                return $items = $items->get();
            });
        }

        $items = $items->keyBy('game_id')->toArray();

        $items = Helper::toArray($items);

        /**
         * Format coaches
         */
        if (!empty($params['additional_fields']) && (in_array('coach', $params['additional_fields']) || in_array('all', $params['additional_fields']))) foreach ($items as $key => $item) {
            if ($item['t1id'] == $params['team_id']) {
                $items[$key]['chid'] = $item['coach_t1_id'];
                $items[$key]['chn'] = $item['coach_t1_name'];
            } else {
                $items[$key]['chid'] = $item['coach_t2_id'];
                $items[$key]['chn'] = $item['coach_t2_name'];
            }


            unset($items[$key]['coach_t1_id'], $items[$key]['coach_t2_id'], $items[$key]['coach_t1_name'], $items[$key]['coach_t2_name']);
        }

        $event_types = $this->events->eventTypes();

        $event_types_opposite = $this->events->eventTypesOpposite();

        if (!empty($params['stats_id']) && $params['stats_id'] == 'all') $params['stats_id'] = array_keys($event_types);

        /**
         * @stats: Attaching additional stats info(goals, cards, corners)
         */
        if (!empty($params['stats'])) {

            $params['games'] = array_keys($items);

            if (!empty($params['no_cache'])) {
                $stats = $this->stats($params);
            } else {
                $cache_key = 'stats_' . md5(http_build_query([array_keys($items), 'period' => $params['period'] ?? null]));

                $stats = CacheHelper::remember($cache_key, config('api.cache.ttl.games'), function () use ($params, $items) {
                    return $this->stats($params);
                });
            }

            if (!empty($stats)) foreach ($stats as $item) {
                $items[$item['game_id']]['stats'][$item['type']] = $item;

                unset(
                    $items[$item['game_id']]['stats'][$item['type']]['game_id'],
                    $items[$item['game_id']]['stats'][$item['type']]['period'],
                    $items[$item['game_id']]['stats'][$item['type']]['type']
                );
            }
        }

        /**
         * @EVENTS: Attaching events by minutes(goals, cards, corners, subs, fouls etc)
         */

        $game_event_types = [];

        /**
         * oth_* to game_ids, removing oth_* from $items
         */
        foreach ($items as $game_id => $item) {

            $items[$game_id]['gds'] = strtotime($item['gds']);

            $t1_name = !empty($item['t1_alt_name']) ? $item['t1_alt_name'] : $item['t1_name'];
            $t2_name = !empty($item['t2_alt_name']) ? $item['t2_alt_name'] : $item['t2_name'];

            if ($params['lang'] == 'ru') {
                $t1_name = !empty($item['t1_name_ru']) ? $item['t1_name_ru'] : $t1_name;
                $t2_name = !empty($item['t2_name_ru']) ? $item['t2_name_ru'] : $t2_name;
            }

            $items[$game_id]['t1n'] = $t1_name;
            $items[$game_id]['t2n'] = $t2_name;

            unset(
                $items[$game_id]['t1_alt_name'],
                $items[$game_id]['t2_alt_name'],
                $items[$game_id]['t1_name'],
                $items[$game_id]['t2_name'],
                $items[$game_id]['t1_name_ru'],
                $items[$game_id]['t2_name_ru'],
            );

            foreach ($item as $column => $value) {
                if (substr($column, 0, strlen('oth_')) === 'oth_') {

                    if (!empty($value)) {
                        $event_type_id = (int)filter_var($column, FILTER_SANITIZE_NUMBER_INT);

                        $event_name = $event_types[$event_type_id]['alias'];

                        /**
                         * Filter by event id (other_types table)
                         */
                        if (in_array($event_type_id, ($params['stats_id'] ?? []))) {
                            $game_event_types[$event_name]['game_ids'][$item['game_id']] = $item['game_id'];

                            $items[$game_id]['events'] = [];
                        }
                    }

                    unset($items[$game_id][$column]);
                    unset($items[$game_id]['game_id']);
                }
            }
        }

        $params['event_types'] = $game_event_types;

        $this->formattingParams($params);

        if (!empty($game_event_types) &&
            (
                !empty($params['events'])
                || !empty($params['event_time_from'])
                || !empty($params['event_time_to'])
                || !empty(count(array_intersect($this->events->event_tables, array_keys($params['events_features'] ?? []))))
                || !empty($params['match_intervals'])
            )) {

            if (!empty($params['no_cache'])) {
                $events = $this->events->all($params);
            } else {
                $cache_key = 'events_' . md5(http_build_query($params));

                $events = CacheHelper::remember($cache_key, config('api.cache.ttl.games'), function () use ($params) {
                    return $this->events->all($params);
                });
            }

            foreach ($events as $event_name => $event_items) {
                foreach ($event_items as $event) {

                    $team = $items[$event['game_id']]['t1id'] == $event['team_id'] ? 1 : 2;

                    $event_type_id = $event_types_opposite[$event_name]['id'];

                    $event_data['tid'] = $team;
//                    $event_data['team_id'] = $event['team_id'];
                    $event_data['m'] = $event['minute'];
                    $event_data['s'] = $event['second'];
                    $event_data['p'] = $event['period'];

                    if($event_name == 'card') {
                        $event_data['type'] = $event['type'];
                     }

                    $items[$event['game_id']]['events'][$event_type_id][] = $event_data;
                }
            }
        }

        if (!empty($events) && !empty($params['stats']) &&
            (
                !empty($params['event_time_from'])
                || !empty($params['event_time_to'])
                || !empty(count(array_intersect($this->events->event_tables, array_keys($params['events_features'] ?? []))))
                || !empty($params['match_intervals'])
            )) {
            /**
             * Clearing stats
             */
            foreach ($items as $key => $item) $items[$key]['stats'] = [];

            foreach ($events as $event_name => $event_items) {

                $event_counter = [];

                /**
                 * Events counting
                 */
                foreach ($event_items as $event) {

                    if ($event_name == 'possession') {

                        $event_counter[$event['game_id']]['t1'][] = $event['t1'];

                        $event_counter[$event['game_id']]['t2'][] = $event['t2'];

                    } elseif ($event_name == 'card') {
                        $team_key = $event['team_id'] == $items[$event['game_id']]['t1id'] ? 't1' : 't2';

                        $event_counter[$event['game_id']][$event['type']][$team_key][] = $event;
                    } else {
                        $team_key = $event['team_id'] == $items[$event['game_id']]['t1id'] ? 't1' : 't2';

                        $event_counter[$event['game_id']][$team_key][] = $event;
                    }
                }

                /**
                 * Attach event data to stats section
                 */
                foreach ($event_counter as $game_id => $value) {
                    $event_type_id = $event_types_opposite[$event_name]['id'];

                    if ($event_name == 'possession') {
//                    $items[$game_id]['stats'][$event_name]['t1_zz'] = array_sum($value['t1'] ?? []);
//                    $items[$game_id]['stats'][$event_name]['t2_zz'] = array_sum($value['t2'] ?? []);
                    } elseif ($event_name == 'card') { //TODO for now all card types is adding to `card` stats group
                        if(empty($items[$game_id]['stats'][$event_types_opposite['card']['id']]['1'])) $items[$game_id]['stats'][$event_types_opposite['card']['id']]['1'] = 0;
                        if(empty($items[$game_id]['stats'][$event_types_opposite['card']['id']]['2'])) $items[$game_id]['stats'][$event_types_opposite['card']['id']]['2'] = 0;

                        /**
                         * Yellow card (card)
                         */

                        $items[$game_id]['stats'][$event_types_opposite['card']['id']]['1'] += !empty($value[1]['t1']) ? count($value[1]['t1']) : 0;
                        $items[$game_id]['stats'][$event_types_opposite['card']['id']]['2'] += !empty($value[1]['t2']) ? count($value[1]['t2']) : 0;

                        /**
                         * Red Card (red_card)
                         */
                        $items[$game_id]['stats'][$event_types_opposite['card']['id']]['1'] += !empty($value[2]['t1']) ? count($value[2]['t1']) : 0;
                        $items[$game_id]['stats'][$event_types_opposite['card']['id']]['2'] += !empty($value[2]['t2']) ? count($value[2]['t2']) : 0;

                        /**
                         * Second yellow card (yellow_red_card)
                         */
                        $items[$game_id]['stats'][$event_types_opposite['card']['id']]['1'] += !empty($value[3]['t1']) ? count($value[3]['t1']) : 0;
                        $items[$game_id]['stats'][$event_types_opposite['card']['id']]['2'] += !empty($value[3]['t2']) ? count($value[3]['t2']) : 0;


                    } else {
                        $items[$game_id]['stats'][$event_type_id]['1'] = !empty($value['t1']) ? count($value['t1']) : 0;
                        $items[$game_id]['stats'][$event_type_id]['2'] = !empty($value['t2']) ? count($value['t2']) : 0;
                    }

                    unset($game_event_types[$event_name]['game_ids'][$game_id]);
                }
            }

            /**
             * Set stats to zero for $game_event_types that lefts
             */
            foreach ($game_event_types as $event_name => $event_type) {
                $event_type_id = $event_types_opposite[$event_name]['id'];
                if (!empty($event_type['game_ids'])) foreach ($event_type['game_ids'] as $game_id) {
                    $items[$game_id]['stats'][$event_type_id]['1'] = 0;
                    $items[$game_id]['stats'][$event_type_id]['2'] = 0;
                }
            }
        }

        /**
         * Stats features
         */
        if (!empty($params['stats_features_id'])) {
            $this->statsFeatures($items, $events, $params);
        }

        /**
         * Filter by intervals
         */
        if (!empty($params['match_intervals'])) {
            $this->intervals($items, $params);
        }

        foreach ($items as $game_id => $item) {
            unset($items[$game_id]);

            /**
             * Remove stats block if game not started yet(anonse = 1)
             */
            if ($item['st'] == 1) unset($item['stats']);

            if(!empty($params['team_id'])) {
                $team = $params['team_id'] == $item['t1id'] ? 1 : 2;
                $opponent = $params['team_id'] == $item['t1id'] ? 2 : 1;
                $goal_event_id = $event_types_opposite['goal']['id'];

                if (!empty($params['team_goals_stats'])) {
                    if (empty($item['stats'])) continue;

                    if (!($item['stats'][$goal_event_id][$team] == $params['team_goals_stats'])) continue;
                }

                if (!empty($params['opponent_goals_stats'])) {
                    if (empty($item['stats'])) continue;

                    if (!($item['stats'][$goal_event_id][$opponent] == $params['opponent_goals_stats'])) continue;
                }

                if (!empty($params['goals_result_stats'])) {
                    if (empty($item['stats'])) continue;

                    if ($params['goals_result_stats'] == 'win' && !($item['stats'][$goal_event_id][$team] > $item['stats'][$goal_event_id][$opponent])) continue;

                    if ($params['goals_result_stats'] == 'draw' && !($item['stats'][$goal_event_id][$team] == $item['stats'][$goal_event_id][$opponent])) continue;

                    if ($params['goals_result_stats'] == 'lose' && !($item['stats'][$goal_event_id][$team] < $item['stats'][$goal_event_id][$opponent])) continue;
                }

                if (!empty($params['goals_win_stats'])) {
                    if (empty($item['stats'])) continue;

                    if (!($item['stats'][$goal_event_id][$team] > $item['stats'][$goal_event_id][$opponent]
                        && $item['stats'][$goal_event_id][$team] - $item['stats'][$goal_event_id][$opponent] == $params['goals_win_stats'])) continue;
                }

                if (!empty($params['goals_lose_stats'])) {
                    if (empty($item['stats'])) continue;

                    if (!($item['stats'][$goal_event_id][$team] < $item['stats'][$goal_event_id][$opponent]
                        && $item['stats'][$goal_event_id][$opponent] - $item['stats'][$goal_event_id][$team] == $params['goals_lose_stats'])) continue;
                }
            }

            /**
             * Clearing events from unnecessary fields
             */
            if(!empty($item['events'])) foreach ($item['events'] as $event_id => $event_group) {
                foreach ($event_group as $event_index => $event) {
                    unset($item['events'][$event_id][$event_index]['type']);
                }
            }

            unset($item['sec_added_1h'], $item['sec_added_2h']);

            /**
             * Clear events if events=2
             */
            if(!empty($params['events']) && $params['events'] == 2) unset($item['events']);

            $items['id_' . $game_id] = $item;
        }

        if(!empty($params['team_id'])) $data['team_info'] = $this->team->one($params);

        if(!empty($params['referee_id'])) $data['referee_info'] = $this->referee->one($params);

        $data['data'] = $items;

        return $data;
    }

    /**
     * Calculating stats and events values according to intervals
     *
     * @param $games
     * @param $params
     */
    public function intervals(&$games, $params)
    {
        $goals_diff = $params['match_intervals']['goals_diff'] ?? null;

        $after_minute = $params['match_intervals']['after_minute'] ?? 0;

        $red_card = $params['match_intervals']['red_card'] ?? null;

        $red_card_opponent = $params['match_intervals']['red_card_opponent'] ?? null;

        foreach ($games as $game_id => $game) {

            if(empty($game['events']) || empty($game['events'][$this->event_types_opposite['goal']['id']])) continue;

            $team = $game['t1id'] == $params['team_id'] ? 1 : 2;

            $opponent = $game['t1id'] == $params['team_id'] ? 2 : 1;

            if($game['sec_added_1h'] == -999) $game['sec_added_1h'] = 75;

            if($game['sec_added_2h'] == -999) $game['sec_added_2h'] = 225;

            /**
             * Score at the beginning of the game always 0-0
             */
            $score = ['1' => 0, '2' => 0];

            $intervals = [];

            foreach ($game['events'][$this->event_types_opposite['goal']['id']] as $event_index => $event) {

                /**
                 * Counting scores
                 */
                $score[$event['tid']]++;

                /**
                 * Shifting time of start/end of second half intervals by amount of seconds added to first half
                 */
                $interval_time_shifting = 0;

                if($event['p'] > 1) $interval_time_shifting = $game['sec_added_1h'];

                $event_time = $event['m']*60+$event['s'];

                if ($goals_diff == ($score[$team] - $score[$opponent])) {
                    $intervals[] = ['start' => $event_time + $interval_time_shifting];
                } elseif (!Helper::keyExistInArray($intervals, 'start') && $goals_diff != 0) {
                    continue;
                } elseif ($goals_diff == 0 && empty($intervals[count($intervals)-1]['start']) && !empty($intervals[count($intervals)-1]['end'])) {
                    continue;
                } elseif (empty($intervals[count($intervals)-1]['start']) && $goals_diff != 0) {
                    continue;
                } else {
                    $intervals[] = ['end' => $event_time + $interval_time_shifting];
                }
            }

            $pretty_intervals = [];
            /**
             * Improving intervals array for better experience in filtering events
             */
            foreach ($intervals as $interval_index =>  $interval) {

                $pretty_intervals[$interval_index] = [
                    'start' => 0,
                    'end' => 90*60+$game['sec_added_1h']+$game['sec_added_2h']
                ];

                if(!empty($interval['start'])) {
                    $pretty_intervals[$interval_index]['start'] = $interval['start'];
                }

                if(!empty($interval['end']) && !empty($pretty_intervals[$interval_index-1]['start'])) {
                    $pretty_intervals[$interval_index - 1]['end'] = $interval['end'];

                    unset($pretty_intervals[$interval_index]);

                    reset($pretty_intervals);
                }

                if(!empty($interval['end']) && empty($pretty_intervals[$interval_index-1]['start'])) {
                    $pretty_intervals[$interval_index]['end'] = $interval['end'];
                }
            }

            /**
             * Filtering pretty intervals by red cards
             */
            if((!empty($red_card) || !empty($red_card_opponent)) && !empty($game['events'][$this->event_types_opposite['card']['id']])) {
                foreach ($pretty_intervals as $index => $pretty_interval) {
                    foreach ($game['events'][$this->event_types_opposite['card']['id']] as $event_index => $event) {

                        $type = $event['type'];

                        /**
                         * Skip if it is yellow card
                         */
                        if($type == 1) continue;

                        $event_time = $event['m'] * 60 + $event['s'];

                        if(
                            ((!empty($red_card) && $event['tid'] == $team) || (!empty($red_card_opponent) && $event['tid'] == $opponent))
                            &&
                            $event_time >= $pretty_interval['start'] && $event_time <= $pretty_interval['end']
                        ) {
                            /**
                             * If we here interval is satisfied by red card condition
                             */
                            continue 2;
                        }

                        /**
                         * If we here interval is not satisfied by red card condition and we should ignore this interval
                         */
                        unset($pretty_intervals[$index]);
                    }
                }
            }

            /**
             * Recalculating `stats` and `events` values
             */
            $games[$game_id]['stats'] = [];

            $amount_of_seconds = [];

            foreach ($games[$game_id]['events'] as $event_id => $event_group) {

                /**
                 * Setting default score
                 */
                $games[$game_id]['stats'][$event_id] = [
                    1 => 0,
                    2 => 0
                ];

                foreach ($event_group as $event_index => $event) {

                    $time_shift = $event['p'] > 1 ? $game['sec_added_1h'] : 0;

                    $event_time = $event['m'] * 60 + $event['s'] + $time_shift;

                    foreach ($pretty_intervals as $interval_index => $interval) {
                        $amount_of_seconds[$interval['start'] . '-' . $interval['end']] = $interval['end'] - $interval['start'];

                        if($event_time > $interval['start'] && $event_time <= $interval['end'] && $event_time >= ($after_minute*60)) {

                            $games[$game_id]['stats'][$event_id][$event['tid']]++;

                            /**
                             * Condition is satisfied - moving to next event
                             */
                            continue 2;
                        }
                    }

                    /**
                     * If we are here, it means that no condition of the interval is satisfied - remove current event
                     */
                    unset($games[$game_id]['events'][$event_id][$event_index]);
                }

                /**
                 * Set stats to -1 if no events found in specified intervals
                 */
                if(empty($games[$game_id]['stats'][$event_id][1]) && empty($games[$game_id]['stats'][$event_id][2])) {
                    $games[$game_id]['stats'][$event_id] = [
                        1 => -1,
                        2 => -1,
                    ];
                }
            }

            /**
             * Adding amount seconds for calculated intervals
             */
            $games[$game_id]['cis'] = array_sum($amount_of_seconds);
        }
    }

    /**
     * @param $params
     * @return array
     */
    public function stats($params)
    {
        $stats = DB::table('statplus')
            ->select('game_id', 'type', 't1 AS 1', 't2 AS 2', 'period')
            ->whereIn('game_id', $params['games']);

        if (!empty($params['period']) && $params['period'] != '2h') $stats = $stats->where('period', $params['period']);

        if (!empty($params['stats_id'])) $stats = $stats->whereIn('type', $params['stats_id']);

        $stats = Helper::toArray($stats->get());

        /**
         * Calculate 2h period results
         */
        if (!empty($params['period']) && $params['period'] == '2h') {
            foreach ($stats as $key => $needle) {
                if ($needle['period'] == 'match') foreach ($stats as $xx => $stack) {
                    if ($needle['game_id'] == $stack['game_id'] && $needle['type'] == $stack['type'] && $stack['period'] == '1h') {
                        $t1 = $needle['1'] - $stack['1'];

                        $t2 = $needle['2'] - $stack['2'];

                        $stats[$key]['1'] = $t1;

                        $stats[$key]['2'] = $t2;

                        unset($stats[$xx]);
                    }
                }
            }
        }

        return $stats;
    }

    /**
     * @param $items
     * @param $events
     * @param $params
     */
    public function statsFeatures(&$items, &$events, $params)
    {
        $stats_options = $this->events->statsOptions();

        $event_params = [];

        foreach ($params['stats_features_id'] as $stats_features_id) {
            $features_components = explode('_', $stats_features_id);

            $event_id = $features_components[0] ?? null;
            unset($features_components[0]);

            $event_params[$stats_features_id]['event_id'] = $event_id;

            foreach ($features_components as $features_component) {
                $values = explode('-', $features_component);

                $field_id = $values[0] ?? null;
                unset($values[0]);

                $field_name = $stats_options[$field_id]['alias'];

                $event_params[$stats_features_id]['fields'][$field_name] = $values;
            }
        }

        $event_types_opposite = $this->events->eventTypesOpposite();

        foreach ($items as $game_id => $item) {

            $stats_features = [];

            foreach ($event_params as $stats_feature_id => $event_param) {

                if (empty($params['event_types'][$this->event_types[$event_param['event_id']]['alias']]['game_ids'][$game_id])) {
                    continue 2;
                }

                $stats_features[$stats_feature_id][1] = 0;
                $stats_features[$stats_feature_id][2] = 0;

                if (!empty($events)) foreach ($events as $event_name => $event_list) {
                    $event_id = $event_types_opposite[$event_name]['id'] ?? null;

                    if ($event_id == $event_param['event_id']) {


                        foreach ($event_list as $event) {

                            $team = $event['team_id'] == $item['t1id'] ? 1 : 2;

                            $conditions = [];

                            if (!empty($event_param['fields'])) foreach ($event_param['fields'] as $field_name => $values) {
                                $conditions[] = !empty($event[$field_name]) && in_array($event[$field_name], $values);
                            }

                            if (!in_array(false, $conditions, true) && $event['game_id'] == $game_id) {
                                $stats_features[$stats_feature_id][$team] = $stats_features[$stats_feature_id][$team] + 1;
                            }
                        }
                    }
                }
            }

            if (!empty($stats_features)) $items[$game_id]['stats_features'] = $stats_features;
        }
    }

    /**
     * Define cs game_id based on soccerway_id
     *
     * @param $cs_game_id
     * @return mixed
     */
    public function getOddsGameId($cs_game_id)
    {
        $soccer_way_id = DB::table('game')->select('soccerway_id')
            ->where('game_id', $cs_game_id)
            ->limit(1)
            ->pluck('soccerway_id')
            ->first();

        $odds_game_id = DB::connection('pinnacle')
            ->table('games')
            ->select('id')
            ->where('soccerway_id', $soccer_way_id)
            ->orderBy('game_date_start', 'DESC')
            ->pluck('id')
            ->first();

        return $odds_game_id;
    }

    /**
     * Return array of main ids based on soccerway id
     *
     * @param $parser_game_ids
     * @param $connection
     * @return mixed
     */
    public function getMainGameIds($parser_game_ids, $connection)
    {
        $parser_games = DB::connection($connection)
            ->table('games')
            ->select('soccerway_id', 'id')
            ->whereIn('id', $parser_game_ids)
            ->where('soccerway_id', '<>', 0)
            ->orderBy('game_date_start', 'ASC')
            ->get()
            ->keyBy('soccerway_id')
            ->toArray();

        $main_ids = DB::table('game')->select('game_id', 'soccerway_id')
            ->whereIn('soccerway_id', array_keys($parser_games))
            ->where('soccerway_id', '<>', 0)
            ->get()
            ->keyBy('soccerway_id');

        $main_ids = json_decode(json_encode($main_ids), true);
        $parser_games = json_decode(json_encode($parser_games), true);

        foreach ($main_ids as $soccerway_id => $main_id) {
            $main_ids[$soccerway_id]['parser_game_id'] = $parser_games[$soccerway_id]['id'];
        }

        return $main_ids;
    }

    public function formattingParams(&$params)
    {
        if (!empty($params['events_features'])) {

            $events_features = [];

            foreach ($params['events_features'] as $event_id => $features) {

                if (!empty($this->event_types[$event_id]['alias'])) {
                    $event_name = $this->event_types[$event_id]['alias'];

                    foreach ($features as $feature_id => $feature) {

                        if (!empty($this->stats_options[$feature_id]['alias']) && $this->stats_options[$feature_id]['stats_id'] == $event_id) {

                            $feature_name = $this->stats_options[$feature_id]['alias'];

                            $events_features[$event_name][$feature_name] = $feature;

                        } else continue;
                    }

                } else continue;
            }

            $params['events_features'] = $events_features;
        }
    }
}
