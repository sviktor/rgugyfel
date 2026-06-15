<?php

namespace Database\Factories;

use App\Models\CustomerUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerUser>
 */
class CustomerUserFactory extends Factory
{
	protected $model = CustomerUser::class;

	/**
	 * A verified, active portal account (password "Password123").
	 *
	 * @return array<string, mixed>
	 */
	public function definition(): array
	{
		return [
			'name'              => $this->faker->name(),
			'email'             => $this->faker->unique()->safeEmail(),
			'phone'             => '+36 30 123 4567',
			'password'          => 'Password123', // hashed by the model cast
			'email_verified_at' => now(),
			'status'            => 1,
		];
	}

	/**
	 * A not-yet-confirmed account.
	 *
	 * @example  CustomerUser::factory()->unverified()->create();
	 */
	public function unverified(): static
	{
		return $this->state(fn () => ['email_verified_at' => null]);
	}

	/**
	 * A currently locked-out account.
	 *
	 * @example  CustomerUser::factory()->locked()->create();
	 */
	public function locked(): static
	{
		return $this->state(fn () => ['locked_until' => now()->addDay()]);
	}
}
