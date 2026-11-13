<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Exception\ValidationFailed;
use App\Domain\Producer\Producer;
use App\Domain\Shared\DocumentNumber;
use PHPUnit\Framework\TestCase;

/** T-06 - The party that rents, with a trimmed name and a document number. */
final class ProducerTest extends TestCase
{
    public function test_it_trims_the_name(): void
    {
        $producer = Producer::register('producer-1', '  Laura Ortiz  ', $this->document());

        $this->assertSame('Laura Ortiz', $producer->fullName);
    }

    public function test_it_keeps_the_document_number(): void
    {
        $producer = Producer::register('producer-1', 'Laura Ortiz', $this->document());

        $this->assertSame('1098765432', $producer->documentNumber->value);
    }

    public function test_an_empty_name_is_rejected(): void
    {
        $this->expectException(ValidationFailed::class);

        Producer::register('producer-1', '   ', $this->document());
    }

    public function test_a_name_longer_than_two_hundred_characters_is_rejected(): void
    {
        $this->expectException(ValidationFailed::class);

        Producer::register('producer-1', str_repeat('a', 201), $this->document());
    }

    private function document(): DocumentNumber
    {
        return DocumentNumber::fromString('1098765432');
    }
}
