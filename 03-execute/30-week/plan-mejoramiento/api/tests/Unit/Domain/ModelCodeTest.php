<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Exception\ValidationFailed;
use App\Domain\Shared\ModelCode;
use PHPUnit\Framework\TestCase;

/** T-03 - The code that identifies an equipment model (AC-03.2). */
final class ModelCodeTest extends TestCase
{
    public function test_accepts_a_code_of_four_to_twenty_characters(): void
    {
        $this->assertSame('FX3-BODY', ModelCode::fromString(' FX3-BODY ')->value);
        $this->assertSame('DJI-MINI4PRO', ModelCode::fromString('DJI-MINI4PRO')->value);
    }

    public function test_uppercases_what_it_is_given(): void
    {
        $this->assertSame('FX3-BODY', ModelCode::fromString('fx3-body')->value);
    }

    public function test_rejects_a_code_shorter_than_four_characters(): void
    {
        $this->expectException(ValidationFailed::class);

        ModelCode::fromString('FX3');
    }

    public function test_rejects_a_code_longer_than_twenty_characters(): void
    {
        $this->expectException(ValidationFailed::class);

        ModelCode::fromString(str_repeat('A', 21));
    }

    public function test_rejects_characters_outside_letters_digits_and_hyphens(): void
    {
        $this->expectException(ValidationFailed::class);

        ModelCode::fromString('FX3 BODY');
    }

    public function test_two_codes_with_the_same_value_are_equal(): void
    {
        $this->assertTrue(ModelCode::fromString('FX3-BODY')->equals(ModelCode::fromString('fx3-body')));
    }
}
