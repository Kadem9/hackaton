<?php

namespace App\Service\Appointment;

use App\Service\Chatbot\PdfGeneratorService;

readonly class AppointmentRecapService
{
    public function __construct(
        private PdfGeneratorService $pdfGenerator,
        private string $kernelTempDir
    ) {}

    public function generate(array $data): array
    {
        $operations = $data['operations'] ?? [];
        $operationsFormatted = is_array($operations) && !empty($operations)
            ? '<ul>' . implode('', array_map(fn($op) => '<li>' . htmlspecialchars($op) . '</li>', $operations)) . '</ul>'
            : '<em>Aucune opération spécifiée</em>';

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>Récapitulatif de rendez-vous</title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 14px; margin: 40px; color: #333; }
                .title { text-align: center; font-size: 20px; margin-bottom: 30px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
                th { background-color: #f4f4f4; width: 30%; }
                ul { padding-left: 20px; margin: 0; }
                li { margin-bottom: 5px; }
                .footer { text-align: center; font-size: 12px; color: #888; margin-top: 30px; }
            </style>
        </head>
        <body>
            <div class="title">Récapitulatif de votre rendez-vous</div>
            <table>
                <tr><th>Client</th><td>{$this->escape($data['firstname'])} {$this->escape($data['lastname'])}</td></tr>
                <tr><th>Véhicule</th><td>{$this->escape($data['vehicle'])}</td></tr>
                <tr><th>Garage</th><td>{$this->escape($data['garage'])}</td></tr>
                <tr><th>Créneau</th><td>{$this->escape($data['slot'])}</td></tr>
                <tr><th>Problème</th><td>{$this->escape($data['problem'])}</td></tr>
                <tr><th>Opérations recommandées</th><td>{$operationsFormatted}</td></tr>
            </table>
            <div class="footer">Merci d'avoir choisi nos services.</div>
        </body>
        </html>
        HTML;

        // Generate files
        $pdfBytes = $this->pdfGenerator->generatePdf($html);
        $pdfFile  = $this->kernelTempDir . '/recap_' . uniqid('', true) . '.pdf';
        file_put_contents($pdfFile, $pdfBytes);

        $jsonFile = $this->kernelTempDir . '/recap_' . uniqid('', true) . '.json';
        file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return [
            'pdf_url' => '/download-pdf?path=' . urlencode($pdfFile),
            'json_url' => '/download-json?path=' . urlencode($jsonFile)
        ];
    }

    private function escape(string $input): string
    {
        return htmlspecialchars($input, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
