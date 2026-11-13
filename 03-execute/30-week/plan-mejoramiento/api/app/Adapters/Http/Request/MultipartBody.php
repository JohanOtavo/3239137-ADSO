<?php

declare(strict_types=1);

namespace App\Adapters\Http\Request;

use App\Domain\Exception\ValidationFailed;
use Illuminate\Http\Request;

/**
 * Reads the uploaded file out of a PUT request.
 *
 * PHP only populates $_FILES for POST, so on a PUT the framework finds no file
 * however well formed the request is. The contract puts the photo on
 * PUT /equipment/{id}/photo, so the raw body is parsed here instead of bending
 * the contract into a POST.
 *
 * Only the bytes are taken. The declared filename and content type are
 * ignored on purpose: the use case decides the type by inspecting the content,
 * and a client that renames a script to .jpg must not get a say in that.
 */
final class MultipartBody
{
    public static function fileContents(Request $request, string $field): string
    {
        $uploaded = $request->file($field);

        if ($uploaded !== null && ! is_array($uploaded)) {
            $contents = $uploaded->get();

            return is_string($contents) ? $contents : '';
        }

        return self::parse((string) $request->getContent(), self::boundary($request), $field);
    }

    private static function boundary(Request $request): string
    {
        $contentType = (string) $request->header('Content-Type', '');

        if (preg_match('/boundary="?([^";]+)"?/i', $contentType, $found) !== 1) {
            throw new ValidationFailed('La foto debe enviarse como multipart/form-data.', [
                ['field' => 'file', 'issue' => 'falta el límite del formulario'],
            ]);
        }

        return $found[1];
    }

    private static function parse(string $body, string $boundary, string $field): string
    {
        foreach (explode('--'.$boundary, $body) as $part) {
            $split = strpos($part, "\r\n\r\n");

            if ($split === false) {
                continue;
            }

            $headers = substr($part, 0, $split);

            if (preg_match('/name="'.preg_quote($field, '/').'"/i', $headers) !== 1) {
                continue;
            }

            // The part ends with the CRLF that precedes the next boundary line,
            // and that CRLF belongs to the protocol, not to the file.
            return rtrim(substr($part, $split + 4), "\r\n");
        }

        throw new ValidationFailed('Falta el archivo de la foto.', [
            ['field' => $field, 'issue' => 'no viene en el formulario'],
        ]);
    }
}
