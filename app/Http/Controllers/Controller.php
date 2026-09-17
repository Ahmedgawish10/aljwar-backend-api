<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

abstract class Controller
{
    protected function paginationMeta(LengthAwarePaginator $paginator): array
    {
        $paginator->withQueryString();

        return [
            'count' => $paginator->total(),
            'next' => $paginator->nextPageUrl(),
            'previous' => $paginator->previousPageUrl(),
        ];
    }

    protected function acceptPatchFiles(Request $request): void
    {
        if ($request->files->count() > 0) {
            return;
        }

        $contentType = (string) $request->header('Content-Type');
        if (! str_contains($contentType, 'multipart/form-data')) {
            return;
        }

        if (! preg_match('/boundary=(.*)$/i', $contentType, $matches)) {
            return;
        }

        $boundary = '--'.trim($matches[1], '"');

        foreach (explode($boundary, $request->getContent()) as $part) {
            $headerEnd = strpos($part, "\r\n\r\n");
            if ($headerEnd === false) {
                continue;
            }

            $headers = substr($part, 0, $headerEnd);
            preg_match('/name="([^"]+)"/', $headers, $field);
            preg_match('/filename="([^"]+)"/', $headers, $file);

            if (empty($field[1]) || empty($file[1])) {
                continue;
            }

            $tmp = tempnam(sys_get_temp_dir(), 'upl');
            file_put_contents($tmp, rtrim(substr($part, $headerEnd + 4), "\r\n"));

            $request->files->set($field[1], new UploadedFile($tmp, $file[1], null, UPLOAD_ERR_OK, true));
        }
    }
}
