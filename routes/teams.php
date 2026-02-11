<?php

/* Routes per gestione teams */


use App\Utils\Response;
use App\Models\Team;
use App\Utils\Request;
use Pecee\SimpleRouter\SimpleRouter as Router;

/**
 * GET /api/teams - Lista teams
 */
Router::get('/teams', function () {
    try {
        $teams = Team::all();
        Response::success($teams)->send();
    } catch (\Exception $e) {
        Response::error('Errore nel recupero della lista teams: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});

/**
 * GET /api/teams/{id} - Atleta 
 */
Router::get('/teams/{id}', function ($id) {
    try {
        $team = Team::find($id);

        if($team === null) {
            Response::error('team non trovato', Response::HTTP_NOT_FOUND)->send();
            return;
        }

        Response::success($team)->send();
    } catch (\Exception $e) {
        Response::error('Errore nel recupero della lista teams: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});


/**
 * POST /api/teams - Crea nuova teams
 */
Router::post('/teams', function () {
    try {
        $request = new Request();
        $data = $request->json();

        // Validazione
        $errors = Team::validate($data);
        if (!empty($errors)) {
            Response::error('Errore di validazione', Response::HTTP_BAD_REQUEST, $errors)->send();
            return;
        }

        $team = Team::create($data);

        Response::success($team, Response::HTTP_CREATED, "Atleta creato con successo")->send();
    } catch (\Exception $e) {
        Response::error('Errore durante la creazione del nuovo team: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});

Router::match(['put', 'patch'], '/teams/{id}', function($id) {
    try {
        $request = new Request();
        $data = $request->json();

        $team = Team::find($id);
        if($team === null) {
            Response::error('Atleta non trovata', Response::HTTP_NOT_FOUND)->send();
            return;
        }

        $errors = Team::validate(array_merge($data, ['id' => $id]));
        if (!empty($errors)) {
            Response::error('Errore di validazione', Response::HTTP_BAD_REQUEST, $errors)->send();
            return;
        }

        $team->update($data);

        Response::success($team, Response::HTTP_OK, "teams aggiornati con successo")->send();
    } catch (\Exception $e) {
        Response::error('Errore durante l\'aggiornamento degli teams: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});

Router::delete('/teams/{id}', function($id) {
    try {
        $team = Team::find($id);
        if($team === null) {
            Response::error('teams non trovati', Response::HTTP_NOT_FOUND)->send();
            return;
        }

        $team->delete();

        Response::success(null, Response::HTTP_OK, "team eliminato con successo")->send();
    } catch (\Exception $e) {
        Response::error('Errore durante l\'eliminazione degli teams: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});

