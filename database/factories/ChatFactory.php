<?php
declare(strict_types=1);

namespace Marvin\Ask\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Marvin\Ask\Models\Chat;
use Marvin\Ask\Models\User;

/**
 * @extends Factory<Chat>
 */
class ChatFactory extends Factory
{
    protected $model = Chat::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        return [
            'user_id' => fake()->boolean(5) ? User::factory() : null,
            'deleted_at' => fake()->boolean(15) ? now() : null,
        ];
    }
}
