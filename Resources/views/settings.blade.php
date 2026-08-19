<form class="form-horizontal margin-top margin-bottom" method="POST" action="">
    {{ csrf_field() }}

    <div class="form-group">
        <label class="col-sm-2 control-label">{{ __('Status') }}</label>
        <div class="col-sm-6">
            <label class="control-label">
                @if (\AiIntegration::isEnabled())
                    <strong class="text-success"><i class="glyphicon glyphicon-ok"></i> {{ __('Active') }}</strong>
                @else
                    <strong class="text-warning">{{ __('Inactive') }}</strong>
                @endif
            </label>
            @if (!empty($last_log_message))
                <div class="margin-top-10 text-help">{{ __('Last log message') }} (<a href="{{ route('logs', ['name' => \AiIntegration::LOG_NAME]) }}" target="_blank">{{ __('View log') }}</a>):</div>
                <pre class="margin-bottom-0 margin-top-5 input-sized-lg alert alert-warning">[{{ App\User::dateFormat($last_log_message->created_at) }}] {{ $last_log_message->description }}</pre>
            @endif
        </div>
    </div>

    <div class="form-group{{ $errors->has('settings.aiintegration.provider') ? ' has-error' : '' }} margin-bottom-10">
        <label for="aiintegration_provider" class="col-sm-2 control-label">{{ __('AI Provider') }}</label>

        <div class="col-sm-6">
            <select id="aiintegration_provider" class="form-control input-sized-lg" name="settings[aiintegration.provider]">
                @foreach (AiIntegration::getProviders() as $prodier_code => $provider)
                    <option value="{{ $prodier_code }}" @if ($settings['aiintegration.provider'] == $prodier_code) selected @endif data-aii-base-url="{{ $provider['base_url'] }}">{{ $provider['name'] }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="form-group{{ $errors->has('settings.aiintegration.api_key') ? ' has-error' : '' }}">
        <label for="aiintegration_api_key" class="col-sm-2 control-label">{{ __('API Key') }}</label>

        <div class="col-sm-6">
            <input id="aiintegration_api_key" type="password" class="form-control input-sized-lg" name="settings[aiintegration.api_key]" value="{{ \Helper::safePassword(old('settings.aiintegration.api_key', $settings['aiintegration.api_key'])) }}">
        </div>
    </div>

    <div class="form-group{{ $errors->has('settings.aiintegration.base_url') ? ' has-error' : '' }} margin-bottom-10">
        <label for="aiintegration_base_url" class="col-sm-2 control-label">{{ __('Base URL') }}</label>

        <div class="col-sm-6">
            <input id="aiintegration_base_url" type="text" class="form-control input-sized-lg" name="settings[aiintegration.base_url]" value="{{ old('settings.aiintegration.base_url', $settings['aiintegration.base_url']) }}" placeholder="{{ __('(optional)') }}">

            <p class="form-help" id="aii_default_base_url">
                {{ __('Default') }}: <span id="aii_default_base_url_value">{{ AiIntegration::getProvider()['base_url'] ?? '' }}</span>
            </p>
        </div>
    </div>

    <div class="form-group{{ $errors->has('settings.aiintegration.model') ? ' has-error' : '' }} margin-bottom-10">
        <label for="aiintegration_model" class="col-sm-2 control-label">{{ __('Model') }}</label>

        <div class="col-sm-6">
            <select type="text" id="aiintegration_model" class="form-control input-sized-lg" name="settings[aiintegration.model]" autocomplete="off">
                @php
                    $model = old('settings.aiintegration.model', $settings['aiintegration.model']);
                @endphp
                @if ($model)
                    <option value="{{ $model }}">{{ $model }}</option>
                @endif
            </select>
        </div>
    </div>

    <div class="form-group margin-top margin-bottom">
        <div class="col-sm-6 col-sm-offset-2">
            <button type="submit" class="btn btn-primary">
                {{ __('Save') }}
            </button>
        </div>
    </div>
</form>

@section('javascript')
    @parent
    aiiInitSettings();
@endsection