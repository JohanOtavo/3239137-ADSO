<?php

declare(strict_types=1);

namespace App\Adapters\Http\Controller;

use App\Adapters\Http\Request\MultipartBody;
use App\Adapters\Http\Response\Presenter;
use App\Application\UseCase\Equipment\GetEquipmentPhotoService;
use App\Application\UseCase\Equipment\RemoveEquipmentPhotoService;
use App\Application\UseCase\Equipment\SetEquipmentPhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** US-03 - The photo of a piece of equipment (AC-03.6, AC-03.7). */
final readonly class EquipmentPhotoController
{
    public function __construct(
        private SetEquipmentPhotoService $set,
        private GetEquipmentPhotoService $get,
        private RemoveEquipmentPhotoService $remove,
        private Presenter $presenter,
    ) {}

    public function update(Request $request, string $id): JsonResponse
    {
        $equipment = $this->set->handle($id, MultipartBody::fileContents($request, 'file'));

        return new JsonResponse($this->presenter->equipment($equipment));
    }

    /**
     * The binary is served by this route and never as a public path, so the
     * token still decides who may see it.
     */
    public function show(string $id): Response
    {
        $contents = $this->get->handle($id);

        return new Response($contents, 200, [
            'Content-Type' => self::typeOf($contents),
            'Content-Length' => (string) strlen($contents),
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->remove->handle($id);

        return new JsonResponse(null, 204);
    }

    /** Read from the stored bytes, the same way the upload was validated. */
    private static function typeOf(string $contents): string
    {
        return str_starts_with($contents, "\x89PNG") ? 'image/png' : 'image/jpeg';
    }
}
