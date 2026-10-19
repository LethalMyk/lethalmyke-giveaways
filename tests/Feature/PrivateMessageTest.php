<?php

namespace Tests\Feature;

use App\Models\PrivateMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivateMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_messages(): void
    {
        $user = User::factory()->create();

        $this->get(route('messages.index'))->assertRedirect();
        $this->get(route('messages.show', $user))->assertRedirect();
        $this->post(route('messages.store', $user), ['body' => 'oi'])->assertRedirect();
    }

    public function test_send_read_and_poll_messages(): void
    {
        [$a, $b] = User::factory()->count(2)->create();

        $this->actingAs($a)->postJson(route('messages.store', $b), ['body' => '<b>oi</b>'])->assertOk();
        $this->assertSame(1, PrivateMessage::whereNull('read_at')->count());

        $this->actingAs($b)->get(route('messages.index'))->assertOk()->assertSee('&lt;b&gt;oi&lt;/b&gt;', false);
        $this->actingAs($b)->get(route('messages.show', $a))->assertOk();
        $this->assertSame(0, PrivateMessage::whereNull('read_at')->count());

        $this->actingAs($b)->postJson(route('messages.store', $a), ['body' => 'e aí'])->assertOk();
        $this->actingAs($a)->getJson(route('messages.poll', $b).'?after=0')
            ->assertOk()->assertJsonCount(2, 'messages')->assertJsonPath('messages.1.body', 'e aí');
    }

    public function test_validation_and_privacy(): void
    {
        [$a, $b, $c] = User::factory()->count(3)->create();

        $this->actingAs($a)->postJson(route('messages.store', $b), ['body' => '   '])->assertStatus(422);
        $this->actingAs($a)->postJson(route('messages.store', $b), ['body' => str_repeat('x', 1001)])->assertStatus(422);
        $this->actingAs($a)->postJson(route('messages.store', $a), ['body' => 'eu'])->assertNotFound();

        $this->actingAs($a)->postJson(route('messages.store', $b), ['body' => 'segredo']);
        $this->actingAs($c)->getJson(route('messages.poll', $a).'?after=0')->assertJsonCount(0, 'messages');
        $this->assertNull(PrivateMessage::first()->read_at);
    }
}
