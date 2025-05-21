<?php

namespace App\Service\Chatbot;

readonly class ConcessionLocatorService
{
    public function __construct(
        private string $csvPath
    ) {}

    public function findByCity(string $city): array
    {
        if (!file_exists($this->csvPath)) {
            return [];
        }

        $city = strtolower(trim($city));
        $matches = [];

        if (($handle = fopen($this->csvPath, 'r')) !== false) {
            $firstLine = true;

            while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                if ($firstLine) {
                    $firstLine = false;
                    continue;
                }

                $cityName = strtolower(trim($data[1] ?? ''));

                if ($city === $cityName) {
                    $matches[] = [
                        'name' => $data[0] ?? '',
                        'city' => $data[1] ?? '',
                        'address' => $data[2] ?? '',
                        'zipcode' => $data[3] ?? '',
                        'latitude' => $data[4] ?? '',
                        'longitude' => $data[5] ?? '',
                    ];
                }
            }

            fclose($handle);
        }

        return $matches;
    }

    public function findClosest(string $address, int $limit = 3): array
    {
        $encoded = urlencode($address);
        $url = "https://nominatim.openstreetmap.org/search?q={$encoded}&format=json&limit=1";

        $client = curl_init($url);
        curl_setopt($client, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($client, CURLOPT_USERAGENT, 'SymfonyBot/1.0');
        $response = curl_exec($client);
        curl_close($client);

        $results = json_decode($response, true);

        if (!$results || empty($results[0])) {
            return [];
        }

        $userLat = (float) $results[0]['lat'];
        $userLon = (float) $results[0]['lon'];

        $garages = [];
        if (($handle = fopen($this->csvPath, 'r')) !== false) {
            $first = true;
            while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                if ($first) {
                    $first = false;
                    continue;
                }

                $lat = (float) $data[4];
                $lon = (float) $data[5];

                $distance = $this->haversine($userLat, $userLon, $lat, $lon);
                $garages[] = [
                    'name' => $data[0],
                    'city' => $data[1],
                    'address' => $data[2],
                    'zipcode' => $data[3],
                    'latitude' => $lat,
                    'longitude' => $lon,
                    'distance_km' => $distance
                ];
            }
            fclose($handle);
        }

        usort($garages, fn($a, $b) => $a['distance_km'] <=> $b['distance_km']);

        return array_slice($garages, 0, $limit);
    }

    private function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

}
