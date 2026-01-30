@props(['pageTitle' => 'Page', 'crumbs' => null, 'iconHtml' => null])

@php
    use App\Models\MenuOption;
    use App\Helpers\MenuHelper;

    $currentPath = '/' . trim(request()->path(), '/');
    $currentRouteName = optional(request()->route())->getName();

    $menuOption = MenuOption::query()
        ->where('status', 1)
        ->where(function ($query) use ($currentPath, $currentRouteName) {
            if ($currentRouteName) {
                $query->orWhere('action', $currentRouteName);
            }
            $query->orWhere('action', $currentPath)
                ->orWhere('action', ltrim($currentPath, '/'));
        })
        ->first();

    $menuIcon = $menuOption?->icon ? MenuHelper::getIconSvg($menuOption->icon) : null;
    $queryIcon = request()->query('icon');
    $queryIcon = is_string($queryIcon) && preg_match('/^ri-[a-z0-9-]+$/', $queryIcon) ? $queryIcon : null;
    $queryIconHtml = $queryIcon ? '<i class="' . $queryIcon . '"></i>' : null;
    $pageIcon = $iconHtml ?: $queryIconHtml ?: $menuIcon;
@endphp

<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div class="flex items-center gap-2">
        @if ($pageIcon)
            <span class="text-gray-500 dark:text-gray-400">{!! $pageIcon !!}</span>
        @endif
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">
            {{ $pageTitle }}
        </h2>
    </div>
    <nav>
        @if (!empty($crumbs))
            <ol class="flex items-center gap-1.5">
                <li>
                    <a
                        class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400"
                        href="{{ url('/') }}"
                    >
                        Home
                        <svg
                            class="stroke-current"
                            width="17"
                            height="16"
                            viewBox="0 0 17 16"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366"
                                stroke=""
                                stroke-width="1.2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </a>
                </li>
                @foreach ($crumbs as $index => $crumb)
                    @php
                        $isLast = $index === array_key_last($crumbs);
                        $label = $crumb['label'] ?? '';
                        $url = $crumb['url'] ?? null;
                    @endphp
                    <li class="text-sm {{ $isLast ? 'text-gray-800 dark:text-white/90' : 'text-gray-500 dark:text-gray-400' }}">
                        @if (!$isLast && $url)
                            <a class="inline-flex items-center gap-1.5" href="{{ $url }}">
                                {{ $label }}
                                <svg
                                    class="stroke-current"
                                    width="17"
                                    height="16"
                                    viewBox="0 0 17 16"
                                    fill="none"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path
                                        d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366"
                                        stroke=""
                                        stroke-width="1.2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </a>
                        @else
                            {{ $label }}
                        @endif
                    </li>
                @endforeach
            </ol>
        @else
            <ol class="flex items-center gap-1.5">
                <li>
                    <a
                        class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400"
                        href="{{ url('/') }}"
                    >
                        Home
                        <svg
                            class="stroke-current"
                            width="17"
                            height="16"
                            viewBox="0 0 17 16"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366"
                                stroke=""
                                stroke-width="1.2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </a>
                </li>
                <li class="text-sm text-gray-800 dark:text-white/90">
                    {{ $pageTitle }}
                </li>
            </ol>
        @endif
    </nav>
</div>
