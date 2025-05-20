<?php

namespace App\Service\Dialogflow;

class DialogflowSessionStore
{
    private array $sessions = [];

    public function set(string $sessionId, string $key, mixed $value): void
    {
        $this->sessions[$sessionId][$key] = $value;
    }

    public function get(string $sessionId, string $key): mixed
    {
        return $this->sessions[$sessionId][$key] ?? null;
    }

    public function getAll(string $sessionId): array
    {
        return $this->sessions[$sessionId] ?? [];
    }
}
