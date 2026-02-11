<?php

namespace App\Models;

use App\Traits\WithValidate;

class Registration extends BaseModel
{
    use WithValidate;

    public ?int $tournament_fk = null;
    public ?int $team_fk = null;
    public ?string $registration_date = null;

    protected static ?string $table = "registration";

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    protected static function validationRules(): array
    {
        return [
            "tournament_fk" => ["required", "integer", "exists:tournament,id"],
            "team_fk" => ["required", "integer", "min:1", "max:255"],
            "registration_date" => ["required", "string", "regex:/^\d{4}-\d{2}-\d{2}$/"],
        ];
    }
};
