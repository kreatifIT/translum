@extends('statamic::layout')
@section('title', __('translum::labels.translation_manager') )


@section('content')

    <publish-form
        title="{{ $title }}"
        action="{{ $action }}"
        :blueprint='@json($blueprint)'
        :meta='@json($meta)'
        :values='@json($values)'
    ></publish-form>

@endsection
