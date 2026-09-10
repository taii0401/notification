<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use App\Exceptions\ApiClientException;
use App\Jobs\SendNotificationJob;
use App\Services\Idempotency\RequestHashService;

use App\Models\IdempotencyKey;
use App\Models\NotificationMessage;
use App\Models\Project;

class CreateNotificationService
{
    public function __construct(private RequestHashService $requestHashService) 
    {   

    }

    public function execute(Project $project, array $data, ?string $idempotencyKey = null): array 
    {
        $template = null;
        if (!empty($data['template'])) {
            $template = $project
                ->notificationTemplates()
                ->where('code', $data['template'])
                ->where('channel', $data['channel'])
                ->where('status', 'active')
                ->first();

            if (!$template) {
                throw new NotFoundHttpException(
                    'Notification template not found.'
                );
            }
        }

        $requestHash = null;

        if ($idempotencyKey !== null && trim($idempotencyKey) === '') {
            throw new \InvalidArgumentException(
                'Idempotency key must not be empty.'
            );
        }

        //檢查是否已經在執行了
        if ($idempotencyKey !== null) {
            $requestHash = $this->requestHashService->make([
                'event_type' => $data['event_type'],
                'channel' => $data['channel'],
                'recipient' => $data['recipient'],
                'template' => $data['template'] ?? null,
                'data' => $data['data'] ?? null,
                'scheduled_at' => $data['scheduled_at'] ?? null,
            ]);

            $existing = $this->findExisting($project, $idempotencyKey);

            if ($existing !== null) {
                return $this->resolveExisting($existing, $requestHash);
            }
        }

        try {
            $result = DB::transaction(
                function () use ($project, $template, $data, $idempotencyKey, $requestHash) {
                    //建立 Notification
                    $notification = NotificationMessage::create([
                        'project_id' => $project->id,
                        'template_id' => $template?->id,
                        'event_type' => $data['event_type'],
                        'channel' => $data['channel'],
                        'recipient' => $data['recipient'],
                        'payload' => $data['data'] ?? null,
                        'metadata' => null,
                        'status' => 'pending',
                        'scheduled_at' => $data['scheduled_at'] ?? now(),
                    ]);

                    //決定 Provider
                    $provider = match ($notification->channel) {
                        'email' => 'smtp',
                        'webhook' => 'webhook',
                        default => throw new \RuntimeException(
                            'Unsupported notification channel.'
                        ),
                    };

                    //建立 Delivery
                    $delivery = $notification->deliveries()->create([
                        'provider' => $provider,
                        'status' => 'pending',
                        'attempt_count' => 0,
                    ]);


                    if ($idempotencyKey !== null) {
                        IdempotencyKey::create([
                            'project_id' => $project->id,
                            'idempotency_key' => $idempotencyKey,
                            'request_hash' => $requestHash,
                            'notification_id' => $notification->id,
                            'expires_at' => now()->addDay(),
                        ]);
                    }

                    return [
                        'notification' => $notification,
                        'delivery' => $delivery,
                        'replayed' => false,
                    ];
                }
            );

            if (!$result['replayed']) {
                SendNotificationJob::dispatch($result['notification']->id);

                $result['notification']->update([
                    'status' => 'queued',
                ]);

                $result['delivery']->update([
                    'status' => 'queued',
                ]);
            }

            return $result;
        } catch (QueryException $e) {
            if ($idempotencyKey === null || !$this->isUniqueConstraintViolation($e)) {
                throw $e;
            }

            $existing = $this->findExisting($project, $idempotencyKey);

            if (!$existing) {
                throw $e;
            }

            return $this->resolveExisting($existing, $requestHash);
        }
    }

    private function findExisting(Project $project, string $idempotencyKey): ?IdempotencyKey 
    {
        return IdempotencyKey::query()
            ->where('project_id', $project->id)
            ->where('idempotency_key', $idempotencyKey)
            ->with([
                'notification.deliveries',
            ])
            ->first();
    }

    private function resolveExisting(IdempotencyKey $existing, string $requestHash): array 
    {
        if ($existing->request_hash !== $requestHash) {
            throw new ConflictHttpException(
                'Idempotency key has already been used with a different request.'
            );
        }

        $notification = $existing->notification;

        return [
            'notification' => $notification,
            'delivery' => $notification
                ?->deliveries
                ->first(),
            'replayed' => true,
        ];
    }

    private function isUniqueConstraintViolation(QueryException $e): bool 
    {
        return in_array(
            $e->getCode(),
            [
                '23000',
                '23505',
            ],
            true
        );
    }
}
