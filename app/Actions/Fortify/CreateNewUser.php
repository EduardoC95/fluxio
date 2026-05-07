<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Spatie\Permission\Models\Role;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'mobile' => ['nullable', 'string', 'max:255'],
            'password' => $this->passwordRules(),
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'mobile' => $input['mobile'] ?? null,
            'password' => $input['password'],
            'is_active' => true,
        ]);

        if (User::query()->count() === 1 && class_exists(Role::class)) {
            $adminRole = Role::query()->firstOrCreate(
                ['name' => 'Administrador', 'guard_name' => 'web'],
                ['is_active' => true],
            );

            $user->assignRole($adminRole);
        }

        return $user;
    }
}
