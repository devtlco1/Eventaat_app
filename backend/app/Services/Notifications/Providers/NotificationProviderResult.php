<?php

namespace App\Services\Notifications\Providers;

final readonly class NotificationProviderResult
{
    public function __construct(
        public bool $success,
        public ?string $provider_message_id = null,
        public ?string $failure_reason = null,
    ) {
    }

    public static function success(?string $providerMessageId = null): self
    {
        return new self(true, $providerMessageId, null);
    }

    public static function failure(string $reason, ?string $providerMessageId = null): self
    {
        return new self(false, $providerMessageId, $reason);
    }

    /**
     * @return array{success: bool, provider_message_id: ?string, failure_reason: ?string}
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'provider_message_id' => $this->provider_message_id,
            'failure_reason' => $this->failure_reason,
        ];
    }
}

