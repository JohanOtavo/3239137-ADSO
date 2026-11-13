<?php

declare(strict_types=1);

namespace App\Application\UseCase\Rental;

use App\Application\Port\CategoryRepository;
use App\Application\Port\Clock;
use App\Application\Port\EquipmentRepository;
use App\Application\Port\IdGenerator;
use App\Application\Port\ProducerRepository;
use App\Application\Port\RentalRepository;
use App\Application\Port\TransactionManager;
use App\Domain\Exception\DuplicateEquipmentInRental;
use App\Domain\Exception\EquipmentNotAvailable;
use App\Domain\Exception\ResourceNotFound;
use App\Domain\Rental\ProducerBorrowingLimit;
use App\Domain\Rental\Rental;
use App\Domain\Shared\RentalPolicy;

/**
 * US-06 - Record a rental.
 *
 * Everything happens inside one transaction: taking the unit and creating the
 * line are the same operation, and if any step fails nothing is written
 * (AC-06.3).
 */
final readonly class RentEquipmentService
{
    public function __construct(
        private RentalRepository $rentals,
        private EquipmentRepository $equipment,
        private ProducerRepository $producers,
        private CategoryRepository $categories,
        private TransactionManager $transactions,
        private Clock $clock,
        private IdGenerator $ids,
        private RentalPolicy $policy,
        private ProducerBorrowingLimit $limit,
    ) {}

    /** @param list<string> $equipmentIds */
    public function handle(string $producerId, array $equipmentIds, string $actorUserId): Rental
    {
        // Repeated equipment is reported before touching inventory (AC-06.2).
        // Catching it inside the loop would work too, but only after the first
        // unit had already been taken and rolled back.
        if (count(array_unique($equipmentIds)) !== count($equipmentIds)) {
            throw new DuplicateEquipmentInRental('El mismo equipo no puede repetirse en un alquiler.', [
                ['field' => 'items', 'issue' => 'equipo repetido'],
            ]);
        }

        return $this->transactions->transactional(
            fn (): Rental => $this->record($producerId, $equipmentIds, $actorUserId),
        );
    }

    /** @param list<string> $equipmentIds */
    private function record(string $producerId, array $equipmentIds, string $actorUserId): Rental
    {
        if ($this->producers->find($producerId) === null) {
            throw new ResourceNotFound('Ese productor no existe.');
        }

        $now = $this->clock->now();

        $this->limit->ensureCanRent(
            $this->producers->countOpenEquipment($producerId),
            count($equipmentIds),
            $this->producers->hasOverdue($producerId, $now),
        );

        $rental = Rental::open($this->ids->next(), $producerId, $actorUserId, $now, $this->policy);

        foreach ($equipmentIds as $equipmentId) {
            $rental = $this->rentOne($rental, $equipmentId);
        }

        $rental->ensureReadyToRecord();
        $this->rentals->add($rental);

        return $rental;
    }

    private function rentOne(Rental $rental, string $equipmentId): Rental
    {
        $equipment = $this->equipment->findActive($equipmentId);

        if ($equipment === null) {
            throw new ResourceNotFound('Uno de los equipos no existe o está dado de baja.');
        }

        $category = $this->categories->find($equipment->categoryId);

        if ($category === null) {
            throw new ResourceNotFound('La categoría del equipo no existe.');
        }

        // The domain decides whether a unit is free; the database decides who
        // wins when two advisors ask for the last one at the same time. The
        // conditional write carries the count that was read (ADR-002).
        $withdrawn = $equipment->withdraw();

        if (! $this->equipment->applyWithdrawal($withdrawn, $equipment->availableUnits)) {
            throw new EquipmentNotAvailable(sprintf('No quedan unidades disponibles de «%s».', $equipment->name));
        }

        return $rental->addItem(
            $this->ids->next(),
            $equipment->id,
            $equipment->name,
            $equipment->modelCode,
            $category->name,
        );
    }
}
