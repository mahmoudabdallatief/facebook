<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $names = ['Ahmed Ali', 'Sara Hassan', 'Omar Khaled', 'Mona Ibrahim', 'Youssef Adel', 'Nour Mohamed', 'Hana Samir', 'Karim Fathy', 'Laila Mostafa', 'Tarek Nabil'];
        $bios = ['Coffee lover ☕', 'Building cool things.', 'Photography & travel.', 'Reading, learning, sharing.', 'Hello there!', null];

        return [
            'name' => fake()->randomElement($names),
            'email' => fake()->unique()->safeEmail(),
            'bio' => fake()->randomElement($bios),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
