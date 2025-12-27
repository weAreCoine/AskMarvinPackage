<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Marvin\Ask\Models\Chat;
use Marvin\Ask\Models\User;

/**
 * @extends Factory<Chat>
 */
class ChatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sessionId = Str::random(40);

        DB::table('sessions')->insert([
            'id' => $sessionId,
            'user_id' => null,
            'ip_address' => fake()->ipv4,
            'user_agent' => fake()->userAgent,
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->timestamp,
        ]);

        return [
            'user_id' => fake()->boolean(5) ? User::factory() : null,
            'deleted_at' => fake()->boolean(15) ? now() : null,
        ];
    }
}
