<?php

declare(strict_types=1);

namespace App\Domain\Rental;

use App\Domain\Exception\ItemAlreadyReturned;
use App\Domain\Shared\ModelCode;
use DateTimeImmutable;

/**
 * One piece of equipment inside a rental, holding the catalogue values frozen
 * at the instant it went out.
 *
 * They do not follow the catalogue: renaming the equipment tomorrow leaves this
 * line untouched (article VIII, BR-4). That is what keeps a closed report
 * stable.
 */
final readonly class RentalItem
{
    private function __construct(
        public string $id,
        public string $equipmentId,
        public string $equipmentName,
        public ModelCode $modelCode,
        public string $categoryName,
        public DateTimeImmutable $dueAt,
        public ?DateTimeImmutable $returnedAt,
    ) {}

    public static function freeze(
        string $id,
        string $equipmentId,
        string $equipmentName,
        ModelCode $modelCode,
        string $categoryName,
        DateTimeImmutable $dueAt,
    ): self {
        return new self($id, $equipmentId, $equipmentName, $modelCode, $categoryName, $dueAt, null);
    }

    public static function restore(
        string $id,
        string $equipmentId,
        string $equipmentName,
        ModelCode $modelCode,
        string $categoryName,
        DateTimeImmutable $dueAt,
        ?DateTimeImmutable $returnedAt,
    ): self {
        return new self($id, $equipmentId, $equipmentName, $modelCode, $categoryName, $dueAt, $returnedAt);
    }

    public function isReturned(): bool
    {
        return $this->returnedAt !== null;
    }

    public function isOverdueAt(DateTimeImmutable $now): bool
    {
        return ! $this->isReturned() && $this->dueAt < $now;
    }

    /** Whole days late; zero when the line is not overdue (AC-09.1). */
    public function daysLateAt(DateTimeImmutable $now): int
    {
        if (! $this->isOverdueAt($now)) {
            return 0;
        }

        return (int) $this->dueAt->diff($now)->days;
    }

    /** Returning the same line twice does not give the unit back twice (AC-07.2). */
    public function markReturned(DateTimeImmutable $now): self
    {
        if ($this->isReturned()) {
            throw new ItemAlreadyReturned('Esta línea del alquiler ya estaba devuelta.');
        }

        return new self(
            $this->id,
            $this->equipmentId,
            $this->equipmentName,
            $this->modelCode,
            $this->categoryName,
            $this->dueAt,
            $now,
        );
    }
}
