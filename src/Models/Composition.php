<?php

namespace App\Models;

use App\Traits\WithValidate;

class Composition extends BaseModel
{
    use WithValidate;

    public ?int $athlete_fk = null;
    public ?int $team_fk = null;
    public ?string $start_date = null;
    public ?string $end_date = null;   // null = composizione ancora attiva
    public ?string $role = null;
    public ?int $jersey_number = null;

    protected static ?string $table = "composition";

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    protected static function validationRules(): array
    {
        return [
            "athlete_fk" => ["required", "integer", "exists:athlete,id"],
            "team_fk" => ["required", "integer", "exists:team,id"],
            "start_date" => ["required", "regex:/^\d{4}-\d{2}-\d{2}$/"],
            "end_date" => ["sometimes", "regex:/^\d{4}-\d{2}-\d{2}$/"],
            "role" => ["sometimes", "string", "min:1", "max:30"],
            "jersey_number" => ["sometimes", "integer", "min:1", "max:99"],
        ];
    }
}