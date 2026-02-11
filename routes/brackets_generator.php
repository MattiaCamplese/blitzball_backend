<?php

/* Routes per gestione brackets_generator come servizio */

use App\Models\Tournament;
use App\Models\Game;
use App\Services\BracketGenerator;
use App\Utils\Response;
use App\Utils\Request;
use Pecee\SimpleRouter\SimpleRouter as Router;

/**
 * GET /api/brackets_generator/{tournament_id} - Ottieni tutte le partite di un torneo
 */
Router::get('/brackets_generator/{tournament_id}', function ($tournament_id) {
    try {
        $tournament = Tournament::find($tournament_id);
        if (!$tournament) {
            Response::error('Tournament non trovato', Response::HTTP_NOT_FOUND)->send();
            return;
        }

        $games = Game::where('tournament_fk', $tournament->id);
        Response::success($games)->send();
    } catch (\Exception $e) {
        Response::error('Errore nel recupero del bracket: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});

/**
 * GET /api/brackets_generator/{tournament_id}/final - Ottieni risultato della finale finale
 */
Router::get('/brackets_generator/{tournament_id}/final', function ($tournament_id) {
    try {
        $tournament = Tournament::find($tournament_id);
        if (!$tournament) {Response::error('Tournament non trovato', Response::HTTP_NOT_FOUND)->send();
            return;
        }

        $final = BracketGenerator::getFinal($tournament->id);

        if (!$final) {
            Response::error('Finale non ancora disponibile',Response::HTTP_BAD_REQUEST)->send();
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
        Response::error('Errore nel recupero della finale: ' . $e->getMessage(),Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});

/**
 * POST /api/brackets_generator - Crea un nuovo bracket per un torneo
 */
Router::post('/brackets_generator', function () {
    try {
        $request = new Request();
        $data = $request->json();

        // Controllo che il torneo esista o crealo
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

        // Generazione bracket
        $teamIds = $data['team_ids'] ?? [];
        $games = BracketGenerator::generateBracket($tournament, $teamIds);

        Response::success($games, Response::HTTP_CREATED, "Bracket generato con successo")->send();
    } catch (\Exception $e) {
        Response::error('Errore durante la generazione del bracket: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});

/**
 * PATCH /api/brackets_generator/{game_id} - Aggiorna risultato di una partita
 */
Router::patch('/brackets_generator/{game_id}', function ($game_id) {
    try {
        $request = new Request();
        $data = $request->json();

        $game = Game::find($game_id);

        if (!$game) {Response::error('Game non trovato', Response::HTTP_NOT_FOUND)->send();return;
        }

        if ($game->completed) {Response::error('Partita già completata', Response::HTTP_BAD_REQUEST)->send();
            return;
        }

        if (!isset($data['home_score'], $data['away_score'])) {Response::error('home_score e away_score sono obbligatori',Response::HTTP_BAD_REQUEST)->send();
            return;
        }

        $winnerId = BracketGenerator::updateGameResult( $game, (int) $data['home_score'], (int) $data['away_score']
        );

        Response::success(['winner_team_id' => $winnerId],Response::HTTP_OK,'Partita aggiornata con successo')->send();
    } catch (\Exception $e) 
    {
        Response::error('Errore durante l\'aggiornamento della partita: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});
