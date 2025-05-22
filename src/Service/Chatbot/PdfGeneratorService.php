<?php
// src/Service/PdfGeneratorService.php
namespace App\Service\Chatbot;

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfGeneratorService
{
    public function generatePdf(string $html): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
