<?php

declare(strict_types=1);

namespace App\Domain\Rental;

use App\Domain\Exception\DuplicateEquipmentInRental;
use App\Domain\Exception\ResourceNotFound;
use App\Domain\Exception\ValidationFailed;
use App\Domain\Shared\ModelCode;
use App\Domain\Shared\RentalPolicy;
use App\Domain\Shared\RentalStatus;
use DateTimeImmutable;

/**
 * A rental is a settled fact: who asked, who recorded it and when.
 *
 * It is never edited and never deleted; the only thing that changes is the
 * returned_at of one line (AC-06.7, BR-5).
 */
final readonly class Rental
{
    /** @param list<RentalItem> $items */
    private function __construct(
        public string $id,
        public string $producerId,
        public string $rentedByUserId,
        public DateTimeImmutable $rentedAt,
        public array $items,
        private RentalPolicy $policy,
    ) {}

    public static function open(
        string $id,
        string $producerId,
        string $rentedByUserId,
        DateTimeImmutable $rentedAt,
        RentalPolicy $policy,
    ): self {
        return new self($id, $producerId, $rentedByUserId, $rentedAt, [], $policy);
    }

    /** @param list<RentalItem> $items */
    public static function restore(
        string $id,
        string $producerId,
        string $rentedByUserId,
        DateTimeImmutable $rentedAt,
        array $items,
        RentalPolicy $policy,
    ): self {
        return new self($id, $producerId, $rentedByUserId, $rentedAt, $items, $policy);
    }

    /** Freezes the catalogue values and dues at rented_at plus four days (AC-06.6). */
    public function addItem(
        string $itemId,
        string $equipmentId,
        string $equipmentName,
        ModelCode $modelCode,
        string $categoryName,
    ): self {
        if (count($this->items) >= $this->policy->maxItemsPerRental) {
            throw new ValidationFailed(
                sprintf('Un alquiler admite como máximo %d equipos.', $this->policy->maxItemsPerRental),
                [['field' => 'items', 'issue' => 'demasiadas líneas']],
            );
        }

        foreach ($this->items as $item) {
            if ($item->equipmentId === $equipmentId) {
                throw new DuplicateEquipmentInRental('El mismo equipo no puede repetirse en un alquiler.', [
                    ['field' => 'items', 'issue' => 'equipo repetido'],
                ]);
            }
        }

        $dueAt = $this->rentedAt->modify(sprintf('+%d days', $this->policy->rentalDays));
        $frozen = RentalItem::freeze($itemId, $equipmentId, $equipmentName, $modelCode, $categoryName, $dueAt);

        return new self(
            $this->id,
            $this->producerId,
            $this->rentedByUserId,
            $this->rentedAt,
            array_merge($this->items, [$frozen]),
            $this->policy,
        );
    }

    /** A rental carries 1 or 2 lines (AC-06.1). */
    public function ensureReadyToRecord(): void
    {
        if ($this->items === []) {
            throw new ValidationFailed('Un alquiler necesita al menos un equipo.', [
                ['field' => 'items', 'issue' => 'lista vacía'],
            ]);
        }
    }

    /** One line comes back at a time (AC-07.1). */
    public function returnItem(string $itemId, DateTimeImmutable $now): self
    {
        $found = false;
        $items = [];

        foreach ($this->items as $item) {
            if ($item->id === $itemId) {
                $items[] = $item->markReturned($now);
                $found = true;

                continue;
            }

            $items[] = $item;
        }

        if (! $found) {
            throw new ResourceNotFound('Esa línea no pertenece a este alquiler.');
        }

        return new self(
            $this->id,
            $this->producerId,
            $this->rentedByUserId,
            $this->rentedAt,
            $items,
            $this->policy,
        );
    }

    public function item(string $itemId): RentalItem
    {
        foreach ($this->items as $item) {
            if ($item->id === $itemId) {
                return $item;
            }
        }

        throw new ResourceNotFound('Esa línea no pertenece a este alquiler.');
    }

    /**
     * Closed when nothing is still out, overdue when something passed its date,
     * open otherwise (article VII).
     */
    public function statusAt(DateTimeImmutable $now): RentalStatus
    {
        $pending = $this->pendingItems();

        if ($pending === []) {
            return RentalStatus::Closed;
        }

        foreach ($pending as $item) {
            if ($item->isOverdueAt($now)) {
                return RentalStatus::Overdue;
            }
        }

        return RentalStatus::Open;
    }

    public function openItemsCount(): int
    {
        return count($this->pendingItems());
    }

    /** @return list<RentalItem> */
    private function pendingItems(): array
    {
        $pending = [];

        foreach ($this->items as $item) {
            if (! $item->isReturned()) {
                $pending[] = $item;
            }
        }

        return $pending;
    }
}
