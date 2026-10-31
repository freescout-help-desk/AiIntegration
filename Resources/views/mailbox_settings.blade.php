@extends('layouts.app')
@section('title_full', __('AI Integration').' - '.$mailbox->name)
@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection
@section('content')
    <div class="section-heading">{{ __('AI Integration') }}</div>
    @include('partials/flash_messages')
    <div class="container form-container">
        <form method="POST" action="{{ route('mailboxes.aiintegration.save', ['id' => $mailbox->id]) }}">
            {{ csrf_field() }}
            <p class="block-help">{{ __('The mailbox prompt extends or replaces the module base prompt. Authorized users can then extend or replace the resulting prompt in their profile.') }}</p>
            @include('aiintegration::partials/prompt_fields')
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
        </form>
    </div>
@endsection
