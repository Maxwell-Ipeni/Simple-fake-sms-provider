<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use App\Services\SmsCallbackServiceInterface;
use Tests\TestCase;

class FakeSmsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Cache::forget('messages');
    }

    public function test_cache_initializes_when_empty()
    {
        $resp = $this->getJson('/api/cache-watch');
        $resp->assertStatus(200)->assertJsonStructure(['messages']);
        $this->assertIsArray($resp->json('messages'));
    }

    public function test_get_message_validation()
    {
        $resp = $this->postJson('/api/get-message', []);
        $resp->assertStatus(422)->assertJsonStructure(['error','details']);
    }

    public function test_send_message_validation_requires_text_or_last_number()
    {
        // no messages, no number provided
        $resp = $this->postJson('/api/send-message', ['text' => '']);
        $resp->assertStatus(422);

        // missing text
        $resp2 = $this->postJson('/api/send-message', ['number' => '123']);
        $resp2->assertStatus(422);
    }

    public function test_persists_messages_and_calls_callback_successfully()
    {
        // bind a mock callback service that returns delivered=true
        $this->app->bind(SmsCallbackServiceInterface::class, function () {
            return new class implements SmsCallbackServiceInterface {
                public function sendCallback(array $payload): array
                {
                    return ['delivered' => true];
                }
            };
        });

        $post = $this->postJson('/api/send-message', ['number' => '1000', 'text' => 'hello from provider']);
        $post->assertStatus(200)->assertJson(['status' => 'ok', 'delivered' => true]);

        $watch = $this->getJson('/api/cache-watch');
        $watch->assertStatus(200);
        $this->assertCount(1, $watch->json('messages'));
        $this->assertEquals('1000', $watch->json('messages.0.number'));
    }

    public function test_callback_failure_returns_delivered_false_but_still_persists()
    {
        // bind a mock callback service that simulates a failure
        $this->app->bind(SmsCallbackServiceInterface::class, function () {
            return new class implements SmsCallbackServiceInterface {
                public function sendCallback(array $payload): array
                {
                    return ['delivered' => false, 'error' => 'simulated-failure'];
                }
            };
        });

        $post = $this->postJson('/api/send-message', ['number' => '2000', 'text' => 'hello fail']);
        $post->assertStatus(200)->assertJson(['status' => 'ok', 'delivered' => false]);

        $watch = $this->getJson('/api/cache-watch');
        $this->assertCount(1, $watch->json('messages'));
    }

    public function test_get_message_persists_user_messages()
    {
        $resp = $this->postJson('/api/get-message', ['number' => '3000', 'text' => 'user hello']);
        $resp->assertStatus(200)->assertJson(['status' => 'ok']);

        $watch = $this->getJson('/api/cache-watch');
        $this->assertCount(1, $watch->json('messages'));
        $this->assertEquals(1, $watch->json('messages.0.sender'));
    }
}
