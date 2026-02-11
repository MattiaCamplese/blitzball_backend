<?php

namespace App\Models;

use App\Traits\WithValidate;

class Athlete extends BaseModel
{
    use WithValidate;

    public ?string $fiscal_code = null;
    public ?string $first_name = null;
    public ?string $last_name = null;
    public ?string $birth_place = null;
    public ?string $birth_date = null;
    public ?int $tournaments_won = 0;

    protected static ?string $table = "athlete";

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    protected static function validationRules(): array
    {
        return [
            "fiscal_code" => ["required", "string", "min:16", "max:16"],
            "first_name" => ["required", "string", "min:1", "max:50"],
            "last_name" => ["required", "string", "min:1", "max:50"],
        ];
    }
};
