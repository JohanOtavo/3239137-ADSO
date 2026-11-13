<?php

declare(strict_types=1);

namespace Tests\Unit\Adapters\Http;

use App\Adapters\Http\Request\Input;
use App\Domain\Exception\ValidationFailed;
use App\Domain\Shared\RentalStatus;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

/** T-20 - Reading the request, and refusing it in the shape of the contract. */
final class InputTest extends TestCase
{
    public function test_a_missing_required_field_names_itself_in_the_details(): void
    {
        // Arrange
        $request = Request::create('/api/v1/producers', 'POST', ['full_name' => 'Laura Ortiz']);

        // Act
        try {
            Input::requiredString($request, 'document_number');
            $this->fail('A missing field must be rejected.');
        } catch (ValidationFailed $failure) {
            // Assert
            $this->assertSame('VALIDATION_ERROR', $failure->code());
            $this->assertSame([['field' => 'document_number', 'issue' => 'falta o está vacío']], $failure->details());
        }
    }

    public function test_a_field_with_only_spaces_counts_as_missing(): void
    {
        $request = Request::create('/api/v1/producers', 'POST', ['full_name' => '   ']);

        $this->expectException(ValidationFailed::class);

        Input::requiredString($request, 'full_name');
    }

    /** The value is returned untrimmed: normalising it is the domain's rule, not the reader's. */
    public function test_it_returns_the_value_as_it_arrived(): void
    {
        $request = Request::create('/api/v1/users', 'POST', ['username' => 'Ana ']);

        $this->assertSame('Ana ', Input::requiredString($request, 'username'));
    }

    public function test_an_integer_arriving_as_a_string_is_accepted(): void
    {
        $request = Request::create('/api/v1/equipment', 'POST', ['total_units' => '4']);

        $this->assertSame(4, Input::requiredInt($request, 'total_units'));
    }

    public function test_a_non_numeric_integer_is_rejected(): void
    {
        $request = Request::create('/api/v1/equipment', 'POST', ['total_units' => 'four']);

        $this->expectException(ValidationFailed::class);

        Input::requiredInt($request, 'total_units');
    }

    public function test_an_empty_list_of_equipment_is_rejected(): void
    {
        $request = Request::create('/api/v1/rentals', 'POST', ['equipment_ids' => []]);

        $this->expectException(ValidationFailed::class);

        Input::requiredStringList($request, 'equipment_ids');
    }

    /** The index of the broken element is reported, so a client can point at it. */
    public function test_a_list_with_an_empty_element_names_the_position(): void
    {
        $request = Request::create('/api/v1/rentals', 'POST', [
            'equipment_ids' => ['equipment-1', ''],
        ]);

        try {
            Input::requiredStringList($request, 'equipment_ids');
            $this->fail('An empty element must be rejected.');
        } catch (ValidationFailed $failure) {
            $this->assertSame('equipment_ids[1]', $failure->details()[0]['field']);
        }
    }

    public function test_an_absent_query_parameter_reads_as_null(): void
    {
        $request = Request::create('/api/v1/equipment');

        $this->assertNull(Input::optionalString($request, 'q'));
        $this->assertNull(Input::optionalInt($request, 'page'));
        $this->assertNull(Input::optionalInstant($request, 'from'));
        $this->assertNull(Input::optionalStatus($request));
    }

    public function test_a_date_is_read_as_an_instant_in_utc(): void
    {
        $request = Request::create('/api/v1/rentals', 'GET', ['from' => '2026-11-12T09:30:00-05:00']);

        $from = Input::optionalInstant($request, 'from');

        $this->assertNotNull($from);
        $this->assertSame('2026-11-12T14:30:00Z', $from->format('Y-m-d\TH:i:s\Z'));
    }

    public function test_an_unreadable_date_is_a_validation_error(): void
    {
        $request = Request::create('/api/v1/rentals', 'GET', ['from' => 'last tuesday-ish']);

        $this->expectException(ValidationFailed::class);

        Input::optionalInstant($request, 'from');
    }

    public function test_a_known_status_is_read_as_the_enum(): void
    {
        $request = Request::create('/api/v1/rentals', 'GET', ['status' => 'overdue']);

        $this->assertSame(RentalStatus::Overdue, Input::optionalStatus($request));
    }

    public function test_an_unknown_status_is_rejected_rather_than_ignored(): void
    {
        $request = Request::create('/api/v1/rentals', 'GET', ['status' => 'late']);

        $this->expectException(ValidationFailed::class);

        Input::optionalStatus($request);
    }
}
