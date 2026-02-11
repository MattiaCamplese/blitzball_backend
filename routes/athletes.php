<?php

/* Routes per gestione athletes */


use App\Utils\Response;
use App\Models\Athlete;
use App\Utils\Request;
use Pecee\SimpleRouter\SimpleRouter as Router;

/**
 * GET /api/athletes - Lista atleti
 */
Router::get('/athletes', function () {
    try {
        $athletes = Athlete::all();
        Response::success($athletes)->send();
    } catch (\Exception $e) {
        Response::error('Errore nel recupero della lista atleti: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});

/**
 * GET /api/athletes/{id} - Atleta 
 */
Router::get('/athletes/{id}', function ($id) {
    try {
        $athlete = Athlete::find($id);

        if($athlete === null) {
            Response::error('atleta non trovato', Response::HTTP_NOT_FOUND)->send();
            return;
        }

        Response::success($athlete)->send();
    } catch (\Exception $e) {
        Response::error('Errore nel recupero della lista atleti: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});


/**
 * POST /api/athletes - Crea nuova atleti
 */
Router::post('/athletes', function () {
    try {
        $request = new Request();
        $data = $request->json();

        // Validazione
        $errors = Athlete::validate($data);
        if (!empty($errors)) {
            Response::error('Errore di validazione', Response::HTTP_BAD_REQUEST, $errors)->send();
            return;
        }

        $athlete = Athlete::create($data);

        Response::success($athlete, Response::HTTP_CREATED, "Atleta creato con successo")->send();
    } catch (\Exception $e) {
        Response::error('Errore durante la creazione del nuovo atleta: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});

Router::match(['put', 'patch'], '/athletes/{id}', function($id) {
    try {
        $request = new Request();
        $data = $request->json();

        $athlete = Athlete::find($id);
        if($athlete === null) {
            Response::error('Atleta non trovata', Response::HTTP_NOT_FOUND)->send();
            return;
        }

        $errors = Athlete::validate(array_merge($data, ['id' => $id]));
        if (!empty($errors)) {
            Response::error('Errore di validazione', Response::HTTP_BAD_REQUEST, $errors)->send();
            return;
        }

        $athlete->update($data);

        Response::success($athlete, Response::HTTP_OK, "atleti aggiornati con successo")->send();
    } catch (\Exception $e) {
        Response::error('Errore durante l\'aggiornamento degli atleti: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});

Router::delete('/athletes/{id}', function($id) {
    try {
        $athlete = Athlete::find($id);
        if($athlete === null) {
            Response::error('atleti non trovati', Response::HTTP_NOT_FOUND)->send();
            return;
        }

        $athlete->delete();

        Response::success(null, Response::HTTP_OK, "atleta eliminato con successo")->send();
    } catch (\Exception $e) {
        Response::error('Errore durante l\'eliminazione degli atleti: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});