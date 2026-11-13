<?php

declare(strict_types=1);

namespace App\Application\UseCase\Producer;

use App\Application\Port\ProducerRepository;
use App\Application\Shared\PageResult;
use App\Application\Shared\Pagination;
use App\Domain\Producer\Producer;

/** US-05 - List and search producers by name or document (AC-05.2). */
final readonly class SearchProducersService
{
    public function __construct(private ProducerRepository $producers) {}

    /** @return PageResult<Producer> */
    public function handle(?string $query, Pagination $page): PageResult
    {
        return $this->producers->search($query, $page);
    }
}
