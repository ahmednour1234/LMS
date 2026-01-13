<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Teacher\StoreProgramRequest;
use App\Http\Requests\Teacher\UpdateProgramRequest;
use App\Http\Resources\Api\V1\Public\ProgramResource;
use App\Http\Services\ProgramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * @group Teacher Programs
 *
 * Teacher APIs to manage own programs only.
 */
class ProgramController extends ApiController
{
    public function __construct(
        protected ProgramService $programService
    ) {}

    /**
     * My Programs (Paginated)
     *
     * @queryParam q string Search in name(ar/en) or code. Example: English
     * @queryParam active boolean Filter by active (default 1). Example: 1
     * @queryParam parent_id int Filter by parent program (must be mine). Example: 5
     * @queryParam code string Filter by exact code. Example: PRG-001
     * @queryParam sort string Sort (newest, oldest, name). Example: newest
     * @queryParam per_page int Items per page. Example: 15
     */
    public function index(): JsonResponse
    {
        $teacherId = Auth::guard('teacher')->id();

        $filters = request()->only(['q', 'active', 'parent_id', 'code', 'sort']);
        $perPage = (int) request()->get('per_page', 15);

        $programs = $this->programService->getTeacherPrograms($teacherId, $filters, $perPage);

        return $this->successResponse([
            'programs' => ProgramResource::collection($programs),
            'meta' => [
                'current_page' => $programs->currentPage(),
                'last_page'    => $programs->lastPage(),
                'per_page'     => $programs->perPage(),
                'total'        => $programs->total(),
            ],
        ], 'Programs retrieved successfully.');
    }

    /**
     * Show Program (Owned by Teacher)
     */
    public function show(int $program): JsonResponse
    {
        $teacherId = Auth::guard('teacher')->id();

        $model = $this->programService->findTeacherProgramOrFail($teacherId, $program);

        return $this->successResponse(new ProgramResource($model), 'Program retrieved successfully.');
    }

    /**
     * Store Program (Owned by Teacher)
     */
    public function store(StoreProgramRequest $request): JsonResponse
    {
        $teacherId = Auth::guard('teacher')->id();

        // only allow safe fields (NEVER accept teacher_id from request)
        $data = $request->safe()->only([
            'code',
            'name',
            'description',
            'parent_id',
            'is_active',
        ]);

        $data['teacher_id'] = $teacherId;
        $data['is_active']  = $data['is_active'] ?? true;

        // image upload (optional)
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('programs', 'public');
        }

        $program = $this->programService->createForTeacher($teacherId, $data);

        return $this->successResponse(new ProgramResource($program), 'Program created successfully.', 201);
    }

    /**
     * Update Program (Owned by Teacher)
     */
    public function update(UpdateProgramRequest $request, int $program): JsonResponse
    {
        $teacherId = Auth::guard('teacher')->id();

        $model = $this->programService->findTeacherProgramOrFail($teacherId, $program);

        $data = $request->safe()->only([
            'code',
            'name',
            'description',
            'parent_id',
            'is_active',
            'remove_image',
        ]);

        // remove image
        if (!empty($data['remove_image'])) {
            $this->deletePublicFileIfExists($model->image);
            $data['image'] = null;
        }

        // replace image
        if ($request->hasFile('image')) {
            $this->deletePublicFileIfExists($model->image);
            $data['image'] = $request->file('image')->store('programs', 'public');
        }

        unset($data['remove_image']);

        $updated = $this->programService->updateForTeacher($teacherId, $model->id, $data);

        return $this->successResponse(new ProgramResource($updated), 'Program updated successfully.');
    }

    /**
     * Toggle Active (Owned by Teacher)
     */
    public function toggleActive(int $program): JsonResponse
    {
        $teacherId = Auth::guard('teacher')->id();

        $model = $this->programService->findTeacherProgramOrFail($teacherId, $program);

        $updated = $this->programService->toggleActiveForTeacher($teacherId, $model->id);

        return $this->successResponse(new ProgramResource($updated), 'Program status updated successfully.');
    }

    private function deletePublicFileIfExists(?string $path): void
    {
        if (!$path) return;

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
