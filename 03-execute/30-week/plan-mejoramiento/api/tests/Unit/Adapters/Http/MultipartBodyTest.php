<?php

declare(strict_types=1);

namespace Tests\Unit\Adapters\Http;

use App\Adapters\Http\Request\MultipartBody;
use App\Domain\Exception\ValidationFailed;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * T-20 - The PUT upload, which PHP does not parse on its own.
 *
 * These tests exist because the failure they guard against is silent: without
 * the parser the request looks fine and the file is simply absent.
 */
final class MultipartBodyTest extends TestCase
{
    private const BOUNDARY = '----filmgear-test-boundary';

    public function test_it_reads_the_bytes_of_the_named_part(): void
    {
        // Arrange
        $png = "\x89PNG\r\n\x1a\n".str_repeat("\x00", 16);
        $request = $this->putRequest($this->multipart('file', 'camera.png', $png));

        // Act
        $contents = MultipartBody::fileContents($request, 'file');

        // Assert
        $this->assertSame($png, $contents);
    }

    /**
     * The trailing CRLF belongs to the protocol. Keeping it would corrupt every
     * uploaded file by two bytes, which some decoders tolerate and some do not.
     */
    public function test_it_does_not_keep_the_crlf_that_precedes_the_boundary(): void
    {
        $jpeg = "\xff\xd8\xff\xe0 body";
        $request = $this->putRequest($this->multipart('file', 'camera.jpg', $jpeg));

        $this->assertSame($jpeg, MultipartBody::fileContents($request, 'file'));
    }

    public function test_it_ignores_the_other_parts_of_the_form(): void
    {
        $body = $this->multipart('note', 'note.txt', 'not the photo')
            .$this->multipart('file', 'camera.png', 'the photo');

        $this->assertSame('the photo', MultipartBody::fileContents($this->putRequest($body), 'file'));
    }

    public function test_a_form_without_the_field_is_a_validation_error(): void
    {
        $request = $this->putRequest($this->multipart('note', 'note.txt', 'no photo here'));

        $this->expectException(ValidationFailed::class);

        MultipartBody::fileContents($request, 'file');
    }

    public function test_a_body_that_is_not_multipart_is_a_validation_error(): void
    {
        $request = Request::create('/api/v1/equipment/1/photo', 'PUT', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], '{"file":"nope"}');

        $this->expectException(ValidationFailed::class);

        MultipartBody::fileContents($request, 'file');
    }

    private function putRequest(string $body): Request
    {
        return Request::create('/api/v1/equipment/1/photo', 'PUT', [], [], [], [
            'CONTENT_TYPE' => 'multipart/form-data; boundary='.self::BOUNDARY,
        ], $body.'--'.self::BOUNDARY."--\r\n");
    }

    private function multipart(string $field, string $filename, string $contents): string
    {
        return '--'.self::BOUNDARY."\r\n"
            .sprintf("Content-Disposition: form-data; name=\"%s\"; filename=\"%s\"\r\n", $field, $filename)
            ."Content-Type: application/octet-stream\r\n"
            ."\r\n"
            .$contents."\r\n";
    }
}
