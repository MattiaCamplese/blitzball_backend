<?php

/* Routes per gestione halls_of_fame */

use App\Utils\Response;
use App\Models\HallOfFame;
use App\Models\Team;
use App\Models\Tournament;
use App\Utils\Request;
use Pecee\SimpleRouter\SimpleRouter as Router;

/**
 * GET /api/halls_of_fame - Lista halls_of_fame
 */
Router::get('/halls_of_fame', function () {
    try {
        $halls_of_fame = HallOfFame::all();

        // Mappiamo ogni hall_of_fame con nome torneo, location e nome squadra vincitrice
        $result = array_map(function ($hof) {
            // Trova torneo
            $tournament = Tournament::find($hof->tournament_fk);
            $team = Team::find($hof->winning_team_fk);

            return [
                'id' => $hof->id,
                'tournament_fk' => $hof->tournament_fk,
                'winning_team_fk' => $hof->winning_team_fk,
                'victory_date' => $hof->victory_date,
                'tournament_name' => $tournament?->name ?? null,
                'tournament_location' => $tournament?->location ?? null,
                'winning_team_name' => $team?->name ?? null,
            ];
        }, $halls_of_fame);

        Response::success($result)->send();
    } catch (\Exception $e) {
        Response::error(
            'Errore nel recupero della lista halls_of_fame: ' . $e->getMessage(),
            Response::HTTP_INTERNAL_SERVER_ERROR
        )->send();
    }
});

/**
 * POST /api/halls_of_fame - Crea nuova hall_of_fame
 */
Router::post('/halls_of_fame', function () {
    try {
        $request = new Request();
        $data = $request->json();

        // Validazione
        $errors = HallOfFame::validate($data);
        if (!empty($errors)) {
            Response::error('Errore di validazione', Response::HTTP_BAD_REQUEST, $errors)->send();
            return;
        }

        $hall_of_fame = HallOfFame::create($data);

        $hall_of_fame->incrementTeamTournaments();

        Response::success($hall_of_fame, Response::HTTP_CREATED, "HallOfFame creata con successo")->send();
    } catch (\Exception $e) {
        Response::error('Errore durante la creazione della nuova hall_of_fame: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});

Router::match(['put', 'patch'], '/halls_of_fame/{id}', function ($id) {
    try {
        $request = new Request();
        $data = $request->json();

        $hall_of_fame = HallOfFame::find($id);
        if ($hall_of_fame === null) {
            Response::error('HallOfFame non trovata', Response::HTTP_NOT_FOUND)->send();
            return;
        }

        $errors = HallOfFame::validate(array_merge($data, ['id' => $id]));
        if (!empty($errors)) {
            Response::error('Errore di validazione', Response::HTTP_BAD_REQUEST, $errors)->send();
            return;
        }
        if (isset($data['winning_team_fk']) && $data['winning_team_fk'] !== $hall_of_fame->winning_team_fk) {
            $hall_of_fame->decrementTeamTournaments();

            $hall_of_fame->update($data);

            $hall_of_fame->incrementTeamTournaments();
        } else {
            $hall_of_fame->update($data);
        }


        Response::success($hall_of_fame, Response::HTTP_OK, "halls_of_fame aggiornate con successo")->send();
    } catch (\Exception $e) {
        Response::error('Errore durante l\'aggiornamento delle halls_of_fame: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});

Router::delete('/halls_of_fame/{id}', function ($id) {
    try {
        $hall_of_fame = HallOfFame::find($id);
        if ($hall_of_fame === null) {
            Response::error('halls_of_fame non trovate', Response::HTTP_NOT_FOUND)->send();
            return;
        }

        $hall_of_fame->decrementTeamTournaments();

        $hall_of_fame->delete();

        Response::success(null, Response::HTTP_OK, "hall_of_fame eliminata con successo")->send();
    } catch (\Exception $e) {
        Response::error('Errore durante l\'eliminazione delle halls_of_fame: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});
