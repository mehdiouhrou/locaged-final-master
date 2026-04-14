@php
    $multiUpload = count($documentInfos) > 1;
@endphp
<div>
    <div class="form-section">
        <div class="form-title">{{ ui_t('pages.upload.upload_documents') }}</div>
        <p class="text-muted small mb-0 px-1">{{ __('Ajoutez un ou plusieurs fichiers, puis complétez les métadonnées sur cette même page.') }}</p>

        <div id="upload-flow-top" class="mt-4">
            @include('documents.step1-attach')
        </div>

        @if(count($documents) > 0)
            <div class="mt-4 pt-4 border-top">
                @include('documents.step2-documentinfo')
            </div>
        @endif
    </div>
</div>
