<?php

declare(strict_types=1);

namespace Tests\Support\Fake;

use App\Application\Port\PhotoStorage;

final class InMemoryPhotos implements PhotoStorage
{
    /** @var array<string, string> */
    private array $files = [];

    private int $issued = 0;

    public function put(string $contents, string $mimeType): string
    {
        $this->issued++;
        $key = sprintf('photo-%d.%s', $this->issued, $mimeType === 'image/png' ? 'png' : 'jpg');
        $this->files[$key] = $contents;

        return $key;
    }

    public function read(string $photoKey): ?string
    {
        return $this->files[$photoKey] ?? null;
    }

    public function delete(string $photoKey): void
    {
        unset($this->files[$photoKey]);
    }

    public function storedCount(): int
    {
        return count($this->files);
    }
}
