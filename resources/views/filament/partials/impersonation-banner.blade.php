@php
    $impersonationService = app(\App\Services\ImpersonationService::class);
    $isImpersonating = $impersonationService->isImpersonating();
    $originalUser = $isImpersonating ? $impersonationService->getOriginalUser() : null;
    $currentUser = auth()->user();
@endphp

@if ($isImpersonating && $currentUser)
    <div class="sticky top-0 z-50 w-full border-b border-amber-500/30 bg-gradient-to-r from-amber-600 via-amber-700 to-amber-800 text-white shadow-md" dir="rtl">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-2.5 sm:px-6">
            <div class="flex items-center gap-3">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/20 text-white backdrop-blur-xs">
                    <x-filament::icon icon="heroicon-o-eye" class="h-5 w-5" />
                </span>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-md bg-white/20 px-2 py-0.5 text-[11px] font-black uppercase tracking-wider text-amber-100">
                            {{ __('وضع المعاينة الفنية') }}
                        </span>
                        <p class="text-xs sm:text-sm font-bold text-white">
                            {{ __('تتصفح المنصة حالياً بصفتك الأستاذ: ') }}
                            <span class="underline decoration-amber-300 underline-offset-4">{{ $currentUser->name }}</span>
                            @if($currentUser->primarySubject)
                                <span class="text-amber-200">({{ $currentUser->primarySubject->name }})</span>
                            @endif
                        </p>
                    </div>
                    @if($originalUser)
                        <p class="text-[11px] text-amber-200/90">
                            {{ __('الحساب الأصلي: ') }} {{ $originalUser->name }} ({{ $originalUser->role?->label() ?? 'مطور/إدارة' }})
                        </p>
                    @endif
                </div>
            </div>

            <div>
                <form action="{{ route('impersonate.leave', ['locale' => app()->getLocale()]) }}" method="POST">
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-red-600 px-3.5 py-1.5 text-xs font-black text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-2"
                    >
                        <x-filament::icon icon="heroicon-o-arrow-left-on-rectangle" class="h-4 w-4" />
                        {{ __('إنهاء المعاينة والعودة لحسابي') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
@endif

