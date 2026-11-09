<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ImpersonationService;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    /**
     * Start impersonating a teacher.
     */
    public function start(
        Request $request,
        string $locale,
        User $user,
        ImpersonationService $service
    ): RedirectResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        if (! $service->canImpersonate($actor) || ! $service->canBeImpersonated($user, $actor)) {
            abort(403, 'غير مصرح لك بمعاينة هذا الحساب.');
        }

        $service->impersonate($user);

        Notification::make()
            ->title('تم تفعيل وضع المعاينة الفنية')
            ->body("أنت الآن تتصفح المنصة بصفتك الأستاذ: {$user->name}")
            ->warning()
            ->send();

        return redirect()->route('filament.auth.pages.dashboard', ['locale' => $locale]);
    }

    /**
     * Leave impersonation and return to the original administrative/developer account.
     */
    public function leave(
        Request $request,
        string $locale,
        ImpersonationService $service
    ): RedirectResponse {
        if (! $service->isImpersonating()) {
            return redirect()->route('filament.auth.pages.dashboard', ['locale' => $locale]);
        }

        $original = $service->leave();

        Notification::make()
            ->title('تم إنهاء وضع المعاينة')
            ->body($original ? "تمت العودة بنجاح إلى حسابك: {$original->name}" : 'تمت العودة لحسابك الأصلي')
            ->success()
            ->send();

        return redirect()->route('filament.auth.pages.dashboard', ['locale' => $locale]);
    }
}
