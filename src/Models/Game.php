<?php

namespace App\Models;
use App\Traits\WithValidate;

class Game extends BaseModel
{
    use WithValidate;

    public ?int $id = null;
    public ?int $tournament_fk = null;
    public ?int $match_number = null;
    public ?int $round = null;
    public ?int $position_in_round = null;
    public ?int $home_team_fk = null;
    public ?int $away_team_fk = null;
    public ?int $home_score = null;
    public ?int $away_score = null;
    public ?string $match_date = null;
    public ?bool $completed = null;

    protected static ?string $table = "game";

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    protected static function validationRules(): array
    {
        return [
            "id" => ["sometimes", "integer", "min:1"],
            "tournament_fk" => ["required", "integer", "exists:tournament,id"],
            "match_number" => ["required", "integer", "min:1"],
            "round" => ["required", "integer", "min:1"],
            "position_in_round" => ["required", "integer", "min:1"],
            "home_team_fk" => ["sometimes", "integer", "exists:team,id", "different:away_team_fk"],
            "away_team_fk" => ["sometimes", "integer", "exists:team,id", "different:home_team_fk"],
            "home_score" => ["sometimes", "integer", "min:0"],
            "away_score" => ["sometimes", "integer", "min:0"],
            "match_date" => ["sometimes", "string", "regex:/^\d{4}-\d{2}-\d{2}$/"],
            "completed" => ["nullable", "boolean"]
        ];
    }

    public function tournament()
    {
        return $this->belongsTo(Tournament::class, 'tournament_fk');
    }

    public function homeTeam()
    {
        return $this->belongsTo(Team::class, 'home_team_fk');
    }

    public function awayTeam()
    {
        return $this->belongsTo(Team::class, 'away_team_fk');
    }
}