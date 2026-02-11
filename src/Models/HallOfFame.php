<?php

namespace App\Models;

use App\Traits\WithValidate;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\Composition;
use App\Models\Athlete;

class HallOfFame extends BaseModel
{
    use WithValidate;

    public ?int $tournament_fk = null;
    public ?int $winning_team_fk = null;
    public ?string $victory_date = null;

    protected static ?string $table = "hall_of_fame";

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    protected static function validationRules(): array
    {
        return [
            "tournament_fk" => ["required", "integer", "exists:tournament,id"],
            "winning_team_fk" => ["required", "integer", "exists:team,id"],
            "victory_date" => ["required", "string", "regex:/^\d{4}-\d{2}-\d{2}$/"],
        ];
    }

    // Relazione con Team vincente
    public function winningTeam(): ?Team
    {
        return Team::find($this->winning_team_fk);
    }

    // Relazione con Tournament
    public function tournament(): ?Tournament
    {
        return Tournament::find($this->tournament_fk);
    }

    public function incrementTeamTournaments(): bool
    {
        $team = $this->winningTeam();
        if (!$team) throw new \Exception("Team non trovato");

        // Incrementa trofei della squadra
        $team->tournaments_won = ($team->tournaments_won ?? 0) + 1;
        $team->save();

        // Incrementa trofei di tutti gli atleti ATTIVI nella squadra al momento della vittoria
        $activeCompositions = Composition::where('team_fk', $team->id);
        foreach ($activeCompositions as $comp) {
            // Solo atleti attivi (senza end_date o con end_date >= victory_date)
            if ($comp->end_date === null || $comp->end_date > $this->victory_date) {
                $athlete = Athlete::find($comp->athlete_fk);
                if ($athlete) {
                    $athlete->tournaments_won = ($athlete->tournaments_won ?? 0) + 1;
                    $athlete->save();
                }
            }
        }

        return true;
    }

    public function decrementTeamTournaments(): bool
    {
        $team = $this->winningTeam();
        if (!$team) throw new \Exception("Team non trovato");

        // Decrementa trofei della squadra
        $team->tournaments_won = max(0, ($team->tournaments_won ?? 0) - 1);
        $team->save();

        // Decrementa trofei di tutti gli atleti che erano attivi al momento della vittoria
        $activeCompositions = Composition::where('team_fk', $team->id);
        foreach ($activeCompositions as $comp) {
            // Solo atleti attivi (senza end_date o con end_date >= victory_date)
            if ($comp->end_date === null || $comp->end_date > $this->victory_date) {
                $athlete = Athlete::find($comp->athlete_fk);
                if ($athlete) {
                    $athlete->tournaments_won = max(0, ($athlete->tournaments_won ?? 0) - 1);
                    $athlete->save();
                }
            }
        }

        return true;
    }
}
