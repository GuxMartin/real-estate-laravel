<?php

namespace App\Actions\Fortify;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Exception;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param array<string, string> $input
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public function create(array $input): User
    {
        try {
            Validator::make($input, [
                'name'  => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique(User::class),
                ],
                'password' => $this->passwordRules(),
                'role' => ['required', 'string', Rule::in(['tenant', 'buyer', 'seller', 'landlord', 'contractor'])],
            ])->validate();

            return DB::transaction(function () use ($input) {
                return tap(User::create([
                    'name'     => $input['name'],
                    'email'    => $input['email'],
                    'password' => Hash::make($input['password']),
                ]), function (User $user) use ($input) {
                    $team = $this->assignOrCreateTeam($user);
                    $user->switchTeam($team);
                    setPermissionsTeamId($team->id);
                    $user->assignRole($input['role']);
                });
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('User creation validation failed', [
                'errors' => $e->errors(),
                'input' => array_diff_key($input, array_flip(['password'])),
            ]);
            throw $e;
        } catch (Exception $e) {
            Log::error('User creation failed', [
                'message' => $e->getMessage(),
                'input' => array_diff_key($input, array_flip(['password'])),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new Exception('Failed to create user. Please try again later.');
        }
    }

    /**
     * Assign the user to the first team or create a personal team.
     *
     * @throws \Exception
     */
    protected function assignOrCreateTeam(User $user): Team
    {
        try {
            return $user->ownedTeams()->create([
                'name' => $user->name . "'s Team",
                'personal_team' => true,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to create personal team', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new Exception('Failed to create personal team. Please try again later.');
        }
    }
}