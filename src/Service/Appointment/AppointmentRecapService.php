<?php


namespace App\Service\Appointment;

use App\Service\Chatbot\PdfGeneratorService;

readonly class AppointmentRecapService
{
    public function __construct(private PdfGeneratorService $pdfGenerator, private string $kernelTempDir)
    {
    }

    public function generate(array $data): array
    {
        // 1. Construire le HTML
        $html = <<<HTML
            <!DOCTYPE html>
            <html lang="fr">
            <head><meta charset="UTF-8"><title>Récapitulatif</title>
            <style>
              body { font-family:sans-serif; font-size:14px; margin:40px;}
              .title { text-align:center; font-size:20px; margin-bottom:30px; }
              table { width:100%; border-collapse:collapse; }
              th,td { padding:8px; border-bottom:1px solid #ddd; text-align:left; }
              th { background:#f4f4f4; width:30%; }
              .footer { text-align:center; font-size:12px; color:#888; margin-top:30px; }
            </style>
            </head>
            <body>
              <div class="title">Récapitulatif de votre rendez-vous</div>
              <table>
                <tr><th>Client</th><td>{$data['firstname']} {$data['lastname']}</td></tr>
                <tr><th>Véhicule</th><td>{$data['vehicle']}</td></tr>
                <tr><th>Garage</th><td>{$data['garage']}</td></tr>
                <tr><th>Créneau</th><td>{$data['slot']}</td></tr>
                <tr><th>Problème</th><td>{$data['problem']}</td></tr>
              </table>
              <div class="footer">Merci d'avoir choisi nos services.</div>
            </body>
            </html>
        HTML;

        $pdfBytes = $this->pdfGenerator->generatePdf($html);
        $pdfFile = $this->kernelTempDir . '/recap_' . uniqid('', true) . '.pdf';
        file_put_contents($pdfFile, $pdfBytes);

        $jsonFile = $this->kernelTempDir . '/recap_' . uniqid('', true) . '.json';
        file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $pdfUrl = '/download-pdf?path=' . urlencode($pdfFile);
        $jsonUrl = '/download-json?path=' . urlencode($jsonFile);

        return ['pdf_url' => $pdfUrl, 'json_url' => $jsonUrl];
    }
}
