<?php

namespace Database\Factories;

use App\Enums\NotificationStatus;
use App\Models\NotificationMessage;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationMessage>
 */
class NotificationMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'project_id' => Project::factory(),
            'template_id' => null,
            'event_type' => 'order.paid',
            'channel' => 'email',
            'recipient' => fake()->safeEmail(),
            'payload' => [
                'order_no' => 'ORD-001',
            ],
            'status' => NotificationStatus::QUEUED,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => NotificationStatus::PENDING,
        ]);
    }

    public function queued(): static
    {
        return $this->state(fn () => [
            'status' => NotificationStatus::QUEUED,
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn () => [
            'status' => NotificationStatus::PROCESSING,
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn () => [
            'status' => NotificationStatus::SENT,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => NotificationStatus::FAILED,
        ]);
    }
}
