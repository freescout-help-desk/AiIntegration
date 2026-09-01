<div class="conv-top-block clearfix">
    <a href="{{ route('aiintegration.ajax_html', ['action' => 'generate_reply', 'param' => $conversation_id]) }}?t={{ time() }}" class="btn btn-primary" data-trigger="modal" data-modal-title="✦ {{ __('Draft Reply (AI)') }}" data-modal-no-footer="true" data-modal-on-show="aiiDraftReplyModal" data-modal-loader="{{ \Module::getPublicPath(AII_MODULE).'/img/loader.svg' }}" data-modal-class="aii-modal-reply"><span class="text-larger">✦</span> {{ __('Draft Reply (AI)') }}</a>
    <button class="btn btn-default" type="button" id="aii_summarize" data-loading-text="… {{ __('Summarize') }}">≡ {{ __('Summarize') }}</button>

    <div class="aii-result hidden margin-top-10" id="aii_summary"></div>
</div>
