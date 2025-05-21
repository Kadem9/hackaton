<?php

namespace App\Service\Chatbot;

readonly class OperationService
{
    public function __construct(
        private string $csvPath
    ) {}

    public function getOperationsFromCsv(): array
    {
        if (!file_exists($this->csvPath)) {
            return [];
        }

        $operations = [];
        if (($handle = fopen($this->csvPath, 'r')) !== false) {
            $firstLine = true;

            while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                if ($firstLine) {
                    $firstLine = false;
                    continue;
                }

                $operationName = trim($data[0] ?? '');
                if ($operationName !== '') {
                    $operations[] = $operationName;
                }
            }

            fclose($handle);
        }

        return array_unique($operations);
    }
}
