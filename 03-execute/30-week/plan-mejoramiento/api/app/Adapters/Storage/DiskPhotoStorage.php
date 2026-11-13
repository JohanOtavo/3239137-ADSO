<?php

declare(strict_types=1);

namespace App\Adapters\Storage;

use App\Application\Port\IdGenerator;
use App\Application\Port\PhotoStorage;
use Illuminate\Contracts\Filesystem\Filesystem;

/**
 * Photos on local disk.
 *
 * The key it returns is opaque: it is a generated name, not a path a client
 * could ask for, so changing the storage layout never breaks a stored row
 * (D-08).
 */
final readonly class DiskPhotoStorage implements PhotoStorage
{
    public function __construct(
        private Filesystem $disk,
        private IdGenerator $ids,
    ) {}

    public function put(string $contents, string $mimeType): string
    {
        // The name comes from the id generator, never from the upload: a
        // client-supplied filename is how a path traversal gets in.
        $photoKey = $this->ids->next().$this->extensionFor($mimeType);
        $this->disk->put($photoKey, $contents);

        return $photoKey;
    }

    public function read(string $photoKey): ?string
    {
        if (! $this->disk->exists($photoKey)) {
            return null;
        }

        $contents = $this->disk->get($photoKey);

        return $contents === null ? null : (string) $contents;
    }

    public function delete(string $photoKey): void
    {
        if ($this->disk->exists($photoKey)) {
            $this->disk->delete($photoKey);
        }
    }

    private function extensionFor(string $mimeType): string
    {
        return $mimeType === 'image/png' ? '.png' : '.jpg';
    }
}
