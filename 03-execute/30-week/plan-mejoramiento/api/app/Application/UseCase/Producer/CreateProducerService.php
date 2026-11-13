<?php

declare(strict_types=1);

namespace App\Application\UseCase\Producer;

use App\Application\Port\IdGenerator;
use App\Application\Port\ProducerRepository;
use App\Domain\Exception\DocumentTaken;
use App\Domain\Producer\Producer;
use App\Domain\Shared\DocumentNumber;

/** US-05 - Create a producer. The document number is unique (AC-05.1). */
final readonly class CreateProducerService
{
    public function __construct(
        private ProducerRepository $producers,
        private IdGenerator $ids,
    ) {}

    public function handle(string $fullName, string $documentNumber): Producer
    {
        $document = DocumentNumber::fromString($documentNumber);

        if ($this->producers->existsByDocument($document)) {
            throw new DocumentTaken(sprintf('Ya hay un productor con el documento %s.', $document->value));
        }

        $producer = Producer::register($this->ids->next(), $fullName, $document);
        $this->producers->add($producer);

        return $producer;
    }
}
