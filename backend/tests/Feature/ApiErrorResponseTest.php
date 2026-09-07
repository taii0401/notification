<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiErrorResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_key_is_required(): void
    {
        $this->postJson('/api/notifications', $this->notificationPayload())
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => 'API Key is required.',
            ]);
    }

    public function test_api_key_must_be_valid(): void
    {
        $this->withToken('invalid-api-key')
            ->postJson('/api/notifications', $this->notificationPayload())
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => 'Invalid API Key.',
            ]);
    }

    public function test_api_key_must_be_active(): void
    {
        [$project, $plainTextKey] = $this->createProjectAndApiKey(
            apiKeyStatus: 'inactive'
        );

        $this->withToken($plainTextKey)
            ->postJson('/api/notifications', $this->notificationPayload())
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => 'API Key is not active.',
            ]);
    }

    public function test_api_key_must_not_be_expired(): void
    {
        [$project, $plainTextKey] = $this->createProjectAndApiKey(
            expiresAt: now()->subMinute()
        );

        $this->withToken($plainTextKey)
            ->postJson('/api/notifications', $this->notificationPayload())
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => 'API Key has expired.',
            ]);
    }

    public function test_api_key_project_must_be_available(): void
    {
        [$project, $plainTextKey] = $this->createProjectAndApiKey();
        $project->delete();

        $this->withToken($plainTextKey)
            ->postJson('/api/notifications', $this->notificationPayload())
            ->assertForbidden()
            ->assertExactJson([
                'message' => 'Project is unavailable.',
            ]);
    }

    public function test_api_key_project_must_be_active(): void
    {
        [$project, $plainTextKey] = $this->createProjectAndApiKey(
            projectStatus: 'inactive'
        );

        $this->withToken($plainTextKey)
            ->postJson('/api/notifications', $this->notificationPayload())
            ->assertForbidden()
            ->assertExactJson([
                'message' => 'Project is not active.',
            ]);
    }

    public function test_validation_error_uses_the_shared_message(): void
    {
        [$project, $plainTextKey] = $this->createProjectAndApiKey();

        $this->withToken($plainTextKey)
            ->postJson('/api/notifications', [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'The given data was invalid.')
            ->assertJsonValidationErrors([
                'event_type',
                'channel',
                'recipient',
            ]);
    }

    public function test_unknown_api_route_uses_the_shared_message(): void
    {
        $this->getJson('/api/unknown-route')
            ->assertNotFound()
            ->assertExactJson([
                'message' => 'The requested resource was not found.',
            ]);
    }

    public function test_unsupported_http_method_uses_the_shared_message(): void
    {
        $this->putJson('/api/notifications')
            ->assertMethodNotAllowed()
            ->assertExactJson([
                'message' => 'The HTTP method is not allowed for this endpoint.',
            ]);
    }

    /**
     * @return array{0: Project, 1: string}
     */
    private function createProjectAndApiKey(
        string $apiKeyStatus = 'active',
        string $projectStatus = 'active',
        mixed $expiresAt = null
    ): array {
        $project = Project::create([
            'name' => 'Test Project',
            'slug' => fake()->unique()->slug(),
            'status' => $projectStatus,
        ]);

        $plainTextKey = 'test-api-key-'.fake()->unique()->uuid();

        ApiKey::create([
            'project_id' => $project->id,
            'name' => 'Test API Key',
            'key_prefix' => 'test_',
            'key_hash' => hash('sha256', $plainTextKey),
            'status' => $apiKeyStatus,
            'expires_at' => $expiresAt,
        ]);

        return [$project, $plainTextKey];
    }

    /** @return array<string, mixed> */
    private function notificationPayload(): array
    {
        return [
            'event_type' => 'order.created',
            'channel' => 'email',
            'recipient' => 'customer@example.com',
            'data' => [
                'order_id' => 123,
            ],
        ];
    }
}
