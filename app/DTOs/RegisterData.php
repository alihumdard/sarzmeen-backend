<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\UserRole;

final readonly class RegisterData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public UserRole $role,
        public ?string $phone = null,
        public ?string $city = null,
        public ?string $agencyName = null,
        public ?string $agencyType = null,
        public ?string $title = null,
    ) {}
}
