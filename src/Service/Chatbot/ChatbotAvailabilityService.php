<?php

namespace App\Service\Chatbot;

readonly class ChatbotAvailabilityService
{
    public function __construct(private string $availabilityPath) {}

    public function getAvailableDates(string $name, string $city): array
    {
        $json = json_decode(file_get_contents($this->availabilityPath), true);

        foreach ($json as $entry) {
            if (
                strtoupper($entry['dealership_name']) === strtoupper($name)
                && strtoupper($entry['city']) === strtoupper($city)
            ) {
                $formatted = [];

                foreach ($entry['availability'] as $date => $slots) {
                    if ($slots === 'closed' || (int)$slots === 0) continue;

                    $dt = \DateTime::createFromFormat('Y-m-d', $date);
                    $formatter = new \IntlDateFormatter('fr_FR', \IntlDateFormatter::FULL, \IntlDateFormatter::NONE, null, null, 'EEEE d MMMM');

                    $dayFormatted = ucfirst($formatter->format($dt));
                    $formatted[] = "{$dayFormatted} — {$slots} créneau" . ($slots > 1 ? 'x' : '');
                }

                return $formatted;
            }
        }
        return [];
    }
}
