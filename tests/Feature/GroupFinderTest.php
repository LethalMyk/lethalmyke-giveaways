<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\Hunt;
use App\Models\HuntGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GroupFinderTest extends TestCase
{
    use RefreshDatabase;

    private function character(User $user, string $name, string $vocation = 'Elite Knight', int $level = 900): Character
    {
        return Character::create([
            'user_id' => $user->id, 'name' => $name, 'level' => $level,
            'vocation' => $vocation, 'world' => 'Malveria', 'source' => 'manual',
        ]);
    }

    private function createGroup(User $user, Character $character, array $overrides = []): HuntGroup
    {
        $this->actingAs($user)->post(route('tools.group-finder.store'), $overrides + [
            'character_id' => $character->id,
            'max_members' => 4,
            'level_min' => 800,
            'level_max' => 1000,
            'wanted_vocations' => ['paladin', 'druid'],
            'mode' => 'now',
        ])->assertSessionHasNoErrors();

        return HuntGroup::query()->latest('id')->firstOrFail();
    }

    public function test_index_is_public_but_creation_requires_login(): void
    {
        $this->get(route('tools.group-finder.index'))->assertOk()->assertSee('Group Finder');
        $this->get(route('tools.group-finder.create'))->assertRedirect();
        $this->post(route('tools.group-finder.store'))->assertRedirect();
    }

    public function test_character_is_imported_from_rubinot(): void
    {
        Http::fake(['rubinot.com.br/*' => Http::response(['player' => [
            'name' => 'Peitodifrango', 'level' => 1057, 'vocation' => 'Elite Knight', 'world' => 'Malveria',
        ]])]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('tools.group-finder.characters.store'), ['name' => 'peitodifrango'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('characters', [
            'user_id' => $user->id, 'name' => 'Peitodifrango', 'level' => 1057,
            'vocation' => 'Elite Knight', 'world' => 'Malveria', 'source' => 'rubinot',
        ]);
    }

    public function test_character_falls_back_to_manual_when_rubinot_is_blocked(): void
    {
        Http::fake(['rubinot.com.br/*' => Http::response('<html>Just a moment...</html>', 403)]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('tools.group-finder.characters.store'), ['name' => 'Fulano'])
            ->assertSessionHasErrors('level');

        $this->actingAs($user)->post(route('tools.group-finder.characters.store'), [
            'name' => 'Fulano', 'level' => 850, 'vocation' => 'Royal Paladin', 'world' => 'Malveria',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('characters', ['name' => 'Fulano', 'source' => 'manual', 'level' => 850]);
    }

    public function test_unknown_character_is_rejected_and_names_are_exclusive(): void
    {
        Http::fake(['rubinot.com.br/*' => Http::response(['error' => 'not found'], 404)]);
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($owner)->post(route('tools.group-finder.characters.store'), ['name' => 'Ninguem'])
            ->assertSessionHasErrors('name');

        $this->character($owner, 'Peitodifrango');
        $this->actingAs($other)->post(route('tools.group-finder.characters.store'), [
            'name' => 'peitodifrango', 'level' => 900, 'vocation' => 'Elite Knight', 'world' => 'Malveria',
        ])->assertSessionHasErrors('name');
    }

    public function test_group_creation_and_joining_rules(): void
    {
        $leader = User::factory()->create();
        $hunt = Hunt::create(['name' => 'Dark Thais', 'level_min' => 400, 'is_published' => true]);
        $group = $this->createGroup($leader, $this->character($leader, 'Lider'), ['hunt_ids' => [$hunt->id]]);

        $this->assertSame(1, $group->members()->count());
        $this->assertTrue($group->hunts->contains($hunt));
        $this->get(route('tools.group-finder.index'))
            ->assertOk()->assertSee('1/4')->assertSee('Dark Thais')->assertSee('Agora');

        $join = function (string $name, string $vocation, int $level) use ($group) {
            $user = User::factory()->create();
            $character = $this->character($user, $name, $vocation, $level);

            return $this->actingAs($user)->post(route('tools.group-finder.join', $group), ['character_id' => $character->id]);
        };

        $join('Baixinho', 'Royal Paladin', 700)->assertSessionHas('success');
        $this->get(route('tools.group-finder.index'))->assertSee('font-bold text-rose-400', false);
        $join('Cavaleiro', 'Elite Knight', 900)->assertSessionHas('error');
        $join('Paladino', 'Royal Paladin', 900)->assertSessionHas('success');
        $join('Druida', 'Elder Druid', 950)->assertSessionHas('success');

        $group->load('members');
        $this->assertSame(4, $group->members->count());
    }

    public function test_group_cannot_exceed_capacity_or_accept_duplicate_users(): void
    {
        $leader = User::factory()->create();
        $group = $this->createGroup($leader, $this->character($leader, 'Lider'), ['max_members' => 2, 'wanted_vocations' => []]);

        $this->actingAs($leader)->post(route('tools.group-finder.join', $group), [
            'character_id' => $leader->characters()->first()->id,
        ])->assertSessionHas('error');

        $first = User::factory()->create();
        $second = User::factory()->create();
        $this->actingAs($first)->post(route('tools.group-finder.join', $group), [
            'character_id' => $this->character($first, 'Um', 'Master Sorcerer')->id,
        ])->assertSessionHas('success');
        $this->actingAs($second)->post(route('tools.group-finder.join', $group), [
            'character_id' => $this->character($second, 'Dois', 'Master Sorcerer')->id,
        ])->assertSessionHas('error');

        $this->assertSame(2, $group->members()->count());
    }

    public function test_user_cannot_join_with_someone_elses_character(): void
    {
        $leader = User::factory()->create();
        $group = $this->createGroup($leader, $this->character($leader, 'Lider'), ['wanted_vocations' => []]);
        $intruder = User::factory()->create();
        $stolen = $this->character(User::factory()->create(), 'Alheio');

        $this->actingAs($intruder)->post(route('tools.group-finder.join', $group), ['character_id' => $stolen->id])
            ->assertNotFound();
    }

    public function test_scheduled_group_lists_separately_and_rejects_past_dates(): void
    {
        $leader = User::factory()->create();
        $character = $this->character($leader, 'Lider');

        $this->actingAs($leader)->post(route('tools.group-finder.store'), [
            'character_id' => $character->id, 'max_members' => 3, 'mode' => 'scheduled',
            'starts_at' => now()->subDay()->format('Y-m-d\TH:i'),
        ])->assertSessionHasErrors('starts_at');

        $when = now()->addDays(2)->setTimezone(HuntGroup::TIMEZONE);
        $this->createGroup($leader, $character, [
            'mode' => 'scheduled', 'starts_at' => $when->format('Y-m-d\TH:i'),
        ]);

        $group = HuntGroup::firstOrFail();
        $this->assertTrue($group->isScheduled());
        $this->get(route('tools.group-finder.index'))->assertSee($when->format('d/m'))->assertSee($when->format('H:i'));
    }

    public function test_only_owner_can_cancel_and_members_can_leave(): void
    {
        $leader = User::factory()->create();
        $group = $this->createGroup($leader, $this->character($leader, 'Lider'), ['wanted_vocations' => []]);
        $member = User::factory()->create();
        $this->actingAs($member)->post(route('tools.group-finder.join', $group), [
            'character_id' => $this->character($member, 'Membro')->id,
        ]);

        $this->actingAs($member)->delete(route('tools.group-finder.cancel', $group))->assertForbidden();
        $this->actingAs($leader)->delete(route('tools.group-finder.leave', $group))->assertForbidden();

        $this->actingAs($member)->delete(route('tools.group-finder.leave', $group));
        $this->assertSame(1, $group->members()->count());

        $this->actingAs($leader)->delete(route('tools.group-finder.cancel', $group));
        $this->assertSame('cancelled', $group->fresh()->status);
        auth()->logout();
        $this->get(route('tools.group-finder.index'))->assertSee('Cancelado')->assertSee('Nenhum grupo ativo agora');
    }

    public function test_authenticated_pages_render(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('tools.group-finder.create'))
            ->assertRedirect(route('tools.group-finder.characters'));

        $this->character($user, 'Peitodifrango');
        Hunt::create(['name' => 'Furious Crater', 'level_min' => 400, 'is_published' => true]);

        $this->actingAs($user)->get(route('tools.group-finder.characters'))->assertOk()->assertSee('Peitodifrango');
        $this->actingAs($user)->get(route('tools.group-finder.create'))->assertOk()->assertSee('Furious Crater');
    }

    public function test_owner_can_edit_group(): void
    {
        $owner = User::factory()->create();
        $char = $this->character($owner, 'Lider');
        $this->actingAs($owner)->post(route('tools.group-finder.store'), [
            'character_id' => $char->id, 'max_members' => 4, 'mode' => 'now',
        ]);
        $group = HuntGroup::firstOrFail();

        $this->actingAs($owner)->get(route('tools.group-finder.edit', $group))->assertOk();

        $this->actingAs($owner)->put(route('tools.group-finder.update', $group), [
            'max_members' => 3, 'level_min' => 800, 'level_max' => 1000, 'mode' => 'now',
            'wanted_vocations' => ['knight'], 'notes' => 'novo',
        ])->assertRedirect(route('tools.group-finder.index'));

        $group->refresh();
        $this->assertSame(3, $group->max_members);
        $this->assertSame(800, $group->level_min);
        $this->assertSame(['knight'], $group->wanted_vocations);

        $this->actingAs($owner)->put(route('tools.group-finder.update', $group), [
            'max_members' => 2, 'mode' => 'now',
        ])->assertRedirect();
        $this->actingAs($owner)->put(route('tools.group-finder.update', $group), [
            'max_members' => 1, 'mode' => 'now',
        ])->assertSessionHasErrors('max_members');

        $other = User::factory()->create();
        $this->actingAs($other)->get(route('tools.group-finder.edit', $group))->assertForbidden();
        $this->actingAs($other)->put(route('tools.group-finder.update', $group), ['max_members' => 3, 'mode' => 'now'])->assertForbidden();
    }

    public function test_hunt_window_and_world_rules(): void
    {
        $owner = User::factory()->create();
        $leader = $this->character($owner, 'Lider');

        $start = now('America/Sao_Paulo')->addDay()->setTime(21, 0);
        $this->actingAs($owner)->post(route('tools.group-finder.store'), [
            'character_id' => $leader->id, 'max_members' => 4, 'mode' => 'scheduled',
            'starts_at' => $start->format('Y-m-d\TH:i'), 'ends_time' => '00:00',
        ])->assertRedirect(route('tools.group-finder.index'));

        $group = HuntGroup::firstOrFail();
        $this->assertSame('21:00', $group->starts_at->timezone('America/Sao_Paulo')->format('H:i'));
        $this->assertSame('00:00', $group->ends_at->timezone('America/Sao_Paulo')->format('H:i'));
        $this->assertTrue($group->ends_at->gt($group->starts_at));
        $this->assertTrue($group->expires_at->equalTo($group->ends_at));

        $this->actingAs($owner)->get(route('tools.group-finder.index'))
            ->assertSee('21:00 às 00:00')->assertSee('Malveria');

        $this->actingAs($owner)->post(route('tools.group-finder.store'), [
            'character_id' => $leader->id, 'max_members' => 4, 'mode' => 'scheduled',
            'starts_at' => $start->format('Y-m-d\TH:i'), 'ends_time' => '10:00',
        ])->assertSessionHasErrors('ends_time');

        $other = User::factory()->create();
        $foreign = $this->character($other, 'Gringo');
        $foreign->update(['world' => 'Outro']);

        $this->actingAs($other)->post(route('tools.group-finder.join', $group), ['character_id' => $foreign->id])
            ->assertSessionHas('error');
        $this->assertSame(1, $group->members()->count());

        $this->actingAs($other)->get(route('tools.group-finder.index'))->assertSee('não tem personagem no mundo');

        $foreign->update(['world' => 'malveria']);
        $this->actingAs($other)->post(route('tools.group-finder.join', $group), ['character_id' => $foreign->id])
            ->assertSessionHas('success');
    }

    public function test_leader_can_kick_close_and_history_lists_finished_groups(): void
    {
        [$owner, $member, $outsider] = User::factory()->count(3)->create();
        $leader = $this->character($owner, 'Lider');
        $guest = $this->character($member, 'Membro');
        $this->character($outsider, 'Fora');

        $this->actingAs($owner)->post(route('tools.group-finder.store'), [
            'character_id' => $leader->id, 'max_members' => 4, 'mode' => 'now',
        ]);
        $group = HuntGroup::firstOrFail();
        $this->actingAs($member)->post(route('tools.group-finder.join', $group), ['character_id' => $guest->id]);
        $membership = $group->members()->where('user_id', $member->id)->firstOrFail();

        $this->actingAs($member)->delete(route('tools.group-finder.kick', [$group, $membership]))->assertForbidden();
        $this->actingAs($owner)->delete(route('tools.group-finder.kick', [$group, $group->members()->where('user_id', $owner->id)->first()]))->assertStatus(422);

        $this->actingAs($owner)->delete(route('tools.group-finder.kick', [$group, $membership]))->assertRedirect();
        $this->assertSame(1, $group->members()->count());

        $this->actingAs($member)->post(route('tools.group-finder.join', $group), ['character_id' => $guest->id])
            ->assertSessionHas('error');
        $this->assertSame(1, $group->members()->count());

        $this->actingAs($outsider)->post(route('tools.group-finder.close', $group), ['status' => 'completed'])->assertForbidden();
        $this->actingAs($owner)->post(route('tools.group-finder.close', $group), ['status' => 'bogus'])->assertSessionHasErrors('status');

        $this->actingAs($owner)->post(route('tools.group-finder.close', $group), ['status' => 'completed'])->assertRedirect();
        $this->assertSame('completed', $group->fresh()->status);

        auth()->logout();
        $this->get(route('tools.group-finder.index'))->assertSee('Lider')->assertSee('Grupo completo');
        $this->actingAs($owner)->get(route('tools.group-finder.history'))->assertOk()->assertSee('Grupo completo')->assertSee('Lider');
        $this->actingAs($outsider)->get(route('tools.group-finder.history'))->assertOk()->assertSee('Lider')->assertDontSee('Reativar grupo');
        $this->actingAs($owner)->post(route('tools.group-finder.close', $group), ['status' => 'expired'])->assertForbidden();

        $old = HuntGroup::create([
            'user_id' => $owner->id, 'character_id' => $leader->id, 'max_members' => 3, 'wanted_vocations' => [],
            'expires_at' => now()->subHour(), 'status' => 'open',
        ]);
        $old->members()->create(['user_id' => $owner->id, 'character_id' => $leader->id]);
        $this->actingAs($owner)->get(route('tools.group-finder.history'))->assertSee('Hunt expirada');
    }

    public function test_leader_can_add_and_remove_guest_character(): void
    {
        [$owner, $other] = User::factory()->count(2)->create();
        $leader = $this->character($owner, 'Lider');
        $group = $this->createGroup($owner, $leader);

        $this->actingAs($other)->post(route('tools.group-finder.guests.store', $group), ['guest_name' => 'Fake'])->assertForbidden();
        $this->actingAs($owner)->post(route('tools.group-finder.guests.store', $group), ['guest_name' => 'Fake Boy', 'guest_vocation' => 'Elder Druid', 'guest_level' => 900])->assertSessionHas('success');
        $this->actingAs($owner)->post(route('tools.group-finder.guests.store', $group), ['guest_name' => 'fake boy'])->assertSessionHas('error');
        $this->actingAs($owner)->post(route('tools.group-finder.guests.store', $group), ['guest_name' => 'X', 'guest_vocation' => 'Bogus'])->assertSessionHasErrors(['guest_name', 'guest_vocation']);

        $this->actingAs($owner)->get(route('tools.group-finder.index'))->assertSee('Fake Boy')->assertSee('fictício');
        $this->actingAs($other)->get(route('tools.group-finder.index'))->assertOk();

        $guest = $group->members()->whereNotNull('guest_name')->firstOrFail();
        $this->actingAs($other)->delete(route('tools.group-finder.kick', [$group, $guest]))->assertForbidden();
        $this->actingAs($owner)->delete(route('tools.group-finder.kick', [$group, $guest]))->assertRedirect();
        $this->assertSame(1, $group->members()->count());
        $this->assertDatabaseCount('hunt_group_bans', 0);
    }

    public function test_user_can_edit_own_character_level(): void
    {
        [$owner, $other] = User::factory()->count(2)->create();
        $char = $this->character($owner, 'Lider');

        $this->actingAs($other)->patch(route('tools.group-finder.characters.update', $char), ['level' => 500])->assertForbidden();
        $this->actingAs($owner)->patch(route('tools.group-finder.characters.update', $char), ['level' => 0])->assertSessionHasErrors('level');
        $this->actingAs($owner)->patch(route('tools.group-finder.characters.update', $char), ['level' => 1234])->assertSessionHas('success');

        $this->assertSame(1234, $char->fresh()->level);
        $this->actingAs($owner)->get(route('tools.group-finder.characters'))->assertOk()->assertSee('value="1234"', false);
    }

    public function test_leader_can_reopen_group_within_twenty_minutes(): void
    {
        $leader = User::factory()->create();
        $group = $this->createGroup($leader, $this->character($leader, 'Lider'));
        $this->actingAs($leader)->post(route('tools.group-finder.close', $group), ['status' => 'completed']);
        $this->assertFalse($group->fresh()->isActive());

        $this->actingAs($leader)->get(route('tools.group-finder.history'))->assertSee('Reativar grupo');
        $this->actingAs(User::factory()->create())->post(route('tools.group-finder.reopen', $group))->assertForbidden();
        $this->actingAs($leader)->post(route('tools.group-finder.reopen', $group))->assertSessionHas('success');
        $this->assertTrue($group->fresh()->isActive());

        $this->actingAs($leader)->post(route('tools.group-finder.close', $group), ['status' => 'expired']);
        HuntGroup::query()->whereKey($group->id)->update(['updated_at' => now()->subMinutes(21)]);
        $this->actingAs($leader)->post(route('tools.group-finder.reopen', $group))->assertSessionHas('error');
        $this->assertFalse($group->fresh()->isActive());
    }

    public function test_expired_groups_are_hidden(): void
    {
        $leader = User::factory()->create();
        $group = $this->createGroup($leader, $this->character($leader, 'Lider'));
        $group->update(['expires_at' => now()->subMinute()]);

        auth()->logout();
        $this->get(route('tools.group-finder.index'))->assertSee('Hunt expirada')->assertSee('Nenhum grupo ativo agora');
    }
}
