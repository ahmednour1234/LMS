<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Domain\Training\Models\Program;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Teacher\StoreProgramRequest;
use App\Http\Requests\Teacher\UpdateProgramRequest;
use App\Http\Resources\Public\Api\V1\ProgramResource;
use App\Http\Services\ProgramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * @group Teacher Programs
 *
 * APIs for teachers to manage their own programs.
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
     * @queryParam parent_id int Filter by parent program. Example: 5
     * @queryParam code string Filter by exact code. Example: PRG-001
     * @queryParam sort string Sort (newest, oldest, name). Example: newest
     * @queryParam per_page int Items per page. Example: 15
     */
    public function index(): JsonResponse
    {
        $teacher = Auth::guard('teacher')->user();

        $filters = request()->only(['q', 'active', 'parent_id', 'code', 'sort']);
        $perPage  = (int) request()->get('per_page', 15);

        $programs = $this->programService->getTeacherPrograms($teacher->id, $filters, $perPage);

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
        $teacher = Auth::guard('teacher')->user();

        $model = $this->programService->findTeacherProgram($teacher->id, $program);

        if (!$model) {
            return $this->errorResponse(
                \App\Http\Enums\ApiErrorCode::NOT_FOUND,
                'Program not found.',
                null,
                404
            );
        }

        return $this->successResponse(new ProgramResource($model), 'Program retrieved successfully.');
    }

    /**
     * Store Program
     */
    public function store(StoreProgramRequest $request): JsonResponse
    {
        $teacher = Auth::guard('teacher')->user();

        $data = $request->validated();
        $data['teacher_id'] = $teacher->id;
        $data['is_active']  = $data['is_active'] ?? true;

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('programs', 'public');
        }

        $program = Program::create($data);

        return $this->successResponse(
            new ProgramResource($program),
            'Program created successfully.',
            201
        );
    }

    /**
     * Update Program (Owned by Teacher)
     */
    public function update(UpdateProgramRequest $request, int $program): JsonResponse
    {
        $teacher = Auth::guard('teacher')->user();

        $model = $this->programService->findTeacherProgram($teacher->id, $program);

        if (!$model) {
            return $this->errorResponse(
                \App\Http\Enums\ApiErrorCode::NOT_FOUND,
                'Program not found.',
                null,
                404
            );
        }

        $data = $request->validated();

        // remove image
        if (!empty($data['remove_image']) && $model->image) {
            if (Storage::disk('public')->exists($model->image)) {
                Storage::disk('public')->delete($model->image);
            }
            $data['image'] = null;
        }

        // replace image
        if ($request->hasFile('image')) {
            if ($model->image && Storage::disk('public')->exists($model->image)) {
                Storage::disk('public')->delete($model->image);
            }
            $data['image'] = $request->file('image')->store('programs', 'public');
        }

        unset($data['remove_image']);

        $model->update($data);
        $model->refresh();

        return $this->successResponse(new ProgramResource($model), 'Program updated successfully.');
    }

    /**
     * Toggle Active
     */
    public function toggleActive(int $program): JsonResponse
    {
        $teacher = Auth::guard('teacher')->user();

        $model = $this->programService->findTeacherProgram($teacher->id, $program);

        if (!$model) {
            return $this->errorResponse(
                \App\Http\Enums\ApiErrorCode::NOT_FOUND,
                'Program not found.',
                null,
                404
            );
        }

        $model->is_active = !$model->is_active;
        $model->save();

        return $this->successResponse(new ProgramResource($model), 'Program status updated successfully.');
    }

}
