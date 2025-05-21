<?php

namespace App\Service\Dialogflow;

class ConcessionLocator
{
    private array $concessions = [];

    public function __construct(string $csvPath)
    {
        if (!file_exists($csvPath)) {
            throw new \RuntimeException("Fichier introuvable : $csvPath");
        }


        $lines = file($csvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', array_shift($lines));
        $headers = str_getcsv($firstLine, ';');

        foreach ($lines as $line) {
            $data = str_getcsv($line, ';');

            if (count($data) !== count($headers)) {
                continue;
            }

            $this->concessions[] = array_combine($headers, $data);
            file_put_contents('/tmp/debug_concessions.csv', json_encode($this->concessions, JSON_PRETTY_PRINT));

        }
    }

    public function findNearest(string $codePostal): ?array
    {
        foreach ($this->concessions as $concession) {
            if (trim($concession['zipcode']) === trim($codePostal)) {
                return $concession;
            }
        }

        $target = $this->getGeoCoordinatesFromPostalCode($codePostal);

        if (!$target) {
            return null;
        }

        $closest = null;
        $minDistance = INF;

        foreach ($this->concessions as $concession) {
            $lat = (float) $concession['latitude'];
            $lon = (float) $concession['longitude'];

            $distance = $this->haversine($target['lat'], $target['lon'], $lat, $lon);

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $closest = $concession;
            }
        }

        return $closest;
    }

    private function getGeoCoordinatesFromPostalCode(string $postalCode): ?array
    {
        if ($postalCode === '69003' || str_starts_with($postalCode, '69')) {
            return ['lat' => 45.75, 'lon' => 4.85];
        }

        return null;
    }

    private function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2 +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
