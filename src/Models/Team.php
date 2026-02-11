<?php

namespace App\Models;

use App\Traits\WithValidate;

class Team extends BaseModel
{
    use WithValidate;

    public ?string $name = null;
    public ?string $logo = null;
    public ?int $tournaments_won = null;

    protected static ?string $table = "team";

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    protected static function validationRules(): array
    {
        return [
            "name" => ["required", "min:1", "max:50", "unique:team,name"],
            "logo" => ["sometimes", "string", "min:1", "max:255"],
            "tournaments_won" => ["sometimes", "integer", "min:0",],
        ];
    }
};
