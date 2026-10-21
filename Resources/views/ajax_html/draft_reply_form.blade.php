<div class="aii-draft" data-conversation-id="{{ $conversation_id }}" data-auto-draft="{{ $auto_draft ? 1 : 0 }}">
    <div class="aii-drafts"></div>

    <div class="text-center modal-loader hidden">
        <img src="{{ asset(\Module::getPublicPath(AII_MODULE).'/img/loader.svg') }}" />
    </div>

    <div class="aii-draft-composer @if ($auto_draft) hidden @endif">
        <div class="input-group">
            <input type="text" class="form-control aii-draft-instructions" maxlength="{{ \AiIntegration::MAX_INSTRUCTIONS_LENGTH }}" placeholder="{{ __('Instructions for the AI') }}">
            <span class="input-group-btn">
                <button type="button" class="btn btn-default aii-draft-send">{{ __('Send') }}</button>
            </span>
        </div>
    </div>
</div>
