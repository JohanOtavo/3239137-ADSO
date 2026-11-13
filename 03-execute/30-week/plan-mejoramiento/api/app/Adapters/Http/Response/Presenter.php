<?php

declare(strict_types=1);

namespace App\Adapters\Http\Response;

use App\Application\Port\CategoryRepository;
use App\Application\Shared\CategoryView;
use App\Application\Shared\MostRequestedRow;
use App\Application\Shared\OverdueRow;
use App\Application\Shared\PageResult;
use App\Domain\Equipment\Equipment;
use App\Domain\Producer\Producer;
use App\Domain\Rental\Rental;
use App\Domain\Rental\RentalItem;
use App\Domain\User\User;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Turns domain objects into the exact field names of the contract.
 *
 * It lives in the adapter because the field names are a transport decision: the
 * domain calls it modelCode and the wire calls it model_code, and neither has
 * to give way.
 *
 * The category names are read once and kept for the request. There are five of
 * them, so one query per request is cheaper than a join on every row and far
 * cheaper than a lookup per equipment.
 */
final class Presenter
{
    /** @var array<string, string>|null */
    private ?array $categoryNames = null;

    public function __construct(private readonly CategoryRepository $categories) {}

    /** @return array<string, mixed> */
    public function equipment(Equipment $equipment): array
    {
        return [
            'id' => $equipment->id,
            'name' => $equipment->name,
            'brand' => $equipment->brand,
            'model_code' => $equipment->modelCode->value,
            'category' => [
                'id' => $equipment->categoryId,
                'name' => $this->categoryName($equipment->categoryId),
            ],
            'total_units' => $equipment->totalUnits,
            'available_units' => $equipment->availableUnits,
            'photo_key' => $equipment->photoKey,
        ];
    }

    /** @return array<string, mixed> */
    public function producer(Producer $producer, ?int $openRentals = null): array
    {
        $body = [
            'id' => $producer->id,
            'full_name' => $producer->fullName,
            'document_number' => $producer->documentNumber->value,
        ];

        return $openRentals === null ? $body : $body + ['open_rentals' => $openRentals];
    }

    /** @return array<string, mixed> */
    public function rental(Rental $rental, DateTimeImmutable $now): array
    {
        return [
            'id' => $rental->id,
            'producer_id' => $rental->producerId,
            'rented_at' => self::instant($rental->rentedAt),
            'status' => $rental->statusAt($now)->value,
            'items' => array_map([self::class, 'rentalItem'], $rental->items),
        ];
    }

    /** @return array<string, mixed> */
    public static function rentalItem(RentalItem $item): array
    {
        return [
            'id' => $item->id,
            'equipment_id' => $item->equipmentId,
            'equipment_name' => $item->equipmentName,
            'model_code' => $item->modelCode->value,
            'category_name' => $item->categoryName,
            'due_at' => self::instant($item->dueAt),
            'returned_at' => self::instant($item->returnedAt),
        ];
    }

    /** @return array<string, mixed> */
    public static function user(User $user): array
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'role' => $user->role->value,
        ];
    }

    /** @return array<string, mixed> */
    public static function category(CategoryView $category): array
    {
        return ['id' => $category->id, 'name' => $category->name];
    }

    /** @return array<string, mixed> */
    public static function overdue(OverdueRow $row): array
    {
        return [
            'rental_id' => $row->rentalId,
            'item_id' => $row->itemId,
            'equipment_name' => $row->equipmentName,
            'producer_name' => $row->producerName,
            'due_at' => self::instant($row->dueAt),
            'days_overdue' => $row->daysLate,
        ];
    }

    /** @return array<string, mixed> */
    public static function mostRequested(MostRequestedRow $row): array
    {
        return [
            'equipment_id' => $row->equipmentId,
            'equipment_name' => $row->equipmentName,
            'category_name' => $row->categoryName,
            'times_rented' => $row->timesRented,
        ];
    }

    /**
     * One page envelope for every list, so a client writes the paging code once.
     *
     * @param  PageResult<object>  $result
     * @param  callable(object): array<string, mixed>  $each
     * @return array<string, mixed>
     */
    public static function page(PageResult $result, callable $each): array
    {
        return [
            'items' => array_map($each, $result->items),
            'page' => $result->page,
            'size' => $result->size,
            'total' => $result->total,
        ];
    }

    /** UTC with the Z suffix, as the contract writes every instant. */
    public static function instant(?DateTimeImmutable $moment): ?string
    {
        return $moment?->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }

    private function categoryName(string $categoryId): string
    {
        if ($this->categoryNames === null) {
            $this->categoryNames = [];

            foreach ($this->categories->all() as $category) {
                $this->categoryNames[$category->id] = $category->name;
            }
        }

        return $this->categoryNames[$categoryId] ?? '';
    }
}
