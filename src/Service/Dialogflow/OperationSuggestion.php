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

        $lines[0] = preg_replace('/^\xEF\xBB\xBF/', '', $lines[0]);

        $headers = str_getcsv($lines[0], ';'); // On force le séparateur
        array_shift($lines); // remove headers

        foreach ($lines as $line) {
            $data = str_getcsv($line, ';');

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
