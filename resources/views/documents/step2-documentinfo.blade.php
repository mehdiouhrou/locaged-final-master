@php
    $multiUpload = count($documentInfos) > 1;
@endphp
<div>
        <h3 class="section-label py-4 px-5 px-md-0">
            <img src="{{ asset('assets/Clip path group (1).svg') }}" alt="{{ __('actions.file') }}"> {{ __('pages.upload.document_information') }}
        </h3>
        @if($multiUpload)
            <div class="px-5 px-md-0 mb-3">
                <div class="form-check form-switch small">
                    <input class="form-check-input" type="checkbox" id="useSharedMetadataSwitch" wire:model.live="useSharedMetadata">
                    <label class="form-check-label" for="useSharedMetadataSwitch">
                        {{ $useSharedMetadata ? __('pages.upload.shared_metadata_all') : __('pages.upload.shared_metadata_each') }}
                    </label>
                </div>
                <p class="small text-muted mb-0">{{ __('pages.upload.shared_metadata_help') }}</p>
            </div>
            <div class="px-5 px-md-0 mb-4">
                <div class="d-flex flex-column flex-sm-row flex-sm-wrap align-items-stretch align-items-sm-center gap-2 mb-2">
                    <span class="small text-muted text-nowrap">{{ __('pages.upload.files_heading') }}</span>
                    <div class="d-flex flex-column flex-sm-row flex-sm-wrap gap-2 flex-grow-1" style="min-width: 0;">
                        @foreach($documentInfos as $i => $_meta)
                            @php
                                $fname = $documentOriginalNames[$i] ?? ($_meta['title'] ?? '—');
                            @endphp
                            <button type="button"
                                    wire:click="selectDocument({{ $i }})"
                                    class="btn btn-sm text-start {{ (int) $currentDocumentIndex === $i ? 'btn-primary' : 'btn-outline-secondary' }}"
                                    style="min-width: 0; max-width: 100%;"
                                    title="{{ $fname }}">
                                <span class="d-block text-truncate">{{ $fname }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
                <button type="button"
                        class="btn btn-outline-primary btn-sm"
                        wire:click="applyCurrentMetadataToAllDocuments">
                    {{ __('pages.upload.apply_metadata_all') }}
                </button>
            </div>
        @endif
        <div class="d-none" x-data x-init="$wire.currentInfo?.color || $wire.set('currentInfo.color','Blue')">
            <label class="form-label">{{ __('pages.upload.organize_by_color') }} <span class="text-danger">*</span></label>
            <div class="d-flex gap-3 align-items-center">
                <label class="d-inline-flex align-items-center">
                    <input type="radio" class="form-check-input me-2" name="color" value="Blue" wire:model="currentInfo.color">
                    <span>{{ __('pages.upload.color_blue') }}</span>
                </label>
                <label class="d-inline-flex align-items-center">
                    <input type="radio" class="form-check-input me-2" name="color" value="Red" wire:model="currentInfo.color">
                    <span>{{ __('pages.upload.color_red') }}</span>
                </label>
                <label class="d-inline-flex align-items-center">
                    <input type="radio" class="form-check-input me-2" name="color" value="Green" wire:model="currentInfo.color">
                    <span>{{ __('pages.upload.color_green') }}</span>
                </label>
            </div>
        </div>
        <div class="row g-4 align-items-stretch">
            <div class="col-lg-6 d-flex">
                <div class="form-box shadow-sm bg-white p-0 w-100 h-100 d-flex align-items-center justify-content-center">
                    @if($currentPreviewUrl && $this->currentPreviewType === 'image')
                        <img src="{{ $currentPreviewUrl }}" class="img-fluid w-100 h-100" style="object-fit: contain;" alt="{{ __('pages.upload.image_preview') }}" />
                    @elseif($currentPreviewUrl && $this->currentPreviewType === 'pdf')
                        {{-- PDF.js renders into #upload-pdfjs-viewer (see resources/js/upload-pdf-preview.js). --}}
                        <div wire:ignore class="w-100 h-100 overflow-auto d-flex align-items-start justify-content-center" style="min-height:280px;max-height:72vh;">
                            <div id="upload-pdfjs-viewer" class="w-100"></div>
                        </div>
                    @else
                        <div class="text-muted p-3">{{ __('pages.upload.no_preview') }}</div>
                    @endif
                </div>
            </div>

            <div class="col-lg-6 d-flex">
                <div class="form-box shadow-sm bg-white p-4 w-100 h-100">
                    <div class="row">
                        <!-- File Name -->
                        @if(count($documentInfos) === 1 || ($multiUpload && !$useSharedMetadata))
                            <div class="col-md-12 mb-3">
                                <label class="form-label">{{ __('pages.upload.file_name') }}<span class="text-danger">*</span></label>
                                <input type="text" class="form-control" placeholder="{{ __('pages.upload.file_name') }}" required
                                       wire:model="currentInfo.title"
                                />
                            </div>
                        @endif

                        @php
                            $selectedCategoryId = $currentInfo['category_id'] ?? null;
                            $lockCategoryFromContext = !is_null($categoryId ?? null);
                        @endphp

                        <div class="col-md-12 mb-3">
                            <p class="small text-muted mb-0">{{ __('La structure (pôle, département, service) du document est déduite de votre compte, pas de la catégorie.') }}</p>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('pages.upload.category') }}<span class="text-danger">*</span></label>
                            <select id="categorySelect"
                                    class="form-select @error('currentInfo.category_id') is-invalid @enderror"
                                    required
                                    wire:model.change="currentInfo.category_id"
                                    @if($lockCategoryFromContext) disabled @endif>
                                <option value="">{{ __('pages.upload.select_category') }}</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected($selectedCategoryId == $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('currentInfo.category_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Subcategory (filtered by selected Category, now optional) -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('pages.upload.subcategory') }}</label>
                            <select id="subcategorySelect"
                                    class="form-select @error('currentInfo.subcategory_id') is-invalid @enderror"
                                    wire:model.change="currentInfo.subcategory_id"
                                    @if(!$selectedCategoryId) disabled @endif>
                                <option value="" selected>{{ __('pages.upload.select_subcategory') }}</option>
                                @foreach($subcategories as $subcategory)
                                    @if(!$selectedCategoryId || $subcategory->category_id == $selectedCategoryId)
                                        <option value="{{ $subcategory->id }}">
                                            {{ $subcategory->name }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            @error('currentInfo.subcategory_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            @if(!$selectedCategoryId)
                                <small class="text-muted">{{ __('pages.upload.select_category_first') }}</small>
                            @endif
                        </div>

                        <!-- Creation Date -->
                        <div class="col-md-6">
                            <label for="created_at" class="form-label">{{ __('pages.upload.date_and_time') }} <span class="text-danger">*</span></label>
                            <input
                                type="datetime-local"
                                class="form-control @error('currentInfo.created_at') is-invalid @enderror"
                                id="created_at"
                                wire:model.change="currentInfo.created_at"
                                required
                            >
                            @error('currentInfo.created_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Expire Date (always read-only; computed from selected category policy) -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('pages.upload.expire_date') }}<span class="text-danger">*</span></label>
                            @php
                                $selCat = ($currentInfo['category_id'] ?? null) ? $categories->firstWhere('id', $currentInfo['category_id']) : null;
                                $autoExpiry = $selCat && $selCat->expiry_value && $selCat->expiry_unit;
                            @endphp
                            <input type="date" class="form-control @error('currentInfo.expire_at') is-invalid @enderror" required
                                   wire:model="currentInfo.expire_at"
                                   readonly style="background-color: #f8f9fa; cursor: not-allowed;"
                            />
                            @error('currentInfo.expire_at')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            @if($autoExpiry)
                                <small class="text-muted">{{ __('pages.upload.expiry_hint', ['value' => $selCat->expiry_value, 'unit' => $selCat->expiry_unit]) }}</small>
                            @else
                                <small class="text-muted">{{ __('pages.upload.select_subcategory_hint') }}</small>
                            @endif
                        </div>

                        <!-- Tags (saisie libre + suggestions) -->
                        <div class="col-md-12 mb-3">
                            <label for="tagInputDraft" class="form-label">{{ __('pages.upload.tags') }}</label>
                            @error('currentInfo.tag_ids')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                            @error('currentInfo.new_tag_names')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                            <div class="d-flex flex-wrap gap-1 mb-2">
                                @foreach($currentInfo['tag_ids'] ?? [] as $tid)
                                    @php $tagRow = $tags->firstWhere('id', (int) $tid); @endphp
                                    @if($tagRow)
                                        <span class="badge bg-secondary d-inline-flex align-items-center gap-1">
                                            {{ $tagRow->name }}
                                            <button type="button" class="btn btn-sm btn-link text-white text-decoration-none p-0 lh-1" wire:click="removeTagId({{ (int) $tid }})" aria-label="{{ __('pages.upload.remove_tag') }}">&times;</button>
                                        </span>
                                    @endif
                                @endforeach
                                @foreach($currentInfo['new_tag_names'] ?? [] as $nt)
                                    <span class="badge bg-info text-dark d-inline-flex align-items-center gap-1">
                                        {{ $nt }}
                                        <button type="button" class="btn btn-sm btn-link text-dark text-decoration-none p-0 lh-1" wire:click="removeNewTagName('{{ addslashes($nt) }}')" aria-label="{{ __('actions.remove') }}">&times;</button>
                                    </span>
                                @endforeach
                            </div>
                            <input
                                id="tagInputDraft"
                                type="text"
                                class="form-control"
                                autocomplete="off"
                                list="upload-tag-suggestions"
                                wire:model.live="tagDraft"
                                wire:keydown.enter.prevent="addTagsFromDraft"
                                onkeydown="if(event.key===','){event.preventDefault();event.stopPropagation();@this.call('addTagsFromDraft');}"
                                placeholder="{{ __('pages.upload.tags_input_placeholder') }}"
                            />
                            <datalist id="upload-tag-suggestions">
                                @foreach($tags as $tag)
                                    <option value="{{ $tag->name }}"></option>
                                @endforeach
                            </datalist>
                            <small class="text-muted">{{ __('pages.upload.tags_input_hint') }}</small>
                        </div>

                        <!-- Physical Location (Hierarchical) -->
                        <div class="col-md-12 mb-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input"
                                       type="checkbox"
                                       id="digitalOnlyCheck"
                                       wire:model.live="currentInfo.digital_only">
                                <label class="form-check-label" for="digitalOnlyCheck">
                                    Document uniquement digital pour l'instant
                                </label>
                            </div>
                            <label class="form-label">
                                {{ __('pages.upload.physical_location') }}
                                @if(!($currentInfo['digital_only'] ?? false))
                                    <span class="text-danger">*</span>
                                @endif
                            </label>
                            
                            <!-- Step 1: Room -->
                            <div class="row g-2 mb-2">
                                <div class="col-md-3">
                                    <label class="form-label small">1. {{ __('pages.upload.room') }}</label>
                                    <select class="form-select form-select-sm" wire:model.live="selectedRoomId"
                                            @if($currentInfo['digital_only'] ?? false) disabled @endif>
                                        <option value="">{{ __('pages.upload.select_room') }}</option>
                                        @foreach($this->rooms as $room)
                                            <option value="{{ $room->id }}">{{ $room->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <!-- Step 2: Row -->
                                <div class="col-md-3">
                                    <label class="form-label small">2. {{ __('pages.upload.row') }}</label>
                                    <select class="form-select form-select-sm" wire:model.live="selectedRowId" 
                                            @if(!$selectedRoomId || ($currentInfo['digital_only'] ?? false)) disabled @endif>
                                        <option value="">{{ __('pages.upload.select_row') }}</option>
                                        @foreach($this->rows as $row)
                                            <option value="{{ $row->id }}">{{ $row->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <!-- Step 3: Shelf -->
                                <div class="col-md-3">
                                    <label class="form-label small">3. {{ __('pages.upload.shelf') }}</label>
                                    <select class="form-select form-select-sm" wire:model.live="selectedShelfId"
                                            @if(!$selectedRowId || ($currentInfo['digital_only'] ?? false)) disabled @endif>
                                        <option value="">{{ __('pages.upload.select_shelf') }}</option>
                                        @foreach($this->shelves as $shelf)
                                            <option value="{{ $shelf->id }}">{{ $shelf->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <!-- Step 4: Box -->
                                <div class="col-md-3">
                                    <label class="form-label small">4. {{ __('pages.upload.box') }}</label>
                            <select class="form-select form-select-sm @error('currentInfo.box_id') is-invalid @enderror" wire:model.live="selectedBoxId"
                                            @if(!$selectedShelfId || ($currentInfo['digital_only'] ?? false)) disabled @endif
                                            @if(!($currentInfo['digital_only'] ?? false)) required @endif>
                                        <option value="">{{ __('pages.upload.select_box') }}</option>
                                        @foreach($this->boxes as $box)
                                            <option value="{{ $box->id }}">{{ $box->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            @error('currentInfo.box_id')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                            @if($currentInfo['digital_only'] ?? false)
                                <small class="text-muted">Aucun emplacement physique requis pour ce document.</small>
                            @else
                                <small class="text-muted">{{ __('pages.upload.location_structure_hint') }}</small>
                            @endif
                        </div>

                        <!-- Author (Read-Only) -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('pages.upload.author') }}</label>
                            <input type="text" class="form-control" 
                                   value="{{ $currentInfo['author'] ?? '' }}" 
                                   readonly 
                                   style="background-color: #f8f9fa; cursor: not-allowed;"
                            />
                        </div>


                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-center gap-2 mt-2">
                            @php
                                $user = auth()->user();
                                // Use the departments collection coming from Livewire (pivot-based and
                                // already bypassing global scopes) instead of reloading here.
                                $userDepartmentsForCheck = $userDepartments ?? collect();
                                $isPrivileged = $user && $user->can('manage document global expiry');
                                $canProceed = ($canProceedUpload ?? false) || $isPrivileged || $userDepartmentsForCheck->count() > 0;
                            @endphp

                            @if($multiUpload && $useSharedMetadata)
                                {{-- Multiple files with shared metadata: one form applies to all --}}
                                <button type="button" class="btn btn-outline-secondary" wire:click="prevStep">&lt; {{ __('pages.upload.back') }}</button>
                                <button type="button" 
                                        class="btn btn-danger" 
                                        wire:click="submit"
                                        wire:loading.attr="disabled"
                                        wire:loading.class="opacity-75"
                                        @if(!$canProceed || $isSubmitting) disabled title="{{ __('pages.upload.must_assigned_to_submit') }}" @endif>
                                    <span wire:loading.remove wire:target="submit">{{ __('pages.upload.submit') }}</span>
                                    <span wire:loading wire:target="submit">
                                        <i class="fas fa-spinner fa-spin"></i> {{ __('pages.upload.submitting') }}
                                    </span>
                                </button>
                            @else
                                {{-- Per-file metadata (single file or multi with separate metadata) --}}
                                @if($currentDocumentIndex === 0)
                                    <button type="button" class="btn btn-outline-secondary" wire:click="prevStep">&lt; {{ __('pages.upload.back') }}</button>
                                @else
                                    <button type="button" class="btn btn-outline-secondary" wire:click="prevDocument">&lt; {{ __('pages.upload.back') }}</button>
                                @endif

                                @if($currentDocumentIndex < count($documentInfos) - 1)
                                    <button type="button" 
                                            class="btn btn-outline-secondary" 
                                            wire:click="nextDocument"
                                            @if(!$canProceed) disabled title="{{ __('pages.upload.must_assigned_to_proceed') }}" @endif>
                                        {{ __('pages.upload.next') }} &gt;
                                    </button>
                                @else
                                    <button type="button" 
                                            class="btn btn-danger" 
                                            wire:click="submit"
                                            wire:loading.attr="disabled"
                                            wire:loading.class="opacity-75"
                                            @if(!$canProceed || $isSubmitting) disabled title="{{ __('pages.upload.must_assigned_to_submit') }}" @endif>
                                        <span wire:loading.remove wire:target="submit">{{ __('pages.upload.submit') }}</span>
                                        <span wire:loading wire:target="submit">
                                            <i class="fas fa-spinner fa-spin"></i> {{ __('pages.upload.submitting') }}
                                        </span>
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>


        {{-- NEW: Batch Duplicate Warning Modal --}}
        @if($showDuplicateModal)
            <div class="modal show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); z-index: 1055;" wire:ignore.self>
                <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header bg-warning bg-opacity-10">
                            <h5 class="modal-title">
                                <i class="fa-solid fa-triangle-exclamation text-warning me-2"></i>
                                {{ __('pages.upload.duplicate_warning_title') }}
                                <span class="badge bg-warning text-dark ms-2">
                                    {{ __('pages.upload.duplicates_of_total', ['current' => count($filesWithDuplicates), 'total' => count($documentInfos)]) }}
                                </span>
                            </h5>
                        </div>

                        <div class="modal-body" style="max-height: 50vh; overflow-y: auto; scroll-behavior: smooth;">
                            <p class="mb-3">
                                <i class="fa-solid fa-info-circle me-1"></i>
                                {{ __('pages.upload.batch_duplicate_warning_intro') }}
                            </p>
                            
                            {{-- Scroll indicator hint --}}
                            @if(count($filesWithDuplicates) > 2)
                                <div class="alert alert-info alert-sm py-2 mb-3">
                                    <i class="fa-solid fa-arrows-up-down me-1"></i>
                                    <small>{{ __('pages.upload.duplicate_scroll_hint', ['count' => count($filesWithDuplicates)]) }}</small>
                                </div>
                            @endif
                            
                            {{-- Loop through all files with duplicates --}}
                            @foreach($filesWithDuplicates as $fileIndex)
                                @php
                                    $fileInfo = $documentInfos[$fileIndex] ?? [];
                                    $fileDuplicates = $allDuplicates[$fileIndex] ?? [];
                                    $fileName = $fileInfo['title'] ?? __('pages.upload.unknown_file');
                                @endphp
                                
                                <div class="card mb-3 border-warning">
                                    <div class="card-header bg-light d-flex align-items-center">
                                        <i class="fa-solid fa-file text-warning me-2"></i>
                                        <strong>{{ $fileName }}</strong>
                                        <span class="badge bg-secondary ms-auto">{{ __('pages.upload.duplicate_file_label', ['num' => $fileIndex + 1]) }}</span>
                                    </div>
                                    <div class="card-body">
                                        <p class="small text-muted mb-2">
                                            <i class="fa-solid fa-calendar me-1"></i>
                                            {{ \Carbon\Carbon::parse($fileInfo['created_at'] ?? now())->format('Y-m-d') }}
                                        </p>
                                        <p class="mb-2 fw-semibold small">{{ __('pages.upload.existing_documents') }}</p>
                                        <ul class="list-group list-group-flush">
                                            @foreach($fileDuplicates as $dup)
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                                    <span class="small">{{ $dup['title'] }}</span>
                                                    <a href="{{ $dup['url'] }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="fa-solid fa-eye me-1"></i> {{ __('actions.view') }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" wire:click="reviewAndModify">
                                <i class="fa-solid fa-pen-to-square me-1"></i> {{ __('pages.upload.review_and_modify') }}
                            </button>
                            <button type="button" class="btn btn-outline-danger" wire:click="skipAllWithDuplicates">
                                <i class="fa-solid fa-times me-1"></i> {{ __('pages.upload.skip_all_duplicates') }}
                            </button>
                            <button type="button" class="btn btn-success" wire:click="uploadAllAnyway">
                                <i class="fa-solid fa-check me-1"></i> {{ __('pages.upload.upload_all_anyway') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif


    </div>

    @push('styles')
    <style>
        @keyframes highlight-reset {
            0% { background-color: #fff3cd; }
            50% { background-color: #ffe69c; }
            100% { background-color: #ffffff; }
        }
        .subcategory-highlight {
            animation: highlight-reset 1s ease-in-out;
        }
    </style>
    @endpush

    @push('scripts')
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('categories-reset', () => {
                // Small delay to ensure DOM is updated
                setTimeout(() => {
                    const subcategorySelect = document.getElementById('subcategorySelect');
                    if (subcategorySelect) {
                        subcategorySelect.classList.add('subcategory-highlight');
                        setTimeout(() => {
                            subcategorySelect.classList.remove('subcategory-highlight');
                        }, 1000);
                    }
                }, 50);
            });
        });
    </script>
    @endpush
