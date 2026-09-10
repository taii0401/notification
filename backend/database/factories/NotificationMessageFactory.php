<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

use App\Models\NotificationMessage;
use App\Models\Project;

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
            'status' => 'queued',
        ];
    }
}
