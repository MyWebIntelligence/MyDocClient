<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ExportService
{

    public function serveFile($path, $mimeType): BinaryFileResponse
    {
        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', $mimeType);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename($path));

        return $response;
    }

    /**
     * @param string $name
     * @return string
     *
     * Returns temp path
     */
    public function temp(string $name): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . $name;
    }

    /**
     * @param $pattern
     * @return string
     *
     * Return string with timestamp from sprintf pattern
     */
    public function timestamp($pattern): string
    {
        return sprintf($pattern, date('YmdHis'));
    }

}