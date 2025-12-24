<?php

use Marvin\Ask\Abstracts\LlmProviderClient;
use Marvin\Ask\Models\Chat;
use Marvin\Ask\Models\Message;
use Marvin\Ask\Services\LlmService;
use Prism\Prism\Enums\Provider;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;


test('client is injected from app service provider', function () {
    expect(app(LlmService::class)->llmClient)->toBeInstanceOf(LlmProviderClient::class);
});

it('can generate an embed vector', function () {
    $embed = app(LlmService::class)->embed('hello world');
    expect($embed)->toBeArray()->each->toBeFloat('Generate embed vector should return an array of floats');
});

test('models and providers are correctly loaded', function () {
    $service = app(LlmService::class);
    expect($service->llmClient->chatModel)->toBeString()
        ->and($service->llmClient->chatProvider)->toBeInstanceOf(Provider::class)
        ->and($service->llmClient->embedModel)->toBeString()
        ->and($service->llmClient->embedProvider)->toBeInstanceOf(Provider::class);
});
test('text method return correct response', function () {
    expect(app(LlmService::class)
        ->text('Ciao, come ti chiami?', stream: false))
        ->toBeString('Response is not a string');
});
it('can convert Chat model to conversation array', function () {
    $chat = Chat::factory()->has(Message::factory(10))->create();

    $conversation = app(LlmService::class)->conversationFromChat($chat);
    expect($conversation)->toBeArray()
        ->and(count($conversation))->toBe(10)
        ->and($conversation[0])->toBeInstanceOf(UserMessage::class)
        ->and($conversation[1])->toBeInstanceOf(AssistantMessage::class);
});
