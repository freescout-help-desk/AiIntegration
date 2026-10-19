<div class="aii-draft" data-conversation-id="{{ $conversation_id }}">
    <div class="aii-draft-result hidden"></div>

    <div class="text-center modal-loader hidden">
        <img src="{{ asset(\Module::getPublicPath(AII_MODULE).'/img/loader.svg') }}" />
    </div>

    <div class="aii-draft-chooser">
        <div class="form-group">
            <button type="button" class="btn btn-primary aii-draft-auto">
                <span class="text-larger">✦</span> {{ __('Draft automatically') }}
            </button>
        </div>
        <div class="form-group margin-top">
            <label>{{ __('Instructions for the AI') }}</label>
            <small class="text-help pull-right aii-draft-counter">0 / {{ \AiIntegration::MAX_INSTRUCTIONS_LENGTH }}</small>
            <textarea class="form-control aii-draft-instructions" rows="3" maxlength="{{ \AiIntegration::MAX_INSTRUCTIONS_LENGTH }}" placeholder="{{ __('E.g.: Tell them the refund was issued today and takes 5–7 business days') }}"></textarea>
        </div>
        <div class="form-group">
            <button type="button" class="btn btn-default aii-draft-custom" title="Ctrl+Enter" disabled>
                {{ __('Draft with instructions') }}
            </button>
        </div>
    </div>
</div>
