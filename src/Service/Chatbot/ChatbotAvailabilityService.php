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

                foreach ($entry['availability'] as $date => $daySlots) {
                    if (!is_array($daySlots)) {
                        continue;
                    }

                    $dtDay = \DateTime::createFromFormat('Y-m-d', $date);
                    $formatter = new \IntlDateFormatter(
                        'fr_FR',
                        \IntlDateFormatter::FULL,
                        \IntlDateFormatter::NONE,
                        null,
                        null,
                        'EEEE d MMMM'
                    );
                    $dayFormatted = ucfirst($formatter->format($dtDay));

                    foreach (['morning', 'afternoon'] as $period) {
                        if (empty($daySlots[$period])) {
                            continue;
                        }
                        foreach ($daySlots[$period] as $time) {
                            $formatted[] = "{$dayFormatted} à {$time}";
                        }
                    }
                }

                return $formatted;
            }
        }

        return [];
    }

    public function getAvailableSlots(string $name, string $city): array
    {
        $json = json_decode(file_get_contents($this->availabilityPath), true);
        foreach ($json as $entry) {
            if (
                strtoupper($entry['dealership_name']) === strtoupper($name)
                && strtoupper($entry['city']) === strtoupper($city)
            ) {
                $slotsByDay = [];
                foreach ($entry['availability'] as $date => $daySlots) {
                    if (!is_array($daySlots)) {
                        continue;
                    }
                    $dtDay = \DateTime::createFromFormat('Y-m-d', $date);
                    $formatter = new \IntlDateFormatter(
                        'fr_FR',
                        \IntlDateFormatter::FULL,
                        \IntlDateFormatter::NONE,
                        null,
                        null,
                        'EEEE d MMMM'
                    );
                    $dayLabel = ucfirst($formatter->format($dtDay));
                    $times = [];
                    foreach (['morning', 'afternoon'] as $period) {
                        if (!empty($daySlots[$period]) && is_array($daySlots[$period])) {
                            foreach ($daySlots[$period] as $time) {
                                $times[] = $time;
                            }
                        }
                    }
                    if ($times) {
                        $slotsByDay[] = [
                            'label' => $dayLabel,
                            'times' => $times,
                        ];
                    }
                }
                return $slotsByDay;
            }
        }
        return [];
    }
}
