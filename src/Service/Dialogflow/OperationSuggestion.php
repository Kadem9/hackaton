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

        // Supprimer BOM si présent
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

        file_put_contents('/tmp/op_csv_count.log', "Chargées : " . count($this->operations) . " opérations\n");
    }


    public function suggest(string $description): ?array
    {
        file_put_contents('/tmp/op_match.log', "→ Texte reçu : $description\n", FILE_APPEND);

        $description = strtolower($description);
        $bestMatch = null;
        $highestScore = 0;

        foreach ($this->operations as $operation) {
            $name = strtolower($operation['operation_name'] ?? '');

            // Log brut
            file_put_contents('/tmp/op_match.log', ">> RAW: " . json_encode($operation) . "\n", FILE_APPEND);
            file_put_contents('/tmp/op_match.log', "Comparé à : $name\n", FILE_APPEND);

            if (!$name) {
                continue;
            }

            similar_text($description, $name, $percent);

            file_put_contents('/tmp/op_match.log', "$description vs $name = $percent\n", FILE_APPEND);

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
