<div class="form-group">
    <label for="aii_use_base">{{ __('Use inherited base prompt') }}</label>
    <input type="hidden" name="aiintegration_prompt[use_base]" value="0">
    <div class="onoffswitch-wrap"><div class="onoffswitch">
        <input type="checkbox" class="onoffswitch-checkbox" id="aii_use_base" name="aiintegration_prompt[use_base]" value="1" @if (old('aiintegration_prompt.use_base', $prompt_settings['use_base'])) checked @endif>
        <label class="onoffswitch-label" for="aii_use_base"></label>
    </div></div>
    @include('partials/field_error', ['field' => 'aiintegration_prompt.use_base'])
</div>
<div class="form-group">
    <label for="aii_custom_prompt">{{ __('Custom prompt') }}</label>
    <textarea id="aii_custom_prompt" class="form-control" name="aiintegration_prompt[prompt]" rows="10" maxlength="{{ \AiIntegration::MAX_PROMPT_LENGTH }}">{{ old('aiintegration_prompt.prompt', $prompt_settings['prompt']) }}</textarea>
    @include('partials/field_error', ['field' => 'aiintegration_prompt.prompt'])
    <p class="form-help">{{ __('When enabled, your text extends the inherited prompt. When disabled, enter a complete replacement. An empty extension keeps the inherited prompt. These settings apply to draft replies; response format and translation requirements always apply.') }}</p>
</div>
