<?php
declare(strict_types=1);

namespace Marvin\Ask\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Marvin\Ask\Enums\MessageType;
use Marvin\Ask\Models\Chat;
use Marvin\Ask\Models\Message;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    public static MessageType $type = MessageType::USER;
    protected $model = Message::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $data = [
            'chat_id' => Chat::factory(),
            'content' => $this->faker->paragraph(),
            'type' => self::$type->value,
        ];

        self::$type = self::$type === MessageType::USER ? MessageType::ASSISTANT : MessageType::USER;

        return $data;
    }
}
