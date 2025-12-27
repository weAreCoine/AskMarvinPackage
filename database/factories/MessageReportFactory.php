<?php
declare(strict_types=1);

namespace Marvin\Ask\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Marvin\Ask\Models\Message;
use Marvin\Ask\Models\MessageReport;

/**
 * @extends Factory<MessageReport>
 */
class MessageReportFactory extends Factory
{
    protected $model = MessageReport::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $promptTokens = $this->faker->numberBetween(100, 1000);
        $completionTokens = $this->faker->numberBetween(50, 500);
        $totalTokens = $promptTokens + $completionTokens;

        return [
            'message_id' => Message::factory(),
            'model_name' => $this->faker->randomElement(['gpt-3.5-turbo', 'gpt-4', 'claude-2']),
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'response_time_ms' => $this->faker->numberBetween(500, 5000),
            'embedding_time_ms' => $this->faker->numberBetween(50, 500),
            'retrieved_chunks' => $this->faker->numberBetween(1, 10),
            'used_chunks_ids' => json_encode($this->faker->randomElements([
                '1', '2', '3', '4', '5', '6', '7', '8', '9', '10'
            ], $this->faker->numberBetween(1, 5))),
            'chat_context_size' => $this->faker->numberBetween(1, 20),
            'source' => $this->faker->randomElement(['api', 'web', 'mobile']),
        ];
    }
}
