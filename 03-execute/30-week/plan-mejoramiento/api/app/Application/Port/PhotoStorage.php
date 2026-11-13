<?php

declare(strict_types=1);

namespace App\Application\Port;

/**
 * Stores the binary outside the database and returns an opaque key (D-08).
 *
 * Opaque matters: the key is not a path a client could request, so changing
 * the storage layout never breaks a stored row.
 */
interface PhotoStorage
{
    public function put(string $contents, string $mimeType): string;

    public function read(string $photoKey): ?string;

    public function delete(string $photoKey): void;
}
