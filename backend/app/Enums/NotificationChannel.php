<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case Email = 'email';
    case Webhook = 'webhook';

    public function label(): string
    {
        return match ($this) {
            self::Email => 'Email',
            self::Webhook => 'Webhook',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $channel) => [
                'value' => $channel->value,
                'label' => $channel->label(),
            ],
            self::cases(),
        );
    }

    public static function labelFor(string $value): string
    {
        return self::tryFrom($value)?->label() ?? $value;
    }
}
