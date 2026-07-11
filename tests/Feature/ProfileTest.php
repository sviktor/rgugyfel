<?php

namespace Tests\Feature;

use App\Models\CustomerUser;
use Tests\FeatureTestCase;

/**
 * The Profil -> Személyes adatok tab: the prefill splits the account `name`
 * as family = everything but the last word, given = the last word (the
 * lossless inverse of the register-time concatenation), and a save rebuilds
 * the same full name - a 3-word name must survive an unrelated edit (E3-M5).
 */
class ProfileTest extends FeatureTestCase
{
	public function test_the_profile_prefill_keeps_a_multi_word_family_name(): void
	{
		$user = CustomerUser::factory()->create(['name' => 'Szabóné Kis Éva']);

		$this->actingAs($user, 'customer')
			->get(route('profile'))
			->assertOk()
			->assertSee('value="Szabóné Kis"', false)
			->assertSee('value="Éva"', false);
	}

	public function test_a_profile_save_round_trips_the_full_name(): void
	{
		$user = CustomerUser::factory()->create(['name' => 'Szabóné Kis Éva']);

		$this->actingAs($user, 'customer')
			->post(route('profile.personal'), [
				'last_name'  => 'Szabóné Kis',
				'first_name' => 'Éva',
				'phone'      => '+36 30 123 4567',
				'birth_date' => '1980-02-03',
			])
			->assertRedirect(route('profile', ['tab' => 'personal']));

		$this->assertSame('Szabóné Kis Éva', $user->fresh()->name);
	}
}
