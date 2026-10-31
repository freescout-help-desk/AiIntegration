<div class="form-group">
    <label class="col-sm-2 control-label">{{ __('AI Integration') }}</label>
    <div class="col-sm-6">
        <p class="form-help">{{ __('Personal prompts are applied after the mailbox prompt. Disabling inheritance replaces both the module and mailbox prompts. A personal prompt is used only while the user has permission to customize it.') }}</p>
        @include('aiintegration::partials/prompt_fields', ['prompt_settings' => \AiIntegration::userPrompt($user)])
    </div>
</div>
