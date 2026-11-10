<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\User;
use App\Services\DailyLogRegisterService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PrintDailyLogsController extends Controller
{
    public function __invoke(Request $request, DailyLogRegisterService $register): View
    {
        $validated = $request->validate([
            'ids' => ['required_without:classroom_id', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct'],
            'classroom_id' => ['required_without:ids', 'integer', 'exists:classrooms,id'],
        ]);

        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $classroomId = isset($validated['classroom_id']) ? (int) $validated['classroom_id'] : null;
        $ids = isset($validated['ids']) ? array_values(array_unique(array_map('intval', $validated['ids']))) : [];

        if ($classroomId !== null) {
            $classroom = Classroom::findOrFail($classroomId);
            $logs = $register->completedLogsForClassroom($classroom, $user, $ids ?: null);
        } else {
            $logs = $register->completedLogs($ids, $user);
        }

        return view('print.register', $register->viewData(
            logs: $logs,
            user: $user,
            activeClassroomId: $classroomId,
        ));
    }
}
