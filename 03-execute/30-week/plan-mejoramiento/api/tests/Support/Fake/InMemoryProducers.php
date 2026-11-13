<?php

declare(strict_types=1);

namespace Tests\Support\Fake;

use App\Application\Port\ProducerRepository;
use App\Application\Shared\PageResult;
use App\Application\Shared\Pagination;
use App\Domain\Producer\Producer;
use App\Domain\Shared\DocumentNumber;
use DateTimeImmutable;

/**
 * The counts are set by the test rather than derived.
 *
 * Counting unreturned lines is the database's job; what these tests exercise is
 * the rule that reads the count, so the double states the number outright.
 */
final class InMemoryProducers implements ProducerRepository
{
    /** @var array<string, Producer> */
    private array $producers = [];

    private int $openEquipment = 0;

    private bool $overdue = false;

    public function add(Producer $producer): void
    {
        $this->producers[$producer->id] = $producer;
    }

    public function withOpenEquipment(int $openEquipment): void
    {
        $this->openEquipment = $openEquipment;
    }

    public function withOverdue(bool $overdue): void
    {
        $this->overdue = $overdue;
    }

    public function find(string $id): ?Producer
    {
        return $this->producers[$id] ?? null;
    }

    public function existsByDocument(DocumentNumber $documentNumber): bool
    {
        foreach ($this->producers as $producer) {
            if ($producer->documentNumber->value === $documentNumber->value) {
                return true;
            }
        }

        return false;
    }

    public function search(?string $query, Pagination $page): PageResult
    {
        $producers = array_values($this->producers);

        return new PageResult($producers, $page->page, $page->size, count($producers));
    }

    public function countOpenEquipment(string $producerId): int
    {
        return $this->openEquipment;
    }

    public function hasOverdue(string $producerId, DateTimeImmutable $now): bool
    {
        return $this->overdue;
    }
}
