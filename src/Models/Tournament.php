<?php

namespace App\Models;

use App\Traits\WithValidate;

class Tournament extends BaseModel
{
    use WithValidate;

    public ?int $id = null;
    public ?string $name = null;
    public ?string $start_date = null;
    public ?bool $is_active = null;
    public ?string $location = null;
    public ?int $number_of_teams = null;

    protected static ?string $table = "tournament";

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    protected static function validationRules(): array
    {
        return [
            "id" => ["nullable", "min:1", "integer"],
            "name" => ["required", "string", "min:1", "max:255"],
            "start_date" => ["required", "string", "regex:/^\d{4}-\d{2}-\d{2}$/"],
            "is_active" => ["required", "boolean"],
            "location" => ["sometimes", "string", "max:100"],
            "number_of_teams" => ["required", "integer", "in:4,8,16,32"]
        ];
    }
};
