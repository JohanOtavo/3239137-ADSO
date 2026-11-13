<?php

declare(strict_types=1);

namespace App\Application\UseCase\Producer;

use App\Application\Port\ProducerRepository;
use App\Domain\Exception\ResourceNotFound;
use App\Domain\Producer\Producer;

/**
 * Producer detail with the count of equipment still out (api-contract 3.5).
 *
 * The count ships with the detail because the advisor needs it before starting
 * a rental: it is the number the two item rule is about to check.
 */
final readonly class GetProducerService
{
    public function __construct(private ProducerRepository $producers) {}

    /** @return array{producer: Producer, open_rentals: int} */
    public function handle(string $producerId): array
    {
        $producer = $this->producers->find($producerId);

        if ($producer === null) {
            throw new ResourceNotFound('Ese productor no existe.');
        }

        return [
            'producer' => $producer,
            'open_rentals' => $this->producers->countOpenEquipment($producerId),
        ];
    }
}
