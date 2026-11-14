<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Adapters\Persistence\Database\DatabaseEquipmentRepository;
use App\Adapters\Persistence\Database\Tables;
use App\Domain\Equipment\Equipment;
use Illuminate\Database\DatabaseManager;

/**
 * T-13 - The conditional write under a real race (ADR-002, NFR-2).
 *
 * The in-memory fake can stage a stale read, but it cannot prove that two
 * PostgreSQL sessions resolve the same race the same way. Here each repository
 * owns its own connection, so the WHERE available_units = ? is evaluated by
 * the engine twice over genuinely separate state, which is the situation the
 * ADR was written for: two advisors clicking at the same second.
 *
 * No version column exists, on purpose. The units being counted are the
 * version token, so a decrement that lands on a changed counter affects zero
 * rows and the caller learns it lost.
 */
final class ConcurrentWithdrawalTest extends IntegrationTestCase
{
    public function test_only_one_of_two_sessions_takes_the_last_unit(): void
    {
        $id = $this->equipmentWithOneUnit();

        $first = $this->repositoryOnItsOwnConnection('race-first');
        $second = $this->repositoryOnItsOwnConnection('race-second');

        // Both sessions read before either writes, which is the whole point:
        // each holds available_units = 1 and believes it may take it.
        $seenByFirst = $first->findActive($id);
        $seenBySecond = $second->findActive($id);

        self::assertInstanceOf(Equipment::class, $seenByFirst);
        self::assertInstanceOf(Equipment::class, $seenBySecond);
        self::assertSame(1, $seenByFirst->availableUnits);
        self::assertSame(1, $seenBySecond->availableUnits);

        $winner = $first->applyWithdrawal($seenByFirst->withdraw(), 1);
        $loser = $second->applyWithdrawal($seenBySecond->withdraw(), 1);

        self::assertTrue($winner, 'The first session must take the unit.');
        self::assertFalse($loser, 'The second session must be told it lost the race.');

        // The counter never went negative, so the CHECK was never the thing
        // that saved us: the conditional write did.
        self::assertSame(0, $this->availableUnitsOf($id));
    }

    public function test_the_loser_succeeds_once_it_reads_again(): void
    {
        $id = $this->equipmentWithTwoUnits();

        $first = $this->repositoryOnItsOwnConnection('retry-first');
        $second = $this->repositoryOnItsOwnConnection('retry-second');

        $stale = $second->findActive($id);
        self::assertInstanceOf(Equipment::class, $stale);

        $taken = $first->findActive($id);
        self::assertInstanceOf(Equipment::class, $taken);
        self::assertTrue($first->applyWithdrawal($taken->withdraw(), 2));

        // The stale write is refused, and the retry over a fresh read lands:
        // the race costs a reread and never a wrong number.
        self::assertFalse($second->applyWithdrawal($stale->withdraw(), 2));

        $fresh = $second->findActive($id);
        self::assertInstanceOf(Equipment::class, $fresh);
        self::assertTrue($second->applyWithdrawal($fresh->withdraw(), 1));

        self::assertSame(0, $this->availableUnitsOf($id));
    }

    public function test_two_sessions_cannot_restock_past_the_total(): void
    {
        $id = $this->equipmentWithOneUnit();

        $first = $this->repositoryOnItsOwnConnection('restock-first');
        $second = $this->repositoryOnItsOwnConnection('restock-second');

        $taken = $first->findActive($id);
        self::assertInstanceOf(Equipment::class, $taken);
        self::assertTrue($first->applyWithdrawal($taken->withdraw(), 1));

        // One unit went out, so exactly one restock may come back. The guard is
        // whereColumn available_units < total_units, and it holds across
        // connections (AC-07.3).
        self::assertTrue($first->applyRestock($id));
        self::assertFalse($second->applyRestock($id));

        self::assertSame(1, $this->availableUnitsOf($id));
    }

    /**
     * A repository over a connection of its own.
     *
     * Reusing the application connection for both sides would make the race a
     * fiction: one session cannot read its own uncommitted state as somebody
     * else's. connectUsing opens a second PDO handle against the same database.
     */
    private function repositoryOnItsOwnConnection(string $name): DatabaseEquipmentRepository
    {
        /** @var DatabaseManager $manager */
        $manager = $this->app->make('db');

        /** @var array<string, mixed> $settings */
        $settings = (array) config('database.connections.pgsql');

        return new DatabaseEquipmentRepository($manager->connectUsing($name, $settings, force: true));
    }

    private function equipmentWithOneUnit(): string
    {
        return $this->createEquipment(1);
    }

    private function equipmentWithTwoUnits(): string
    {
        return $this->createEquipment(2);
    }

    /** Created through the API so the row is the one the server would write. */
    private function createEquipment(int $totalUnits): string
    {
        $token = $this->signInAsAdmin();

        $response = $this->postJson('/api/v1/equipment', [
            'name' => 'Cámara réflex',
            'brand' => 'Marca uno',
            'model_code' => 'CAM-001',
            'category_id' => self::CAMERAS,
            'total_units' => $totalUnits,
        ], $this->bearer($token));

        $response->assertStatus(201);

        return (string) $response->json('id');
    }

    private function availableUnitsOf(string $equipmentId): int
    {
        $row = $this->app->make('db')->connection()
            ->table(Tables::EQUIPMENT)
            ->where('id', $equipmentId)
            ->first();

        self::assertNotNull($row);

        return (int) $row->available_units;
    }
}
