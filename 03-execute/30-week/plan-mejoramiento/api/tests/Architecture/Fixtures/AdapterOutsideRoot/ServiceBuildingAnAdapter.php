<?php

declare(strict_types=1);

namespace Tests\Architecture\Fixtures\AdapterOutsideRoot;

/**
 * A fixture, not production code: a use case that builds its own adapter
 * instead of being handed a port, which article III forbids.
 *
 * The method is never called. What matters is the shape of the line.
 */
final class ServiceBuildingAnAdapter
{
    public function handle(): void
    {
        $repository = new DatabaseEquipmentRepository('a-connection');

        unset($repository);
    }
}
