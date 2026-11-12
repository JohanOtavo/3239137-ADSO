<?php

declare(strict_types=1);

namespace App\Domain\Equipment;

use App\Domain\Exception\EquipmentNotAvailable;
use App\Domain\Exception\ValidationFailed;
use App\Domain\Shared\ModelCode;
use App\Domain\Shared\Quantity;
use DateTimeImmutable;

/**
 * A catalogue model. Units are counted, not identified.
 *
 * The object is immutable: every operation returns a new piece of equipment.
 * That way no rule is ever half applied, and a test compares two states instead
 * of watching one mutate.
 */
final readonly class Equipment
{
    private function __construct(
        public string $id,
        public string $name,
        public string $brand,
        public ModelCode $modelCode,
        public string $categoryId,
        public int $totalUnits,
        public int $availableUnits,
        public ?string $photoKey,
        public ?DateTimeImmutable $deletedAt,
    ) {}

    /** On creation, available units start equal to the total (AC-03.1). */
    public static function register(
        string $id,
        string $name,
        string $brand,
        ModelCode $modelCode,
        string $categoryId,
        Quantity $totalUnits,
    ): self {
        return new self(
            $id,
            self::cleanName($name),
            self::cleanBrand($brand),
            $modelCode,
            $categoryId,
            $totalUnits->value,
            $totalUnits->value,
            null,
            null,
        );
    }

    /** Rebuilds stored equipment. Only persistence uses this. */
    public static function restore(
        string $id,
        string $name,
        string $brand,
        ModelCode $modelCode,
        string $categoryId,
        int $totalUnits,
        int $availableUnits,
        ?string $photoKey,
        ?DateTimeImmutable $deletedAt,
    ): self {
        return new self($id, $name, $brand, $modelCode, $categoryId, $totalUnits, $availableUnits, $photoKey, $deletedAt);
    }

    public function isActive(): bool
    {
        return $this->deletedAt === null;
    }

    /** Takes one unit out when renting. No units, no rental (AC-06.3). */
    public function withdraw(): self
    {
        if ($this->availableUnits < 1) {
            throw new EquipmentNotAvailable(sprintf('No quedan unidades disponibles de «%s».', $this->name));
        }

        return $this->withUnits($this->totalUnits, $this->availableUnits - 1);
    }

    /** Puts one unit back. Available never exceeds the total (AC-07.3). */
    public function restock(): self
    {
        if ($this->availableUnits >= $this->totalUnits) {
            throw new ValidationFailed('Las unidades disponibles no pueden superar el total.', [
                ['field' => 'available_units', 'issue' => 'superaría el total'],
            ]);
        }

        return $this->withUnits($this->totalUnits, $this->availableUnits + 1);
    }

    /**
     * Replaces the editable fields. Changing the total shifts the available
     * count by the same difference and never leaves it below zero (AC-03.3).
     *
     * The model code is not editable: it identifies the equipment.
     */
    public function replaceDetails(string $name, string $brand, string $categoryId, Quantity $totalUnits): self
    {
        $available = $this->availableUnits + ($totalUnits->value - $this->totalUnits);

        if ($available < 0) {
            throw new ValidationFailed('Bajar el total dejaría unidades disponibles en negativo: hay unidades alquiladas.', [
                ['field' => 'total_units', 'issue' => 'dejaría disponibles en negativo'],
            ]);
        }

        return new self(
            $this->id,
            self::cleanName($name),
            self::cleanBrand($brand),
            $this->modelCode,
            $categoryId,
            $totalUnits->value,
            $available,
            $this->photoKey,
            $this->deletedAt,
        );
    }

    /** Soft delete: leaves the catalogue, stays in past rentals (AC-03.5). */
    public function softDelete(DateTimeImmutable $now): self
    {
        return new self(
            $this->id,
            $this->name,
            $this->brand,
            $this->modelCode,
            $this->categoryId,
            $this->totalUnits,
            $this->availableUnits,
            $this->photoKey,
            $now,
        );
    }

    /** An opaque key, never a path and never an empty string (AC-03.4). */
    public function attachPhoto(string $photoKey): self
    {
        $key = trim($photoKey);

        if ($key === '') {
            throw new ValidationFailed('La clave de la foto no puede estar vacía.', [
                ['field' => 'photo_key', 'issue' => 'vacía'],
            ]);
        }

        return $this->withPhoto($key);
    }

    public function removePhoto(): self
    {
        return $this->withPhoto(null);
    }

    private function withUnits(int $total, int $available): self
    {
        return new self(
            $this->id,
            $this->name,
            $this->brand,
            $this->modelCode,
            $this->categoryId,
            $total,
            $available,
            $this->photoKey,
            $this->deletedAt,
        );
    }

    private function withPhoto(?string $photoKey): self
    {
        return new self(
            $this->id,
            $this->name,
            $this->brand,
            $this->modelCode,
            $this->categoryId,
            $this->totalUnits,
            $this->availableUnits,
            $photoKey,
            $this->deletedAt,
        );
    }

    private static function cleanName(string $raw): string
    {
        $name = trim($raw);

        if ($name === '' || mb_strlen($name) > 200) {
            throw new ValidationFailed('El nombre del equipo admite entre 1 y 200 caracteres.', [
                ['field' => 'name', 'issue' => 'entre 1 y 200 caracteres'],
            ]);
        }

        return $name;
    }

    private static function cleanBrand(string $raw): string
    {
        $brand = trim($raw);

        if ($brand === '' || mb_strlen($brand) > 120) {
            throw new ValidationFailed('La marca admite entre 1 y 120 caracteres.', [
                ['field' => 'brand', 'issue' => 'entre 1 y 120 caracteres'],
            ]);
        }

        return $brand;
    }
}
