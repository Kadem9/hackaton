<?php

namespace App\Service\Dialogflow;

class OperationSuggestion
{
    private array $operations = [];

    public function __construct(string $csvPath)
    {
        if (!file_exists($csvPath)) {
            throw new \RuntimeException("Fichier introuvable : $csvPath");
        }

        $lines = file($csvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $headers = str_getcsv(array_shift($lines));

        foreach ($lines as $line) {
            $data = str_getcsv($line);

            if (count($data) !== count($headers)) {
                continue;
            }

            $this->operations[] = array_combine($headers, $data);
        }
    }

    public function suggest(string $description): ?array
    {
        $description = strtolower($description);
        $bestMatch = null;
        $highestScore = 0;

        foreach ($this->operations as $operation) {
            $name = strtolower($operation['operation_name'] ?? '');
            if (!$name) {
                continue;
            }

            similar_text($description, $name, $percent);

            if ($percent > $highestScore) {
                $highestScore = $percent;
                $bestMatch = $operation;
            }
        }

        return $highestScore >= 60 ? $bestMatch : null;
    }


    public function getAll(): array
    {
        return $this->operations;
    }
}
