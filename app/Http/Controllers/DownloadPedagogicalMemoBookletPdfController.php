<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\User;
use App\Services\PedagogicalMemoPrintService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class DownloadPedagogicalMemoBookletPdfController extends Controller
{
    public function __invoke(
        Request $request,
        string $locale,
        PedagogicalMemoPrintService $printer,
    ): StreamedResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $teacher = $user;
        if ($request->has('teacher_id') && ($user->is_super_admin ?? false || $user->role?->value === 'admin' || $user->role?->value === 'principal')) {
            $teacher = User::find($request->integer('teacher_id')) ?? $user;
        }

        $subjectId = $request->filled('subject_id') ? $request->integer('subject_id') : ($request->filled('subject') ? $request->integer('subject') : null);
        $levelId = $request->filled('level_id') ? $request->integer('level_id') : ($request->filled('level') ? $request->integer('level') : null);

        if (! $levelId && $request->filled('classroom')) {
            $classroom = Classroom::find($request->integer('classroom'));
            $levelId = $classroom?->level_id;
        }

        $memoIds = null;
        if ($request->filled('memos')) {
            $raw = $request->input('memos');
            $memoIds = is_array($raw) ? array_map('intval', $raw) : array_filter(array_map('intval', explode(',', (string) $raw)));
        }

        try {
            return $printer->bookletPdfResponse(
                teacher: $teacher,
                subjectId: $subjectId,
                levelId: $levelId,
                memoIds: $memoIds,
            );
        } catch (Throwable $exception) {
            report($exception);

            abort(500, __('تعذر إنشاء ملف دفتر التحضير البيداغوجي (PDF).'));
        }
    }
}
