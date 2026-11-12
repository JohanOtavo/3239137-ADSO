<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Exception\ProducerHasOverdue;
use App\Domain\Exception\ProducerLimitExceeded;
use App\Domain\Rental\ProducerBorrowingLimit;
use App\Domain\Shared\RentalPolicy;
use PHPUnit\Framework\TestCase;

/** T-05 - At most two pieces of equipment out, and nothing while overdue. */
final class ProducerBorrowingLimitTest extends TestCase
{
    public function test_renting_up_to_the_limit_is_allowed(): void
    {
        $this->limit()->ensureCanRent(openEquipment: 0, requestedEquipment: 2, hasOverdue: false);

        $this->expectNotToPerformAssertions();
    }

    public function test_one_more_than_the_limit_is_rejected(): void
    {
        $this->expectException(ProducerLimitExceeded::class);

        $this->limit()->ensureCanRent(openEquipment: 1, requestedEquipment: 2, hasOverdue: false);
    }

    public function test_a_producer_at_the_limit_cannot_take_one_more(): void
    {
        $this->expectException(ProducerLimitExceeded::class);

        $this->limit()->ensureCanRent(openEquipment: 2, requestedEquipment: 1, hasOverdue: false);
    }

    public function test_overdue_blocks_even_a_single_item(): void
    {
        $this->expectException(ProducerHasOverdue::class);

        $this->limit()->ensureCanRent(openEquipment: 0, requestedEquipment: 1, hasOverdue: true);
    }

    /** Overdue is reported before the count, so the producer knows what to return. */
    public function test_overdue_is_reported_before_the_count(): void
    {
        $this->expectException(ProducerHasOverdue::class);

        $this->limit()->ensureCanRent(openEquipment: 5, requestedEquipment: 5, hasOverdue: true);
    }

    public function test_the_message_carries_the_current_count(): void
    {
        try {
            $this->limit()->ensureCanRent(openEquipment: 2, requestedEquipment: 1, hasOverdue: false);
            $this->fail('Expected ProducerLimitExceeded.');
        } catch (ProducerLimitExceeded $failure) {
            $this->assertStringContainsString('2 equipo(s)', $failure->getMessage());
        }
    }

    private function limit(): ProducerBorrowingLimit
    {
        return new ProducerBorrowingLimit(new RentalPolicy());
    }
}
