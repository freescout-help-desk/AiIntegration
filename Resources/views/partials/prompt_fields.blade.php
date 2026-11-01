<div class="form-group{{ $errors->has($field_prefix.'prompt') ? ' has-error' : '' }}">
    <label for="aii_custom_prompt" class="col-sm-2 control-label">{{ __('AI Draft Prompt') }}</label>
    <div class="col-sm-6">
        <textarea id="aii_custom_prompt" class="form-control" name="{{ $name_prompt }}" rows="10" maxlength="{{ \AiIntegration::MAX_PROMPT_LENGTH }}">{{ old($old_prompt, $prompt_settings['prompt']) }}</textarea>
        @include('partials/field_error', ['field' => $field_prefix.'prompt'])
        <div class="controls">
            <label for="aii_replace" class="checkbox inline plain">
                <input type="checkbox" name="{{ $name_replace }}" value="1" id="aii_replace" @if (old($old_replace, $prompt_settings['replace'])) checked @endif> <span class="text-help">{{ __('Replace base prompt') }}</span>
            </label>
            @include('partials/field_error', ['field' => $field_prefix.'replace'])
        </div>
    </div>
</div>