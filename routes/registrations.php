<?php

/* Routes per gestione registrations */


use App\Utils\Response;
use App\Models\Registration;
use App\Models\Tournament;
use App\Utils\Request;
use Pecee\SimpleRouter\SimpleRouter as Router;

/**
 * GET /api/registrations - Lista registrations
 */
Router::get('/registrations', function () {
    try {
        $registrations = Registration::all();
        Response::success($registrations)->send();
    } catch (\Exception $e) {
        Response::error('Errore nel recupero della lista registrations: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});

/**
 * GET /api/registrations/{id} - Registration singola
 */
Router::get('/registrations/{id}', function ($id) {
    try {
        $registration = Registration::find($id);

        if($registration === null) {
            Response::error('registration non trovata', Response::HTTP_NOT_FOUND)->send();
            return;
        }

        Response::success($registration)->send();
    } catch (\Exception $e) {
        Response::error('Errore nel recupero della lista registrations: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});


/**
 * POST /api/registrations - Crea nuova registration
 */
Router::post('/registrations', function () {
    try {
        $request = new Request();
        $data = $request->json();

        // Validazione
        $errors = Registration::validate($data);
        if (!empty($errors)) {
            Response::error('Errore di validazione', Response::HTTP_BAD_REQUEST, $errors)->send();
            return;
        }

        $registration = Registration::create($data);
        $tournament = Tournament::find($registration->tournament_fk);

        $tournamentName = $tournament
            ? $tournament('name')
            : "il";

        Response::success($registration, Response::HTTP_CREATED, "Registration creata con successo a {$tournamentName}")->send();
    } catch (\Exception $e) {
        Response::error('Errore durante la creazione della nuova registration: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});

Router::match(['put', 'patch'], '/registrations/{id}', function($id) {
    try {
        $request = new Request();
        $data = $request->json();

        $registration = Registration::find($id);
        if($registration === null) {
            Response::error('Registration non trovata', Response::HTTP_NOT_FOUND)->send();
            return;
        }

        $errors = Registration::validate(array_merge($data, ['id' => $id]));
        if (!empty($errors)) {
            Response::error('Errore di validazione', Response::HTTP_BAD_REQUEST, $errors)->send();
            return;
        }

        $registration->update($data);

        Response::success($registration, Response::HTTP_OK, "registrations aggiornati con successo")->send();
    } catch (\Exception $e) {
        Response::error('Errore durante l\'aggiornamento delle registrations: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});

Router::delete('/registrations/{id}', function($id) {
    try {
        $registration = Registration::find($id);
        if($registration === null) {
            Response::error('registrations non trovate', Response::HTTP_NOT_FOUND)->send();
            return;
        }

        $registration->delete();

        Response::success(null, Response::HTTP_OK, "registration eliminata con successo")->send();
    } catch (\Exception $e) {
        Response::error('Errore durante l\'eliminazione delle registrations: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});