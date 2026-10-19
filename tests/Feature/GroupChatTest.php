<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\HuntGroup;
use App\Models\HuntGroupMessage;
use App\Models\PrivateMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupChatTest extends TestCase
{
    use RefreshDatabase;

    private function group(User $owner, ?User $member = null): HuntGroup
    {
        $char = fn (User $u, string $n) => Character::create([
            'user_id' => $u->id, 'name' => $n, 'level' => 900, 'vocation' => 'Elite Knight', 'world' => 'Test', 'source' => 'manual',
        ]);

        $group = HuntGroup::create([
            'user_id' => $owner->id, 'character_id' => $char($owner, 'Lider')->id, 'max_members' => 4,
            'wanted_vocations' => [], 'starts_at' => null, 'expires_at' => now()->addHours(3), 'status' => 'open',
        ]);
        $group->members()->create(['user_id' => $owner->id, 'character_id' => $group->character_id]);

        if ($member) {
            $group->members()->create(['user_id' => $member->id, 'character_id' => $char($member, 'Membro')->id]);
        }

        return $group;
    }

    public function test_members_can_chat_and_outsiders_cannot(): void
    {
        [$owner, $member, $outsider] = User::factory()->count(3)->create();
        $group = $this->group($owner, $member);

        $this->actingAs($member)->postJson(route('tools.group-finder.chat.store', $group), ['body' => 'bora'])->assertOk();

        $this->actingAs($owner)->getJson(route('tools.group-finder.chat.poll', $group).'?after=0')
            ->assertOk()->assertJsonPath('messages.0.from', 'Membro')->assertJsonPath('messages.0.mine', false);

        $this->actingAs($outsider)->getJson(route('tools.group-finder.chat.poll', $group))->assertForbidden();
        $this->actingAs($outsider)->postJson(route('tools.group-finder.chat.store', $group), ['body' => 'x'])->assertForbidden();
        $this->actingAs($member)->postJson(route('tools.group-finder.chat.store', $group), ['body' => '  '])->assertStatus(422);
    }

    public function test_cannot_send_to_cancelled_group(): void
    {
        $owner = User::factory()->create();
        $group = $this->group($owner);
        $group->update(['status' => 'cancelled']);

        $this->actingAs($owner)->postJson(route('tools.group-finder.chat.store', $group), ['body' => 'oi'])->assertForbidden();
    }

    public function test_feed_returns_new_private_and_group_messages(): void
    {
        [$owner, $member] = User::factory()->count(2)->create();
        $group = $this->group($owner, $member);

        $first = $this->actingAs($owner)->getJson(route('chat.feed'))->assertOk()->assertJsonCount(0, 'items');
        $pm = $first->json('pm_latest');
        $gm = $first->json('gm_latest');

        PrivateMessage::create(['sender_id' => $member->id, 'recipient_id' => $owner->id, 'body' => 'psiu']);
        HuntGroupMessage::create(['hunt_group_id' => $group->id, 'user_id' => $member->id, 'body' => 'olá grupo']);
        HuntGroupMessage::create(['hunt_group_id' => $group->id, 'user_id' => $owner->id, 'body' => 'minha própria']);

        $items = $this->actingAs($owner)->getJson(route('chat.feed')."?pm=$pm&gm=$gm")
            ->assertOk()->assertJsonCount(2, 'items')->json('items');

        $this->assertSame('pm', $items[0]['type']);
        $this->assertSame('Membro', $items[0]['title']);
        $this->assertSame('group', $items[1]['type']);
        $this->assertSame('Membro', $items[1]['from']);
    }

    public function test_widget_and_card_controls_render_for_members(): void
    {
        [$owner, $member] = User::factory()->count(2)->create();
        $this->group($owner, $member);

        $this->actingAs($owner)->get(route('tools.group-finder.index'))
            ->assertOk()->assertSee('id="chat-widget"', false)->assertSee('Mensagem privada')->assertSee('Chat do grupo');

        $this->get('/logout')->assertStatus(405);
        auth()->logout();
        $this->get(route('tools.group-finder.index'))->assertOk()->assertDontSee('id="chat-widget"', false);
    }
}
