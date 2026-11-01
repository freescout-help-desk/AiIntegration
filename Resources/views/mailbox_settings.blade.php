@extends('layouts.app')
@section('title_full', __('AI Integration').' - '.$mailbox->name)
@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection
@section('content')
    <div class="section-heading">{{ __('AI Integration') }}</div>
    @include('partials/flash_messages')
    <div class="row-container">
        <div class="row">
            <div class="col-xs-12">
                <form class="form-horizontal margin-top margin-bottom" method="POST" action="{{ route('mailboxes.aiintegration.save', ['id' => $mailbox->id]) }}">
                    {{ csrf_field() }}

                    @include('aiintegration::partials/prompt_fields', [
                        'name_prompt'  => 'settings[prompt]',
                        'name_replace' => 'settings[replace]',
                        'old_prompt'   => 'settings.prompt',
                        'old_replace'  => 'settings.replace',
                        'field_prefix' => '',
                    ])

                    <div class="form-group margin-top margin-bottom">
                        <div class="col-sm-6 col-sm-offset-2">
                            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
