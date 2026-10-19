@php
    $icons = [
        'book' => 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25',
        'plus' => 'M12 4.5v15m7.5-7.5h-15',
        'calendar' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5',
        'palette' => 'M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75c1.214 0 2.39-.256 3.486-.745a.75.75 0 0 0 .394-1.338 7.53 7.53 0 0 1-4.797-4.797.75.75 0 0 0-1.337-.394A9.71 9.71 0 0 1 12 2.25Zm2.25 5.25a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Zm-4.5 0a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3ZM7.5 13.5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Z',
        'users' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
        'academic' => 'M4.26 10.147a60.436 60.436 0 0 0-.491 6.347A48.627 48.627 0 0 1 12 20.904a48.627 48.627 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.57 50.57 0 0 0-2.658-.813A59.905 59.905 0 0 1 12 3.493a59.902 59.902 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z',
        'user-group' => 'M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z',
        'building' => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21',
    ];
@endphp

<style>
    .sajel-quick-links {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
        gap: 1rem;
    }
    .sajel-quick-link {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        padding: 1rem;
        border: 1px solid var(--gray-200, #e5e7eb);
        border-radius: 0.9rem;
        background: var(--white, #fff);
        text-decoration: none;
        transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
    }
    .sajel-quick-link:hover {
        transform: translateY(-2px);
        border-color: var(--primary-300, #fcd34d);
        box-shadow: 0 10px 15px -3px rgb(0 0 0 / .08), 0 4px 6px -4px rgb(0 0 0 / .08);
    }
    .sajel-quick-link__icon {
        display: grid;
        place-items: center;
        flex-shrink: 0;
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 0.7rem;
        background: var(--primary-50, #fffbeb);
        color: var(--primary-600, #d97706);
    }
    .sajel-quick-link__icon svg { width: 1.35rem; height: 1.35rem; }
    .sajel-quick-link__label {
        display: block;
        font-weight: 700;
        font-size: .925rem;
        color: var(--gray-900, #111827);
    }
    .sajel-quick-link__desc {
        display: block;
        font-size: .75rem;
        color: var(--gray-500, #6b7280);
        margin-top: .1rem;
    }
</style>

<div class="sajel-quick-links">
    @foreach ($links as $link)
        <a href="{{ $link['url'] }}" class="sajel-quick-link">
            <span class="sajel-quick-link__icon">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$link['icon']] ?? $icons['book'] }}"/>
                </svg>
            </span>
            <span>
                <span class="sajel-quick-link__label">{{ $link['label'] }}</span>
                <span class="sajel-quick-link__desc">{{ $link['description'] }}</span>
            </span>
        </a>
    @endforeach
</div>
