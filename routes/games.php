<?php

/* Routes per gestione games */


use App\Utils\Response;
use App\Models\Game;
use App\Utils\Request;
use Pecee\SimpleRouter\SimpleRouter as Router;

/**
 * GET /api/games - Lista games
 */
Router::get('/games', function () {
    try {
        $games = Game::all();
        Response::success($games)->send();
    } catch (\Exception $e) {
        Response::error('Errore nel recupero della lista games: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});

/**
 * GET /api/games/{id} - Game singolo
 */
Router::get('/games/{id}', function ($id) {
    try {
        $game = Game::find($id);

        if ($game === null) {
            Response::error('game non trovato', Response::HTTP_NOT_FOUND)->send();
            return;
        }

        Response::success($game)->send();
    } catch (\Exception $e) {
        Response::error('Errore nel recupero della lista games: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});


/**
 * POST /api/games - Crea nuovo game
 */
Router::post('/games', function () {
    try {
        $request = new Request();
        $data = $request->json();

        // Validazione
        $errors = Game::validate($data);
        if (!empty($errors)) {
            Response::error('Errore di validazione', Response::HTTP_BAD_REQUEST, $errors)->send();
            return;
        }

        $game = Game::create($data);


        Response::success($game, Response::HTTP_CREATED, "Game creato con successo")->send();
    } catch (\Exception $e) {
        Response::error('Errore durante la creazione del nuovo game: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});

Router::match(['put', 'patch'], '/games/{id}', function ($id) {
    try {
        $request = new Request();
        $data = $request->json();

        $game = Game::find($id);
        if ($game === null) {
            Response::error('Game non trovato', Response::HTTP_NOT_FOUND)->send();
            return;
        }

        $errors = Game::validate(array_merge($data, ['id' => $id]));
        if (!empty($errors)) {
            Response::error('Errore di validazione', Response::HTTP_BAD_REQUEST, $errors)->send();
            return;
        }

        $game->update($data);

        Response::success($game, Response::HTTP_OK, "games aggiornati con successo")->send();
    } catch (\Exception $e) {
        Response::error('Errore durante l\'aggiornamento delle games: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});

Router::delete('/games/{id}', function ($id) {
    try {
        $game = Game::find($id);
        if ($game === null) {
            Response::error('games non trovate', Response::HTTP_NOT_FOUND)->send();
            return;
        }

        $game->delete();

        Response::success(null, Response::HTTP_OK, "game eliminata con successo")->send();
    } catch (\Exception $e) {
        Response::error('Errore durante l\'eliminazione delle games: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});
