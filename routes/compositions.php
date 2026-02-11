<?php

/* Routes per gestione compositions */

use App\Models\Composition;
use App\Services\CompositionService;
use App\Utils\Request;
use App\Utils\Response;
use Pecee\SimpleRouter\SimpleRouter as Router;


/**
 * GET /api/compositions/athlete/{athlete_id}
 * Tutte le composizioni di un atleta, con nome squadra incluso
 */
Router::get('/compositions/athlete/{athlete_id}', function ($athlete_id) {
    try {
        $data = CompositionService::getByAthlete((int) $athlete_id);
        Response::success($data)->send();
    } catch (\Exception $e) {
        $code = str_contains($e->getMessage(), 'non trovato')
            ? Response::HTTP_NOT_FOUND
            : Response::HTTP_INTERNAL_SERVER_ERROR;
        Response::error($e->getMessage(), $code)->send();
    }
});

/**
 * GET /api/compositions/team/{team_id}
 * Tutti gli atleti di una squadra, con nome atleta incluso
 */
Router::get('/compositions/team/{team_id}', function ($team_id) {
    try {
        $data = CompositionService::getByTeam((int) $team_id);
        Response::success($data)->send();
    } catch (\Exception $e) {
        $code = str_contains($e->getMessage(), 'non trovata')
            ? Response::HTTP_NOT_FOUND
            : Response::HTTP_INTERNAL_SERVER_ERROR;
        Response::error($e->getMessage(), $code)->send();
    }
});

/**
 * GET /api/compositions - Lista tutte le composizioni
 */
Router::get('/compositions', function () {
    try {
        $compositions = Composition::all();
        Response::success($compositions)->send();
    } catch (\Exception $e) {
        Response::error('Errore nel recupero delle composizioni: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});

/**
 * GET /api/compositions/{id} - Singola composizione
 */
Router::get('/compositions/{id}', function ($id) {
    try {
        $composition = Composition::find((int) $id);
        if ($composition === null) {
            Response::error('Composizione non trovata', Response::HTTP_NOT_FOUND)->send();
            return;
        }
        Response::success($composition)->send();
    } catch (\Exception $e) {
        Response::error('Errore nel recupero della composizione: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});

/**
 * POST /api/compositions - Assegna un atleta a una squadra
 * Usa CompositionService::assign() che controlla i duplicati
 */
Router::post('/compositions', function () {
    try {
        $request = new Request();
        $data = $request->json();

        $composition = CompositionService::assign($data);

        Response::success($composition, Response::HTTP_CREATED, "Atleta assegnato alla squadra con successo")->send();
    } catch (\InvalidArgumentException $e) {
        // Errori di validazione (array JSON)
        $errors = json_decode($e->getMessage(), true) ?? [];
        Response::error('Errore di validazione', Response::HTTP_BAD_REQUEST, $errors)->send();
    } catch (\Exception $e) {
        $code = str_contains($e->getMessage(), 'già attivo')
            ? Response::HTTP_INTERNAL_SERVER_ERROR
            : Response::HTTP_INTERNAL_SERVER_ERROR;
        Response::error($e->getMessage(), $code)->send();
    }
});

/**
 * PATCH /api/compositions/{id}/terminate - Termina una composizione (setta end_date = oggi)
 */
Router::patch('/compositions/{id}/terminate', function ($id) {
    try {
        $composition = CompositionService::terminate((int) $id);
        Response::success($composition, Response::HTTP_OK, "Composizione terminata con successo")->send();
    } catch (\Exception $e) {
        $code = match (true) {
            str_contains($e->getMessage(), 'non trovata') => Response::HTTP_NOT_FOUND,
            str_contains($e->getMessage(), 'già terminata') => Response::HTTP_INTERNAL_SERVER_ERROR,
            default => Response::HTTP_INTERNAL_SERVER_ERROR,
        };
        Response::error($e->getMessage(), $code)->send();
    }
});

/**
 * PUT|PATCH /api/compositions/{id} - Aggiorna una composizione
 */
Router::match(['put', 'patch'], '/compositions/{id}', function ($id) {
    try {
        $request = new Request();
        $data = $request->json();

        $composition = Composition::find((int) $id);
        if ($composition === null) {
            Response::error('Composizione non trovata', Response::HTTP_NOT_FOUND)->send();
            return;
        }

        $errors = Composition::validate(array_merge($data, ['id' => $id]));
        if (!empty($errors)) {
            Response::error('Errore di validazione', Response::HTTP_BAD_REQUEST, $errors)->send();
            return;
        }

        $composition->update($data);
        Response::success($composition, Response::HTTP_OK, "Composizione aggiornata con successo")->send();
    } catch (\Exception $e) {
        Response::error('Errore durante l\'aggiornamento della composizione: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});

/**
 * DELETE /api/compositions/{id} - Elimina una composizione
 */
Router::delete('/compositions/{id}', function ($id) {
    try {
        $composition = Composition::find((int) $id);
        if ($composition === null) {
            Response::error('Composizione non trovata', Response::HTTP_NOT_FOUND)->send();
            return;
        }

        $composition->delete();
        Response::success(null, Response::HTTP_OK, "Composizione eliminata con successo")->send();
    } catch (\Exception $e) {
        Response::error('Errore durante l\'eliminazione della composizione: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});