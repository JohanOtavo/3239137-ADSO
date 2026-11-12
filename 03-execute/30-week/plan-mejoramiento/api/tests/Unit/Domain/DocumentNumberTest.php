<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Exception\ValidationFailed;
use App\Domain\Shared\DocumentNumber;
use PHPUnit\Framework\TestCase;

/** T-03 - A producer document number (AC-05.1). */
final class DocumentNumberTest extends TestCase
{
    public function test_trims_what_it_is_given(): void
    {
        $this->assertSame('1098765432', DocumentNumber::fromString('  1098765432  ')->value);
    }

    public function test_rejects_an_empty_document(): void
    {
        $this->expectException(ValidationFailed::class);

        DocumentNumber::fromString('   ');
    }

    public function test_rejects_a_document_longer_than_thirty_characters(): void
    {
        $this->expectException(ValidationFailed::class);

        DocumentNumber::fromString(str_repeat('9', 31));
    }
}
