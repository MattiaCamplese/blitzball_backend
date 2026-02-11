<?php

/* Routes per gestione tournaments */

use App\Utils\Response;
use App\Models\Tournament;
use App\Utils\Request;
use Pecee\SimpleRouter\SimpleRouter as Router;

/**
 * GET /api/tournaments - Lista tournaments
 */
Router::get('/tournaments', function () {
    try {
        $tournaments = Tournament::all();
        Response::success($tournaments)->send();
    } catch (\Exception $e) {
        Response::error('Errore nel recupero della lista tournaments: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});

/**
 * GET /api/tournaments/{id} - Tournament 
 */
Router::get('/tournaments/{id}', function ($id) {
    try {
        $tournament = Tournament::find($id);

        if($tournament === null) {
            Response::error('tournament non trovato', Response::HTTP_NOT_FOUND)->send();
            return;
        }

        Response::success($tournament)->send();
    } catch (\Exception $e) {
        Response::error('Errore nel recupero della lista tournaments: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});


/**
 * POST /api/tournaments - Crea nuova tournaments
 */
Router::post('/tournaments', function () {
    try {
        $request = new Request();
        $data = $request->json();

        // Validazione
        $errors = Tournament::validate($data);
        if (!empty($errors)) {
            Response::error('Errore di validazione', Response::HTTP_BAD_REQUEST, $errors)->send();
            return;
        }

        $tournament = Tournament::create($data);

        Response::success($tournament, Response::HTTP_CREATED, "Tournament creato con successo")->send();
    } catch (\Exception $e) {
        Response::error('Errore durante la creazione del nuovo tournament: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});

Router::match(['put', 'patch'], '/tournaments/{id}', function($id) {
    try {
        $request = new Request();
        $data = $request->json();

        $tournament = Tournament::find($id);
        if($tournament === null) {
            Response::error('Tournament non trovata', Response::HTTP_NOT_FOUND)->send();
            return;
        }

        $errors = Tournament::validate(array_merge($data, ['id' => $id]));
        if (!empty($errors)) {
            Response::error('Errore di validazione', Response::HTTP_BAD_REQUEST, $errors)->send();
            return;
        }

        $tournament->update($data);

        Response::success($tournament, Response::HTTP_OK, "tournaments aggiornati con successo")->send();
    } catch (\Exception $e) {
        Response::error('Errore durante l\'aggiornamento dei tournaments: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});

Router::delete('/tournaments/{id}', function($id) {
    try {
        $tournament = Tournament::find($id);
        if($tournament === null) {
            Response::error('tournaments non trovati', Response::HTTP_NOT_FOUND)->send();
            return;
        }

        $tournament->delete();

        Response::success(null, Response::HTTP_OK, "tournament eliminato con successo")->send();
    } catch (\Exception $e) {
        Response::error('Errore durante l\'eliminazione dei tournaments: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});