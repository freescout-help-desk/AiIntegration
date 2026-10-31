<div class="text-help margin-bottom-10">
    {{ __('Generated on :date', ['date' => $summary->generated_at.' UTC']) }}
    <button class="btn btn-default btn-xs aii-refresh-summary" type="button" data-loading-text="… {{ __('Refresh') }}">{{ __('Refresh') }}</button>
</div>
@if ($summary_outdated)
    <div class="text-warning margin-bottom-10">{{ __('This summary is outdated. Refresh it to include the latest conversation activity.') }}</div>
@endif
<div class="aii-summary-text">{{ $summary->summary }}</div>
