<div class="row-container">
    <div class="form-horizontal">
        <div class="form-group margin-bottom-10">
            <div class="col-sm-12">
                {{--<div class="aii-reply-loader text-center">
                    <img src="{{ asset(\Module::getPublicPath(AII_MODULE).'/img/loader.svg') }}" height="150">
                </div>--}}
                @if (!$error)
                    <pre class="aii-generated-reply aii-pre"></pre>
                    <div class="aii-generated-reply-hidden hidden">{{ $reply }}</div>

                    @if ($translation)
                        <pre id="aii_translation" class="collapse alert alert-info margin-bottom-5 aii-pre">{{ $translation }}</pre>
                        <p class="text-right">
                            <a href="#aii_translation" data-toggle="collapse">{{ __('Translation') }} ({{ AiIntegration::userLanguageName() }}) <small>▼</small></a>
                        </p>
                    @endif
                @else
                    <div class="alert alert-danger">
                        {{ $error }}
                    </div>
                @endif
            </div>
        </div>

        @if (!$error)
            <div class="form-group">
                <div class="col-sm-12">
                    <button type="button" class="btn btn-warning aii-reply-apply">
                        <i class="glyphicon glyphicon-ok"></i>&nbsp; {{ __('Use (as Draft)') }}
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>