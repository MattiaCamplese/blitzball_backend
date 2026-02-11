<?php

namespace App\Services;

use App\Models\Athlete;
use App\Models\Composition;
use App\Models\Team;

class CompositionService
{
    public static function getByAthlete(int $athleteId): array
    {
        $athlete = Athlete::find($athleteId);
        if (!$athlete) {
            throw new \Exception("Atleta non trovato");
        }

        $compositions = Composition::where('athlete_fk', $athleteId);

        return array_map(function (Composition $c) {
            $team = Team::find($c->team_fk);
            $data = $c->toArray();
            $data['team_name'] = $team?->name ?? null;
            $data['team_logo'] = $team?->logo ?? null;
            return $data;
        }, $compositions);
    }

    public static function getByTeam(int $teamId): array
    {
        $team = Team::find($teamId);
        if (!$team) {
            throw new \Exception("Squadra non trovata");
        }

        $compositions = Composition::where('team_fk', $teamId);

        return array_map(function (Composition $c) {
            $athlete = Athlete::find($c->athlete_fk);
            $data = $c->toArray();
            $data['athlete_first_name'] = $athlete?->first_name ?? null;
            $data['athlete_last_name']  = $athlete?->last_name  ?? null;
            $data['athlete_fiscal_code'] = $athlete?->fiscal_code ?? null;
            $data['athlete_tournaments_won'] = $athlete?->tournaments_won ?? 0;
            return $data;
        }, $compositions);
    }

    public static function assign(array $data): Composition
    {
        $athleteId = $data['athlete_fk'] ?? null;
        $teamId    = $data['team_fk']    ?? null;

        // Verifica esistenza atleta e squadra
        if (!Athlete::find($athleteId)) {
            throw new \Exception("Atleta non trovato");
        }
        if (!Team::find($teamId)) {
            throw new \Exception("Squadra non trovata");
        }

        // Controlla se l'atleta è già attivo in qualche squadra
        $existing = Composition::where('athlete_fk', $athleteId);
        foreach ($existing as $comp) {
            if (empty($comp->end_date)) {
                throw new \Exception("L'atleta è già attivo in un'altra squadra");
            }
        }

        // Controlla se il numero di maglia è già usato nella squadra
        if (!empty($data['jersey_number'])) {
            $teamCompositions = Composition::where('team_fk', $teamId);
            foreach ($teamCompositions as $comp) {
                if (empty($comp->end_date) &&
                    $comp->jersey_number == $data['jersey_number']) {
                    throw new \Exception("Il numero di maglia {$data['jersey_number']} è già usato in questa squadra");
                }
            }
        }

        $errors = Composition::validate($data);
        if (!empty($errors)) {
            throw new \InvalidArgumentException(json_encode($errors));
        }

        return Composition::create($data);
    }

    public static function terminate(int $compositionId): Composition
    {
        $composition = Composition::find($compositionId);
        if (!$composition) {
            throw new \Exception("Composizione non trovata");
        }

        if (!empty($composition->end_date)) {
            throw new \Exception("La composizione è già terminata");
        }

        $composition->update(['end_date' => date('Y-m-d')]);
        return $composition;
    }
}