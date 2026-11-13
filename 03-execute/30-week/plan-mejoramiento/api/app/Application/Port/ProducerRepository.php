<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Application\Shared\PageResult;
use App\Application\Shared\Pagination;
use App\Domain\Producer\Producer;
use App\Domain\Shared\DocumentNumber;
use DateTimeImmutable;

interface ProducerRepository
{
    public function find(string $id): ?Producer;

    public function existsByDocument(DocumentNumber $documentNumber): bool;

    public function add(Producer $producer): void;

    /** @return PageResult<Producer> */
    public function search(?string $query, Pagination $page): PageResult;

    /** Equipment still out, across every rental of this producer (AC-06.4). */
    public function countOpenEquipment(string $producerId): int;

    /** Whether any line is still out and already past its due date (AC-06.5). */
    public function hasOverdue(string $producerId, DateTimeImmutable $now): bool;
}
