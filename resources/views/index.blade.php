@extends('statamic::layout')
@section('title', __('translum::labels.translation_manager') )


@section('content')

    <div class="mb-4">
        @if($search['enabled'])
            <div class="card p-2 mb-3">
                <form method="GET" action="{{ cp_route('translum.index') }}" class="flex items-center gap-2">
                    <div class="flex-1">
                        <input
                            type="text"
                            name="search"
                            value="{{ $search['query'] }}"
                            placeholder="Search translations (keys and values)..."
                            class="input-text"
                        >
                    </div>
                    @if($pagination['enabled'])
                        <input type="hidden" name="page" value="1">
                    @endif
                    <button type="submit" class="btn-primary">
                        Search
                    </button>
                    @if($search['query'])
                        <a href="{{ cp_route('translum.index') }}" class="btn">
                            Clear
                        </a>
                    @endif
                </form>
            </div>
        @endif

        @if($pagination['enabled'])
            <div class="card p-2 flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing page {{ $pagination['current_page'] }} of {{ $pagination['total_pages'] }}
                    ({{ $pagination['total_keys'] }} total keys, {{ $pagination['per_page'] }} per page)
                </div>

                @if($pagination['total_pages'] > 1)
                    <div class="flex items-center gap-2">
                        @if($pagination['current_page'] > 1)
                            <a href="{{ cp_route('translum.index', array_filter(['page' => $pagination['current_page'] - 1, 'search' => $search['query']])) }}"
                               class="btn btn-sm">
                                ← Previous
                            </a>
                        @endif

                        <div class="flex gap-1">
                            @php
                                $start = max(1, $pagination['current_page'] - 2);
                                $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
                            @endphp

                            @if($start > 1)
                                <a href="{{ cp_route('translum.index', array_filter(['page' => 1, 'search' => $search['query']])) }}"
                                   class="btn btn-sm">1</a>
                                @if($start > 2)
                                    <span class="px-2">...</span>
                                @endif
                            @endif

                            @for($i = $start; $i <= $end; $i++)
                                <a href="{{ cp_route('translum.index', array_filter(['page' => $i, 'search' => $search['query']])) }}"
                                   class="btn btn-sm {{ $i == $pagination['current_page'] ? 'btn-primary' : '' }}">
                                    {{ $i }}
                                </a>
                            @endfor

                            @if($end < $pagination['total_pages'])
                                @if($end < $pagination['total_pages'] - 1)
                                    <span class="px-2">...</span>
                                @endif
                                <a href="{{ cp_route('translum.index', array_filter(['page' => $pagination['total_pages'], 'search' => $search['query']])) }}"
                                   class="btn btn-sm">{{ $pagination['total_pages'] }}</a>
                            @endif
                        </div>

                        @if($pagination['current_page'] < $pagination['total_pages'])
                            <a href="{{ cp_route('translum.index', array_filter(['page' => $pagination['current_page'] + 1, 'search' => $search['query']])) }}"
                               class="btn btn-sm">
                                Next →
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    </div>

    <publish-form
        title="{{ $title }}"
        action="{{ $action }}"
        :blueprint='@json($blueprint)'
        :meta='@json($meta)'
        :values='@json($values)'
    ></publish-form>

    @if($pagination['enabled'] && $pagination['total_pages'] > 1)
        <div class="card p-2 mt-4 flex items-center justify-between">
            <div class="text-sm text-gray-700">
                Page {{ $pagination['current_page'] }} of {{ $pagination['total_pages'] }}
            </div>

            <div class="flex items-center gap-2">
                @if($pagination['current_page'] > 1)
                    <a href="{{ cp_route('translum.index', array_filter(['page' => $pagination['current_page'] - 1, 'search' => $search['query']])) }}"
                       class="btn btn-sm">
                        ← Previous
                    </a>
                @endif

                @if($pagination['current_page'] < $pagination['total_pages'])
                    <a href="{{ cp_route('translum.index', array_filter(['page' => $pagination['current_page'] + 1, 'search' => $search['query']])) }}"
                       class="btn btn-sm">
                        Next →
                    </a>
                @endif
            </div>
        </div>
    @endif

@endsection
