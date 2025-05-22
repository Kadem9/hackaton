<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FileDownloadController
{
    #[Route('/download-pdf', name: 'download_pdf')]
    public function downloadPdf(Request $request): Response
    {
        $path = $request->query->get('path');

        if (!$path || !file_exists($path)) {
            return new Response('Fichier PDF introuvable.', 404);
        }

        return new BinaryFileResponse($path);
    }

    #[Route('/download-json', name: 'download_json')]
    public function downloadJson(Request $request): Response
    {
        $path = $request->query->get('path');

        if (!$path || !file_exists($path)) {
            return new Response('Fichier JSON introuvable.', 404);
        }

        return new BinaryFileResponse($path);
    }
}
