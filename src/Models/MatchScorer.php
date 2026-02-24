<?php

namespace App\Models;

class MatchScorer extends BaseModel
{
    public ?int $game_fk = null;
    public ?int $athlete_fk = null;
    public ?int $team_fk = null;
    public ?int $goals = null;

    protected static ?string $table = "match_scorer";
}
