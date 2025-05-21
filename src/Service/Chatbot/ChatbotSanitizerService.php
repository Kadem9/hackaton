<?php

namespace App\Service\Chatbot;


namespace App\Service\Chatbot;

class ChatbotSanitizerService
{
    public function extractMileage(string $input): ?int
    {
        preg_match('/([\d\s.,]{3,})/', $input, $matches);

        if (!isset($matches[1])) {
            return null;
        }

        $clean = (int)preg_replace('/\D/', '', $matches[1]);

        return $clean > 0 ? $clean : null;
    }

    public function extractDate(string $input): ?\DateTime
    {
        if (preg_match('/\b(\d{2})[\/\-](\d{2})[\/\-](\d{4})\b/', $input, $matches)) {
            return \DateTime::createFromFormat('d/m/Y', "{$matches[1]}/{$matches[2]}/{$matches[3]}");
        }

        if (preg_match('/\b(20\d{2}|19\d{2})\b/', $input, $matches)) {
            return \DateTime::createFromFormat('Y', $matches[1]);
        }

        return null;
    }

    public function extractPhone(string $input): ?string
    {
        $phone = preg_replace('/\D/', '', $input);

        return strlen($phone) >= 10 ? $phone : null;
    }

    public function extractVin(string $input): ?string
    {
        if (preg_match('/[A-HJ-NPR-Z0-9]{8,20}/i', $input, $matches)) {
            return strtoupper($matches[0]);
        }

        return null;
    }
}
