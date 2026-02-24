<?php

/* Routes per gestione brackets_generator come servizio */

use App\Models\Tournament;
use App\Models\Game;
use App\Models\MatchScorer;
use App\Models\Athlete;
use App\Services\BracketGenerator;
use App\Utils\Response;
use App\Utils\Request;
use App\Database\DB;
use Pecee\SimpleRouter\SimpleRouter as Router;

/**
 * GET /api/brackets_generator/{tournament_id} - Ottieni tutte le partite di un torneo (con marcatori)
 */
Router::get('/brackets_generator/{tournament_id}', function ($tournament_id) {
    try {
        $tournament = Tournament::find($tournament_id);
        if (!$tournament) {
            Response::error('Tournament non trovato', Response::HTTP_NOT_FOUND)->send();
            return;
        }

        $games = Game::where('tournament_fk', $tournament->id);

        // Aggiungi i marcatori per ogni partita
        $result = [];
        foreach ($games as $game) {
            $gameArr = $game->toArray();
            $gameScorers = MatchScorer::where('game_fk', $game->id);
            $scorersList = [];
            foreach ($gameScorers as $ms) {
                $athlete = Athlete::find($ms->athlete_fk);
                $scorersList[] = [
                    'athlete_id'   => $ms->athlete_fk,
                    'athlete_name' => $athlete ? $athlete->first_name . ' ' . $athlete->last_name : 'Sconosciuto',
                    'goals'        => $ms->goals,
                    'team_fk'      => $ms->team_fk,
                ];
            }
            $gameArr['scorers'] = $scorersList;
            $result[] = $gameArr;
        }

        Response::success($result)->send();
    } catch (\Exception $e) {
        Response::error('Errore nel recupero del bracket: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});

/**
 * GET /api/brackets_generator/{tournament_id}/final - Ottieni risultato della finale
 */
Router::get('/brackets_generator/{tournament_id}/final', function ($tournament_id) {
    try {
        $tournament = Tournament::find($tournament_id);
        if (!$tournament) {
            Response::error('Tournament non trovato', Response::HTTP_NOT_FOUND)->send();
            return;
        }

        $final = BracketGenerator::getFinal($tournament->id);

        if (!$final) {
            Response::error('Finale non ancora disponibile', Response::HTTP_BAD_REQUEST)->send();
            return;
        }

        $winnerTeamId = $final->home_score > $final->away_score
            ? $final->home_team_fk
            : $final->away_team_fk;

        Response::success([
            'final_game_id' => $final->id,
            'winner_team_id' => $winnerTeamId,
            'score' => "{$final->home_score}-{$final->away_score}"
        ])->send();

    } catch (\Exception $e) {
        Response::error('Errore nel recupero della finale: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});

/**
 * POST /api/brackets_generator - Crea un nuovo bracket per un torneo
 */
Router::post('/brackets_generator', function () {
    try {
        $request = new Request();
        $data = $request->json();

        $tournamentId = $data['tournament_id'] ?? null;
        $tournament = $tournamentId ? Tournament::find($tournamentId) : null;

        if (!$tournament) {
            $errors = Tournament::validate($data);
            if (!empty($errors)) {
                Response::error('Errore di validazione', Response::HTTP_BAD_REQUEST, $errors)->send();
                return;
            }
            $tournament = Tournament::create($data);
        }

        $teamIds = $data['team_ids'] ?? [];
        $games = BracketGenerator::generateBracket($tournament, $teamIds);

        Response::success($games, Response::HTTP_CREATED, "Bracket generato con successo")->send();
    } catch (\Exception $e) {
        Response::error('Errore durante la generazione del bracket: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});

/**
 * PATCH /api/brackets_generator/{game_id}/scorers - Aggiorna solo i marcatori (partita già completata)
 */
Router::patch('/brackets_generator/{game_id}/scorers', function ($game_id) {
    try {
        $request = new Request();
        $data = $request->json();

        $game = Game::find($game_id);
        if (!$game) {
            Response::error('Game non trovato', Response::HTTP_NOT_FOUND)->send();
            return;
        }

        if (!$game->completed) {
            Response::error('Partita non ancora completata', Response::HTTP_BAD_REQUEST)->send();
            return;
        }

        $newScorers = $data['scorers'] ?? [];

        DB::beginTransaction();

        // Decrementa i gol dei vecchi marcatori e cancellali
        $oldScorers = MatchScorer::where('game_fk', $game->id);
        foreach ($oldScorers as $ms) {
            $athlete = Athlete::find($ms->athlete_fk);
            if ($athlete) {
                $updated = max(0, ($athlete->goals ?? 0) - $ms->goals);
                $athlete->update(['goals' => $updated]);
            }
            $ms->delete();
        }

        // Inserisci i nuovi marcatori e aggiorna i gol degli atleti
        $savedScorers = [];
        foreach ($newScorers as $scorerData) {
            if (empty($scorerData['athlete_fk']) || empty($scorerData['goals'])) continue;

            $ms = MatchScorer::create([
                'game_fk'    => (int)$game->id,
                'athlete_fk' => (int)$scorerData['athlete_fk'],
                'team_fk'    => (int)$scorerData['team_fk'],
                'goals'      => (int)$scorerData['goals'],
            ]);

            $athlete = Athlete::find($scorerData['athlete_fk']);
            if ($athlete) {
                $athlete->update(['goals' => ($athlete->goals ?? 0) + (int)$scorerData['goals']]);
                $savedScorers[] = [
                    'athlete_id'   => $ms->athlete_fk,
                    'athlete_name' => $athlete->first_name . ' ' . $athlete->last_name,
                    'goals'        => $ms->goals,
                    'team_fk'      => $ms->team_fk,
                ];
            }
        }

        DB::commit();

        Response::success(['scorers' => $savedScorers], Response::HTTP_OK, 'Marcatori aggiornati')->send();
    } catch (\Exception $e) {
        try { DB::rollBack(); } catch (\Throwable $ignored) {}
        Response::error('Errore aggiornamento marcatori: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});

/**
 * PATCH /api/brackets_generator/{game_id} - Aggiorna risultato di una partita (+ marcatori opzionali)
 */
Router::patch('/brackets_generator/{game_id}', function ($game_id) {
    try {
        $request = new Request();
        $data = $request->json();

        $game = Game::find($game_id);
        if (!$game) {
            Response::error('Game non trovato', Response::HTTP_NOT_FOUND)->send();
            return;
        }

        if ($game->completed) {
            Response::error('Partita già completata', Response::HTTP_BAD_REQUEST)->send();
            return;
        }

        if (!isset($data['home_score'], $data['away_score'])) {
            Response::error('home_score e away_score sono obbligatori', Response::HTTP_BAD_REQUEST)->send();
            return;
        }

        DB::beginTransaction();

        $winnerId = BracketGenerator::updateGameResult($game, (int)$data['home_score'], (int)$data['away_score']);

        // Salva i marcatori se forniti
        $savedScorers = [];
        $newScorers = $data['scorers'] ?? [];
        foreach ($newScorers as $scorerData) {
            if (empty($scorerData['athlete_fk']) || empty($scorerData['goals'])) continue;

            $ms = MatchScorer::create([
                'game_fk'    => (int)$game->id,
                'athlete_fk' => (int)$scorerData['athlete_fk'],
                'team_fk'    => (int)$scorerData['team_fk'],
                'goals'      => (int)$scorerData['goals'],
            ]);

            $athlete = Athlete::find($scorerData['athlete_fk']);
            if ($athlete) {
                $athlete->update(['goals' => ($athlete->goals ?? 0) + (int)$scorerData['goals']]);
                $savedScorers[] = [
                    'athlete_id'   => $ms->athlete_fk,
                    'athlete_name' => $athlete->first_name . ' ' . $athlete->last_name,
                    'goals'        => $ms->goals,
                    'team_fk'      => $ms->team_fk,
                ];
            }
        }

        DB::commit();

        Response::success(
            ['winner_team_id' => $winnerId, 'scorers' => $savedScorers],
            Response::HTTP_OK,
            'Partita aggiornata con successo'
        )->send();
    } catch (\Exception $e) {
        try { DB::rollBack(); } catch (\Throwable $ignored) {}
        Response::error('Errore durante l\'aggiornamento della partita: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});
