<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\UseCase\Equipment\CreateEquipmentService;
use App\Application\UseCase\Equipment\SetEquipmentPhotoService;
use App\Domain\Exception\ResourceNotFound;
use App\Domain\Exception\ValidationFailed;
use PHPUnit\Framework\TestCase;
use Tests\Support\Fake\InMemoryCategories;
use Tests\Support\Fake\InMemoryEquipment;
use Tests\Support\Fake\InMemoryPhotos;
use Tests\Support\Fake\SequentialIds;

/**
 * T-15 and T-27 - The photo, by size and by content (AC-03.4).
 *
 * The three promises of the task: a PNG inside the limit is stored, one over
 * it is refused with 422, and equipment with no photo keeps a null key and
 * never an empty string.
 *
 * The fourth assertion is the one the task does not name and the contract
 * does: the type is decided by the bytes. A file called photo.png that holds
 * a script is a script.
 */
final class SetEquipmentPhotoServiceTest extends TestCase
{
    private const CATEGORY_ID = '11111111-1111-4111-8111-111111111111';

    private const MAX_BYTES = 2097152;

    private InMemoryEquipment $equipment;

    private InMemoryPhotos $photos;

    private SetEquipmentPhotoService $service;

    private string $equipmentId;

    protected function setUp(): void
    {
        $this->equipment = new InMemoryEquipment();
        $this->photos = new InMemoryPhotos();

        $categories = new InMemoryCategories();
        $categories->seed(self::CATEGORY_ID, 'Cámaras');

        $created = (new CreateEquipmentService($this->equipment, $categories, new SequentialIds()))
            ->handle('Sony FX3', 'Sony', 'FX3-BODY', self::CATEGORY_ID, 2);

        $this->equipmentId = $created->id;

        $this->service = new SetEquipmentPhotoService(
            $this->equipment,
            $this->photos,
            self::MAX_BYTES,
            ['image/jpeg', 'image/png'],
        );
    }

    public function test_a_png_inside_the_limit_is_stored_and_the_key_is_opaque(): void
    {
        $updated = $this->service->handle($this->equipmentId, self::png());

        $this->assertNotNull($updated->photoKey);
        $this->assertSame(1, $this->photos->storedCount());
        // The key is not a path a client could ask for (D-08).
        $this->assertStringNotContainsString('/', $updated->photoKey);
    }

    public function test_a_photo_over_two_megabytes_is_refused(): void
    {
        // The size is checked before the content is sniffed, so the refusal
        // does not depend on reading three megabytes of anything.
        $tooBig = self::png().str_repeat('0', self::MAX_BYTES + 1);

        $this->expectException(ValidationFailed::class);

        $this->service->handle($this->equipmentId, $tooBig);
    }

    public function test_an_empty_upload_is_refused_rather_than_stored_as_nothing(): void
    {
        $this->expectException(ValidationFailed::class);

        $this->service->handle($this->equipmentId, '');
    }

    public function test_the_type_is_read_from_the_content_and_not_from_the_name(): void
    {
        // What a client called photo.png but is in fact a shell script.
        $notAnImage = "#!/bin/sh\necho hello\n";

        $this->expectException(ValidationFailed::class);

        $this->service->handle($this->equipmentId, $notAnImage);
    }

    public function test_equipment_with_no_photo_keeps_a_null_key(): void
    {
        $equipment = $this->equipment->findActive($this->equipmentId);

        $this->assertNotNull($equipment);
        // Null and not an empty string: the contract says the field is absent
        // or null, and an empty string is a third state nobody asked for.
        $this->assertNull($equipment->photoKey);
    }

    public function test_replacing_a_photo_drops_the_previous_binary(): void
    {
        $first = $this->service->handle($this->equipmentId, self::png());
        $second = $this->service->handle($this->equipmentId, self::png());

        $this->assertNotSame($first->photoKey, $second->photoKey);
        // The old file goes only after the new key is committed, so one photo
        // is left and never zero.
        $this->assertSame(1, $this->photos->storedCount());
        $this->assertNotNull($this->photos->read((string) $second->photoKey));
    }

    public function test_a_photo_for_equipment_that_does_not_exist_is_a_not_found(): void
    {
        $this->expectException(ResourceNotFound::class);

        $this->service->handle('11111111-2222-4333-8444-555555555555', self::png());
    }

    /** The smallest valid PNG: one transparent pixel, header and all. */
    private static function png(): string
    {
        return (string) base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true,
        );
    }
}
