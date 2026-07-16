@extends('layouts.app')

@section('content')
    <div class="addlocation w-75 mt-5 position-relative">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>{{ __('pages.physical.title') }}</h3>
            @can('viewAny', \App\Models\PhysicalLocation::class)
                <a href="{{ route('physical-locations.export') }}" class="btn btn-outline-dark">{{ __('pages.physical.export_report') }}</a>
            @endcan
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="text-muted small">{{ __('pages.physical.kpi.rooms') }}</div>
                        <div class="fs-5 fw-bold">{{ $kpis['rooms'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="text-muted small">{{ __('pages.physical.kpi.boxes') }}</div>
                        <div class="fs-5 fw-bold">{{ $kpis['boxes'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="text-muted small">{{ __('pages.physical.kpi.documents') }}</div>
                        <div class="fs-5 fw-bold">{{ $kpis['documents'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="text-muted small">{{ __('pages.physical.kpi.borrowed') }}</div>
                        <div class="fs-5 fw-bold text-warning">{{ $kpis['borrowed'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="text-muted small">{{ __('pages.physical.kpi.overdue') }}</div>
                        <div class="fs-5 fw-bold text-danger">{{ $kpis['overdue'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-12 col-md-4">
                        <label class="form-label small text-muted mb-1">{{ __('pages.physical.filter.search_label') }}</label>
                        <input type="text" id="locationFilterSearch" class="form-control form-control-sm" placeholder="{{ __('pages.physical.filter.search_placeholder') }}">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small text-muted mb-1">{{ __('pages.physical.filter.room') }}</label>
                        <select id="locationFilterRoom" class="form-select form-select-sm">
                            <option value="all">{{ __('pages.physical.filter.all_rooms') }}</option>
                            @foreach($rooms as $room)
                                <option value="{{ $room->id }}">{{ $room->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small text-muted mb-1">{{ __('pages.physical.filter.box_status') }}</label>
                        <select id="locationFilterStatus" class="form-select form-select-sm">
                            <option value="all">{{ __('pages.physical.filter.all_statuses') }}</option>
                            <option value="available">{{ __('pages.physical.status.available') }}</option>
                            <option value="borrowed">{{ __('pages.physical.status.borrowed') }}</option>
                            <option value="overdue">{{ __('pages.physical.status.overdue') }}</option>
                            <option value="empty">{{ __('pages.physical.status.empty') }}</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2 d-flex align-items-end">
                        <button id="locationFilterReset" type="button" class="btn btn-sm btn-outline-secondary w-100">{{ __('pages.physical.filter.reset') }}</button>
                    </div>
                </div>
            </div>
        </div>



        @can('create', \App\Models\PhysicalLocation::class)
            @unless(request('view_only'))
                {{-- Quick Create Form hidden as per requirements
            <div class="card mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-plus-circle"></i> {{ __('pages.physical.quick_create') }}</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">{{ __('pages.physical.quick_create_help') }}</p>
                    <form method="post" action="{{ route('physical-locations.store') }}">
                        @csrf
                        <!-- original quick-create fields removed -->
                    </form>
                </div>
            </div>
            --}}

            <!-- Add Individual Items -->
            <div class="row g-3 mb-4">
                <!-- Add New Room (first card) -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0"><i class="fas fa-plus"></i> {{ __('pages.physical.actions.add_new_room') }}</h6>
                        </div>
                        <div class="card-body">
                            <form method="post" action="{{ route('physical-locations.add-room') }}">
                                @csrf
                                <div class="mb-3">
                                    <label for="add_room_name" class="form-label">{{ __('pages.physical.actions.room_name') }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="add_room_name" name="name" 
                                           placeholder="{{ __('pages.physical.placeholders.room_example') }}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="add_room_description" class="form-label">{{ __('pages.physical.fields.description_optional') }}</label>
                                    <textarea class="form-control" id="add_room_description" name="description" rows="2"></textarea>
                                </div>
                                <button type="submit" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-plus"></i> {{ __('pages.physical.actions.add_room') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Add Row to Room (second card) -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="fas fa-plus"></i> {{ __('pages.physical.actions.add_row_to_room') }}</h6>
                        </div>
                        <div class="card-body">
                            <form method="post" action="{{ route('physical-locations.add-row') }}">
                                @csrf
                                <div class="mb-3">
                                    <label for="add_row_room_id" class="form-label">{{ __('pages.physical.actions.select_room') }} <span class="text-danger">*</span></label>
                                    <select class="form-select" id="add_row_room_id" name="room_id" required>
                                        <option value="">{{ __('pages.physical.selects.select_room') }}</option>
                                        @foreach($rooms as $room)
                                            <option value="{{ $room->id }}">{{ $room->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="add_row_name" class="form-label">{{ __('pages.physical.actions.row_name') }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="add_row_name" name="name" 
                                           placeholder="{{ __('pages.physical.placeholders.row_example') }}" required>
                                </div>
                                <div class="mb-3">
                                                <label for="add_row_description" class="form-label">{{ __('pages.physical.fields.description_optional') }}</label>
                                    <textarea class="form-control" id="add_row_description" name="description" rows="2"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> {{ __('pages.physical.actions.add_row') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Add Shelf to Row -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-plus"></i> {{ __('pages.physical.actions.add_shelf_to_row') }}</h6>
                        </div>
                        <div class="card-body">
                            <form method="post" action="{{ route('physical-locations.add-shelf') }}">
                                @csrf
                                <div class="mb-3">
                                    <label for="add_shelf_room_id" class="form-label">{{ __('pages.physical.actions.select_room') }}</label>
                                    <select class="form-select" id="add_shelf_room_id" name="room_id">
                                        <option value="">{{ __('pages.physical.selects.select_room') }}</option>
                                        @foreach($rooms as $room)
                                            <option value="{{ $room->id }}">{{ $room->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="add_shelf_row_id" class="form-label">{{ __('pages.physical.actions.select_row') }} <span class="text-danger">*</span></label>
                                    <select class="form-select" id="add_shelf_row_id" name="row_id" required>
                                        <option value="">{{ __('pages.physical.selects.first_select_room') }}</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="add_shelf_name" class="form-label">{{ __('pages.physical.actions.shelf_name') }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="add_shelf_name" name="name" 
                                           placeholder="{{ __('pages.physical.placeholders.shelf_example') }}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="add_shelf_description" class="form-label">{{ __('pages.physical.fields.description_optional') }}</label>
                                    <textarea class="form-control" id="add_shelf_description" name="description" rows="2"></textarea>
                                </div>
                                <button type="submit" class="btn btn-info btn-sm">
                                    <i class="fas fa-plus"></i> {{ __('pages.physical.actions.add_shelf') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Add Box to Shelf (fourth card, second row right) -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0"><i class="fas fa-plus"></i> {{ __('pages.physical.actions.add_box_to_shelf') }}</h6>
                        </div>
                        <div class="card-body">
                            <form method="post" action="{{ route('physical-locations.add-box') }}">
                                @csrf
                                
                                {{-- Service (optional): checkbox disables select --}}
                                <div class="mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input js-box-no-service" type="checkbox" id="add_box_no_service"
                                               data-target-select="add_box_service_id">
                                        <label class="form-check-label" for="add_box_no_service">{{ __('pages.physical.service_optional_checkbox') }}</label>
                                    </div>
                                    <small class="text-muted d-block">{{ __('pages.physical.service_optional_help') }}</small>
                                </div>
                                <div class="mb-3">
                                    <label for="add_box_service_id" class="form-label">{{ __('pages.physical.fields.service') }}</label>
                                    <select class="form-select" id="add_box_service_id" name="service_id">
                                        <option value="">{{ __('pages.physical.select_service_optional') }}</option>
                                        @php
                                            $user = auth()->user();
                                            $accessibleServiceIds = \App\Models\Box::getAccessibleServiceIds($user);
                                            
                                            // Get accessible services
                                            if ($accessibleServiceIds === 'all') {
                                                $services = \App\Models\Service::orderBy('name')->get();
                                            } else {
                                                $services = \App\Models\Service::whereIn('id', $accessibleServiceIds)->orderBy('name')->get();
                                            }
                                        @endphp
                                        @foreach($services as $service)
                                            <option value="{{ $service->id }}">{{ $service->name }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">{{ __('pages.physical.service_link_help') }}</small>
                                </div>

                                <div class="mb-3">
                                    <label for="add_box_room_id" class="form-label">{{ __('pages.physical.actions.select_room') }}</label>
                                    <select class="form-select" id="add_box_room_id" name="room_id">
                                        <option value="">{{ __('pages.physical.selects.select_room') }}</option>
                                        @foreach($rooms as $room)
                                            <option value="{{ $room->id }}">{{ $room->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="add_box_row_id" class="form-label">{{ __('pages.physical.actions.select_row') }}</label>
                                    <select class="form-select" id="add_box_row_id" name="row_id">
                                        <option value="">{{ __('pages.physical.selects.first_select_room') }}</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="add_box_shelf_id" class="form-label">{{ __('pages.physical.selects.select_shelf') }} <span class="text-danger">*</span></label>
                                    <select class="form-select" id="add_box_shelf_id" name="shelf_id" required>
                                        <option value="">{{ __('pages.physical.selects.first_select_row') }}</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="add_box_name" class="form-label">{{ __('pages.physical.actions.box_name') }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="add_box_name" name="name" 
                                           placeholder="{{ __('pages.physical.placeholders.box_example') }}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="add_box_number" class="form-label">Numéro de boîte</label>
                                    <input type="text" class="form-control" id="add_box_number" name="box_number"
                                           placeholder="ex. BOX-001">
                                </div>
                                <div class="mb-3">
                                    <label for="add_box_description" class="form-label">{{ __('pages.physical.fields.description_optional') }}</label>
                                    <textarea class="form-control" id="add_box_description" name="description" rows="2"></textarea>
                                </div>
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="fas fa-plus"></i> {{ __('pages.physical.actions.add_box') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endunless

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-dark text-white">
                            <h6 class="mb-0"><i class="fas fa-layer-group me-1"></i> {{ __('pages.physical.bulk_range_title') }}</h6>
                        </div>
                        <div class="card-body">
                            <form method="post" action="{{ route('physical-locations.bulk-add-boxes') }}">
                                @csrf
                                <div class="mb-2">
                                    <div class="form-check mb-1">
                                        <input class="form-check-input js-box-no-service" type="checkbox" id="bulk_box_no_service"
                                               data-target-select="bulk_box_service_id">
                                        <label class="form-check-label small" for="bulk_box_no_service">{{ __('pages.physical.service_optional_checkbox') }}</label>
                                    </div>
                                    <label class="form-label">{{ __('pages.physical.fields.service') }}</label>
                                    <select class="form-select form-select-sm" id="bulk_box_service_id" name="service_id">
                                        <option value="">{{ __('pages.physical.select_service_optional') }}</option>
                                        @php
                                            $user = auth()->user();
                                            $accessibleServiceIds = \App\Models\Box::getAccessibleServiceIds($user);
                                            if ($accessibleServiceIds === 'all') {
                                                $bulkServices = \App\Models\Service::orderBy('name')->get();
                                            } else {
                                                $bulkServices = \App\Models\Service::whereIn('id', $accessibleServiceIds)->orderBy('name')->get();
                                            }
                                        @endphp
                                        @foreach($bulkServices as $service)
                                            <option value="{{ $service->id }}">{{ $service->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="row g-2">
                                    <div class="col-12 col-md-4">
                                        <label class="form-label">{{ __('pages.physical.filter.room') }}</label>
                                        <select class="form-select form-select-sm" id="bulk_box_room_id">
                                            <option value="">{{ __('pages.physical.bulk.select') }}</option>
                                            @foreach($rooms as $room)
                                                <option value="{{ $room->id }}">{{ $room->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label">{{ __('pages.physical.fields.row') }}</label>
                                        <select class="form-select form-select-sm" id="bulk_box_row_id">
                                            <option value="">{{ __('pages.physical.bulk.first_pick_room') }}</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label">{{ __('pages.physical.fields.shelf') }}</label>
                                        <select class="form-select form-select-sm" id="bulk_box_shelf_id" name="shelf_id" required>
                                            <option value="">{{ __('pages.physical.bulk.first_pick_row') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-2 mt-1">
                                    <div class="col-6 col-md-4">
                                        <label class="form-label">{{ __('pages.physical.bulk.prefix') }}</label>
                                        <input type="text" name="prefix" class="form-control form-control-sm" placeholder="ARCH" value="BOX">
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <label class="form-label">{{ __('pages.physical.bulk.from') }}</label>
                                        <input type="number" min="0" name="start_number" class="form-control form-control-sm" value="1" required>
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <label class="form-label">{{ __('pages.physical.bulk.to') }}</label>
                                        <input type="number" min="0" name="end_number" class="form-control form-control-sm" value="100" required>
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <label class="form-label">{{ __('pages.physical.bulk.padding') }}</label>
                                        <input type="number" min="1" max="8" name="padding" class="form-control form-control-sm" value="3">
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <label class="form-label">{{ __('pages.physical.bulk.separator') }}</label>
                                        <input type="text" name="separator" class="form-control form-control-sm" value="-">
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-2">{{ __('pages.physical.bulk.naming_help') }}</small>
                                <div class="mt-3 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-sm btn-dark">{{ __('pages.physical.bulk.create_range') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0"><i class="fas fa-file-csv me-1"></i> {{ __('pages.physical.csv_import_title') }}</h6>
                        </div>
                        <div class="card-body">
                            <form method="post" action="{{ route('physical-locations.import-boxes.preview') }}" enctype="multipart/form-data" class="mb-3">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label">{{ __('pages.physical.csv_file') }}</label>
                                    <input type="file" name="csv_file" class="form-control form-control-sm" accept=".csv,.txt" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">{{ __('pages.physical.csv_default_service') }}</label>
                                    <select class="form-select form-select-sm" name="default_service_id">
                                        <option value="">{{ __('pages.physical.csv_none') }}</option>
                                        @foreach($bulkServices as $service)
                                            <option value="{{ $service->id }}">{{ $service->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="csvHasHeader" name="has_header" value="1" checked>
                                    <label class="form-check-label" for="csvHasHeader">{{ __('pages.physical.csv_has_header') }}</label>
                                </div>
                                <small class="text-muted d-block mb-2">{{ __('pages.physical.csv_columns_hint') }}</small>
                                <button type="submit" class="btn btn-sm btn-secondary">{{ __('pages.physical.csv_preview_generate') }}</button>
                            </form>

                            @if(!empty($boxImportPreview))
                                <div class="border rounded-3 p-2">
                                    <div class="small mb-1">
                                        <strong>{{ __('pages.physical.csv_preview_label') }}</strong>:
                                        {{ __('pages.physical.csv_line_stats', ['ok' => $boxImportPreview['count_rows'] ?? 0, 'ko' => $boxImportPreview['count_errors'] ?? 0]) }}
                                    </div>
                                    @if(!empty($boxImportPreview['errors']))
                                        <ul class="small text-danger mb-2">
                                            @foreach(array_slice($boxImportPreview['errors'], 0, 5) as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    @if(!empty($boxImportPreview['rows']))
                                        <div class="table-responsive mb-2" style="max-height: 180px;">
                                            <table class="table table-sm mb-0">
                                                <thead>
                                                <tr>
                                                    <th>{{ __('pages.physical.csv_th_line') }}</th>
                                                    <th>{{ __('pages.physical.csv_th_path') }}</th>
                                                    <th>{{ __('pages.physical.csv_th_box') }}</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach(array_slice($boxImportPreview['rows'], 0, 20) as $r)
                                                    <tr>
                                                        <td>{{ $r['line'] }}</td>
                                                        <td>{{ $r['room_name'] }} → {{ $r['row_name'] }} → {{ $r['shelf_name'] }}</td>
                                                        <td>{{ $r['box_name'] }}</td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                    <form method="post" action="{{ route('physical-locations.import-boxes.commit') }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" {{ !empty($boxImportPreview['count_errors']) ? 'disabled' : '' }}>
                                            {{ __('pages.physical.csv_validate_import') }}
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endcan

        <!-- Hierarchical Tree View -->
        <div class="mt-4">
            <h5 class="mb-3">{{ __('pages.physical.actions.location_structure') }}</h5>
            
            @if(isset($rooms) && $rooms->count() > 0)
                <div class="accordion" id="roomsAccordion">
                @foreach($rooms as $room)
                    <div class="card mb-3 location-room-card" data-room-id="{{ $room->id }}" data-filter-text="{{ strtolower($room->name.' '.$room->description) }}">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <button class="btn btn-link text-white text-decoration-none p-0 fw-semibold d-flex align-items-center gap-2" type="button" data-bs-toggle="collapse" data-bs-target="#roomCollapse{{ $room->id }}" aria-expanded="false" aria-controls="roomCollapse{{ $room->id }}">
                                <span>📍 {{ __('pages.physical.fields.room') }}: {{ $room->name }}</span>
                                <span class="badge bg-light text-dark">{{ $room->rows->count() }} {{ __('pages.physical.fields.row') }}(s)</span>
                            </button>
                            <div class="d-flex align-items-center">
                                @if(auth()->user()->can('view any role') || auth()->user()->can('view organization wide reports'))
                                    <form method="POST" action="{{ route('physical-locations.destroy-room', $room->id) }}" 
                                          class="d-inline" onsubmit="return confirm('{{ __('pages.activity_log.are_you_sure') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="{{ __('pages.physical.actions.delete') }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                        <div id="roomCollapse{{ $room->id }}" class="collapse location-room-collapse" data-bs-parent="#roomsAccordion">
                        <div class="card-body">
                            @if($room->description)
                                <small class="text-muted d-block mb-2">{{ $room->description }}</small>
                            @endif
                            @if($room->rows->count() > 0)
                                @foreach($room->rows as $row)
                                    <div class="ms-3 mb-3 border-start border-3 border-secondary ps-3 location-row-wrap" data-filter-text="{{ strtolower($row->name.' '.$row->description) }}">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <button class="btn btn-link text-decoration-none p-0 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#rowCollapse{{ $row->id }}" aria-expanded="false" aria-controls="rowCollapse{{ $row->id }}">
                                                    📐 {{ __('pages.physical.fields.row') }}: {{ $row->name }}
                                                </button>
                                                @if($row->description)
                                                    <small class="text-muted d-block">{{ $row->description }}</small>
                                                @endif
                                            </div>
                                            <span class="badge bg-secondary">{{ $row->shelves->count() }} {{ __('pages.physical.fields.shelf') }}(s)</span>
                                        </div>
                                        <div id="rowCollapse{{ $row->id }}" class="collapse location-row-collapse">
                                        @if($row->shelves->count() > 0)
                                            @foreach($row->shelves as $shelf)
                                                <div class="ms-3 mt-2 mb-2 border-start border-2 border-info ps-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <div>
                                                            <strong>📚 {{ __('pages.physical.fields.shelf') }}: {{ $shelf->name }}</strong>
                                                            @if($shelf->description)
                                                                <small class="text-muted d-block">{{ $shelf->description }}</small>
                                                            @endif
                                                        </div>
                                                        <span class="badge bg-info text-dark">{{ $shelf->boxes->count() }} {{ __('pages.physical.fields.box') }}(es)</span>
                                                    </div>
                                                    
                                                    <ul class="list-unstyled ms-3 mt-2">
                                                            @foreach($shelf->boxes as $box)
                                                                @php
                                                                    $boxDocuments = $box->documents;
                                                                    $boxOpenLoans = $boxDocuments->map(function ($doc) use ($openLoans) {
                                                                        return $openLoans->get($doc->id);
                                                                    })->filter();
                                                                    $hasOverdue = $boxOpenLoans->contains(fn ($loan) => $loan->due_at && $loan->due_at->isPast());
                                                                    $boxStatus = 'available';
                                                                    if ($boxDocuments->count() === 0) {
                                                                        $boxStatus = 'empty';
                                                                    } elseif ($hasOverdue) {
                                                                        $boxStatus = 'overdue';
                                                                    } elseif ($boxOpenLoans->isNotEmpty()) {
                                                                        $boxStatus = 'borrowed';
                                                                    }
                                                                    $boxStatusLabel = match ($boxStatus) {
                                                                        'empty' => __('pages.physical.status.empty'),
                                                                        'overdue' => __('pages.physical.status.overdue'),
                                                                        'borrowed' => __('pages.physical.status.borrowed'),
                                                                        default => __('pages.physical.status.available'),
                                                                    };
                                                                    $boxStatusClass = match ($boxStatus) {
                                                                        'empty' => 'bg-secondary-subtle text-secondary-emphasis',
                                                                        'overdue' => 'bg-danger-subtle text-danger-emphasis',
                                                                        'borrowed' => 'bg-warning-subtle text-warning-emphasis',
                                                                        default => 'bg-success-subtle text-success-emphasis',
                                                                    };
                                                                @endphp
                                                                <li class="mb-2 p-2 bg-light rounded d-flex justify-content-between align-items-start location-box-item"
                                                                    data-filter-status="{{ $boxStatus }}"
                                                                    data-filter-text="{{ strtolower($box->name.' '.$box->description.' '.$box->__toString().' '.$boxDocuments->pluck('title')->implode(' ')) }}">
                                                                    <div class="me-2">
                                                                        <strong>📦 {{ $box->name }}</strong>
                                                                        @if(!$box->service_id)
                                                                            <span class="badge bg-secondary ms-1">{{ __('pages.physical.service_shared_badge') }}</span>
                                                                        @endif
                                                                        <span class="badge rounded-pill {{ $boxStatusClass }} ms-2">{{ $boxStatusLabel }}</span>
                                                                        @if($box->description)
                                                                            <small class="text-muted d-block">{{ $box->description }}</small>
                                                                        @endif
                                                                        <small class="text-muted">
                                                                            {{ __('pages.physical.actions.full_path') }} <code>{{ $box->__toString() }}</code>
                                                                        </small>
                                                                        @if($box->documents->count() > 0)
                                                                            <a href="{{ route('documents.all', ['box_id' => $box->id]) }}" 
                                                                               class="badge bg-secondary ms-2 text-decoration-none"
                                                                               data-bs-toggle="tooltip" 
                                                                               data-bs-html="true"
                                                                               data-bs-placement="top"
                                                                               title="<div class='text-start'><strong>{{ __('Click to view documents') }}</strong><ul class='list-unstyled mb-0 mt-1'>@foreach($box->documents->take(5) as $doc)<li>• {{ $doc->title }}</li>@endforeach @if($box->documents->count() > 5)<li class='text-muted fst-italic'>{{ __('And :count more...', ['count' => $box->documents->count() - 5]) }}</li>@endif</ul></div>">
                                                                                {{ $box->documents->count() }} {{ __('file(s)') }}
                                                                            </a>
                                                                        @else
                                                                            <span class="badge bg-secondary ms-2">0 {{ __('file(s)') }}</span>
                                                                        @endif

                                                                        @if($boxDocuments->isNotEmpty())
                                                                            <div class="mt-2 d-flex flex-column gap-1">
                                                                                @foreach($boxDocuments->take(3) as $doc)
                                                                                    @php
                                                                                        $docLoan = $openLoans->get($doc->id);
                                                                                    @endphp
                                                                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                                                                        <span class="small text-muted text-truncate" style="max-width: 240px;" title="{{ $doc->title }}">{{ $doc->title }}</span>
                                                                                        @if($docLoan)
                                                                                            <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis">{{ __('pages.physical.loan_active') }}</span>
                                                                                            @can('create', \App\Models\DocumentMovement::class)
                                                                                                <form method="POST" action="{{ route('documents.return', $doc) }}" class="d-inline">
                                                                                                    @csrf
                                                                                                    <button type="submit" class="btn btn-xs btn-outline-success px-2 py-0">{{ __('pages.physical.return_document') }}</button>
                                                                                                </form>
                                                                                            @endcan
                                                                                        @else
                                                                                            @can('create', \App\Models\DocumentMovement::class)
                                                                                                <form method="POST" action="{{ route('documents.borrow', $doc) }}" class="d-inline">
                                                                                                    @csrf
                                                                                                    <input type="hidden" name="borrower_name" value="{{ auth()->user()->full_name }}">
                                                                                                    <button type="submit" class="btn btn-xs btn-outline-warning px-2 py-0">{{ __('pages.physical.borrow_document') }}</button>
                                                                                                </form>
                                                                                            @endcan
                                                                                        @endif
                                                                                    </div>
                                                                                @endforeach
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                    <div class="d-inline-flex gap-1">
                                                                        @can('create', \App\Models\PhysicalLocation::class)
                                                                            <button class="btn btn-sm btn-outline-primary" type="button" 
                                                                                    data-bs-toggle="modal" data-bs-target="#editBoxModal{{ $box->id }}">
                                                                                <i class="fas fa-edit"></i> {{ __('pages.physical.actions.edit') }}
                                                                            </button>
                                                                        @endcan
                                                                        @can('delete physical location')
                                                                            <form method="POST" action="{{ route('physical-locations.destroy-box', $box->id) }}" 
                                                                                  class="d-inline" onsubmit="return confirm('{{ __('pages.activity_log.are_you_sure') }}');">
                                                                                @csrf
                                                                                @method('DELETE')
                                                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                                    <i class="fas fa-trash"></i> {{ __('pages.physical.actions.delete') }}
                                                                                </button>
                                                                            </form>
                                                                        @endcan
                                                                    </div>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                </div>
                                            @endforeach
                                        @else
                                            <p class="text-muted small ms-3"><em>{{ __('pages.physical.actions.no_shelves') }}</em></p>
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                <p class="text-muted ms-3"><em>{{ __('pages.physical.actions.no_rows') }}</em></p>
                            @endif
                        </div>
                        </div>
                    </div>
                @endforeach
                </div>
            @else
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    <strong>{{ __('pages.physical.actions.no_locations') }}</strong> {{ __('pages.physical.actions.use_forms') }}
                </div>
            @endif
        </div>
    </div>
    @include('components.modals.confirm-modal')

    <!-- Edit Box Modal -->
    @if(isset($rooms))
        @foreach($rooms as $room)
            @foreach($room->rows as $row)
                @foreach($row->shelves as $shelf)
                    @foreach($shelf->boxes as $box)
                        <div class="modal fade" id="editBoxModal{{ $box->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('physical-locations.update-box', $box->id) }}">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">{{ __('pages.physical.actions.edit') }} {{ __('pages.physical.fields.box') }}: {{ $box->name }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="edit_room_name_{{ $box->id }}" class="form-label">{{ __('pages.physical.fields.room') }}</label>
                                                <input type="text" class="form-control edit-box-room-name"
                                                       id="edit_room_name_{{ $box->id }}"
                                                       data-box-id="{{ $box->id }}"
                                                       name="room_name"
                                                       value="{{ $room->name }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_row_name_{{ $box->id }}" class="form-label">{{ __('pages.physical.fields.row') }}</label>
                                                <input type="text" class="form-control edit-box-row-name"
                                                       id="edit_row_name_{{ $box->id }}"
                                                       data-box-id="{{ $box->id }}"
                                                       name="row_name"
                                                       value="{{ $row->name }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_shelf_name_{{ $box->id }}" class="form-label">{{ __('pages.physical.fields.shelf') }}</label>
                                                <input type="text" class="form-control edit-box-shelf-name"
                                                       id="edit_shelf_name_{{ $box->id }}"
                                                       data-box-id="{{ $box->id }}"
                                                       name="shelf_name"
                                                       value="{{ $shelf->name }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="box_name_edit{{ $box->id }}" class="form-label">{{ __('pages.physical.actions.box_name') }}</label>
                                                <input type="text" class="form-control" id="box_name_edit{{ $box->id }}" 
                                                       name="name" value="{{ $box->name }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="box_number_edit{{ $box->id }}" class="form-label">Numéro de boîte</label>
                                                <input type="text" class="form-control" id="box_number_edit{{ $box->id }}"
                                                       name="box_number" value="{{ $box->box_number }}" placeholder="ex. BOX-001">
                                            </div>

                                            {{-- Service (optional) --}}
                                            <div class="mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input js-box-no-service" type="checkbox"
                                                           id="edit_no_service_{{ $box->id }}"
                                                           data-target-select="edit_service_id_{{ $box->id }}"
                                                        {{ $box->service_id ? '' : 'checked' }}>
                                                    <label class="form-check-label" for="edit_no_service_{{ $box->id }}">{{ __('pages.physical.service_optional_checkbox') }}</label>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_service_id_{{ $box->id }}" class="form-label">{{ __('pages.physical.fields.service') }}</label>
                                                <select class="form-select" id="edit_service_id_{{ $box->id }}" name="service_id">
                                                    <option value="">{{ __('pages.physical.select_service_optional') }}</option>
                                                    @php
                                                        $user = auth()->user();
                                                        $accessibleServiceIds = \App\Models\Box::getAccessibleServiceIds($user);
                                                        
                                                        // Get accessible services
                                                        if ($accessibleServiceIds === 'all') {
                                                            $editServices = \App\Models\Service::orderBy('name')->get();
                                                        } else {
                                                            $editServices = \App\Models\Service::whereIn('id', $accessibleServiceIds)->orderBy('name')->get();
                                                        }
                                                    @endphp
                                                    @foreach($editServices as $service)
                                                        <option value="{{ $service->id }}" {{ (int) $box->service_id === (int) $service->id ? 'selected' : '' }}>
                                                            {{ $service->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label for="box_description_edit{{ $box->id }}" class="form-label">{{ __('pages.physical.fields.description_optional') }}</label>
                                                <textarea class="form-control" id="box_description_edit{{ $box->id }}" 
                                                          name="description" rows="2">{{ $box->description }}</textarea>
                                            </div>
                                            <p class="text-muted small">
                                                <strong>{{ __('pages.physical.actions.full_path') }}</strong>
                                                <span id="edit_box_full_path_{{ $box->id }}">{{ $box->__toString() }}</span>
                                            </p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('pages.physical.actions.cancel') }}</button>
                                            <button type="submit" class="btn btn-primary">{{ __('pages.physical.actions.update') }}</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endforeach
            @endforeach
        @endforeach
    @endif

    <script>
        const rooms = @json($rooms);
        const translations = {
            selectRow: @json(__('pages.physical.selects.select_row')),
            firstSelectRow: @json(__('pages.physical.selects.first_select_row')),
            selectShelf: @json(__('pages.physical.selects.select_shelf')),
            firstSelectShelf: @json(__('pages.physical.selects.first_select_shelf')),
        };

        // Helper to update full path text inside edit modal
        function updateEditBoxFullPath(boxId) {
            const roomInput = document.getElementById('edit_room_name_' + boxId);
            const rowInput = document.getElementById('edit_row_name_' + boxId);
            const shelfInput = document.getElementById('edit_shelf_name_' + boxId);
            const boxNameInput = document.getElementById('box_name_edit' + boxId);
            const pathSpan = document.getElementById('edit_box_full_path_' + boxId);
            if (!roomInput || !rowInput || !shelfInput || !boxNameInput || !pathSpan) return;

            const parts = [];
            const roomText = roomInput.value.trim();
            const rowText = rowInput.value.trim();
            const shelfText = shelfInput.value.trim();
            const boxText = boxNameInput.value.trim();
            if (roomText) parts.push(roomText);
            if (rowText) parts.push(rowText);
            if (shelfText) parts.push(shelfText);
            if (boxText) parts.push(boxText);
            // Use arrow separator between path segments
            pathSpan.textContent = parts.join(' · ');
        }

        // Initialize edit box full path updates based on text inputs
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize Bootstrap tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            function syncBoxServiceOptional(checkbox) {
                if (!checkbox || !checkbox.classList.contains('js-box-no-service')) return;
                var sel = document.getElementById(checkbox.dataset.targetSelect);
                if (!sel) return;
                if (checkbox.checked) {
                    sel.value = '';
                    sel.disabled = true;
                } else {
                    sel.disabled = false;
                }
            }
            document.querySelectorAll('.js-box-no-service').forEach(function (cb) {
                cb.addEventListener('change', function () { syncBoxServiceOptional(cb); });
                syncBoxServiceOptional(cb);
            });

            document.querySelectorAll('.edit-box-room-name').forEach(function (roomInput) {
                const boxId = roomInput.dataset.boxId;
                const rowInput = document.getElementById('edit_row_name_' + boxId);
                const shelfInput = document.getElementById('edit_shelf_name_' + boxId);
                const boxNameInput = document.getElementById('box_name_edit' + boxId);

                function attach(el) {
                    if (!el) return;
                    el.addEventListener('input', function () {
                        updateEditBoxFullPath(boxId);
                    });
                }

                attach(roomInput);
                attach(rowInput);
                attach(shelfInput);
                attach(boxNameInput);

                updateEditBoxFullPath(boxId);
            });

            const searchInput = document.getElementById('locationFilterSearch');
            const roomSelect = document.getElementById('locationFilterRoom');
            const statusSelect = document.getElementById('locationFilterStatus');
            const resetButton = document.getElementById('locationFilterReset');

            function applyLocationFilters() {
                const term = (searchInput?.value || '').trim().toLowerCase();
                const roomId = roomSelect?.value || 'all';
                const status = statusSelect?.value || 'all';

                document.querySelectorAll('.location-room-card').forEach(function (roomCard) {
                    const roomMatch = roomId === 'all' || roomCard.dataset.roomId === roomId;
                    let rowMatchCount = 0;

                    roomCard.querySelectorAll('.location-row-wrap').forEach(function (rowWrap) {
                        let boxMatchCount = 0;
                        rowWrap.querySelectorAll('.location-box-item').forEach(function (boxItem) {
                            const text = (boxItem.dataset.filterText || '').toLowerCase();
                            const statusMatch = status === 'all' || boxItem.dataset.filterStatus === status;
                            const textMatch = term === '' || text.includes(term);
                            const showBox = statusMatch && textMatch;
                            boxItem.style.display = showBox ? '' : 'none';
                            if (showBox) {
                                boxMatchCount += 1;
                            }
                        });

                        const rowText = (rowWrap.dataset.filterText || '').toLowerCase();
                        const showRow = boxMatchCount > 0 || (term !== '' && rowText.includes(term) && status === 'all');
                        rowWrap.style.display = showRow ? '' : 'none';
                        if (showRow) {
                            rowMatchCount += 1;
                        }
                    });

                    const roomText = (roomCard.dataset.filterText || '').toLowerCase();
                    const showRoom = roomMatch && (rowMatchCount > 0 || (term !== '' && roomText.includes(term) && status === 'all'));
                    roomCard.style.display = showRoom ? '' : 'none';

                    const roomCollapse = roomCard.querySelector('.location-room-collapse');
                    if (window.bootstrap && roomCollapse) {
                        const instance = window.bootstrap.Collapse.getOrCreateInstance(roomCollapse, { toggle: false });
                        if (showRoom && (term !== '' || status !== 'all' || roomId !== 'all')) {
                            instance.show();
                        } else {
                            instance.hide();
                        }
                    }

                    roomCard.querySelectorAll('.location-row-collapse').forEach(function (rowCollapse) {
                        if (!window.bootstrap) {
                            return;
                        }
                        const rowWrap = rowCollapse.closest('.location-row-wrap');
                        const showRow = rowWrap && rowWrap.style.display !== 'none';
                        const rowInstance = window.bootstrap.Collapse.getOrCreateInstance(rowCollapse, { toggle: false });
                        if (showRow && (term !== '' || status !== 'all')) {
                            rowInstance.show();
                        } else {
                            rowInstance.hide();
                        }
                    });
                });
            }

            searchInput?.addEventListener('input', applyLocationFilters);
            roomSelect?.addEventListener('change', applyLocationFilters);
            statusSelect?.addEventListener('change', applyLocationFilters);
            resetButton?.addEventListener('click', function () {
                if (searchInput) searchInput.value = '';
                if (roomSelect) roomSelect.value = 'all';
                if (statusSelect) statusSelect.value = 'all';
                applyLocationFilters();
            });
        });

        // Add Row to Room - Simple, no cascading needed

        // Add Shelf to Row - Cascade from Room to Row
        document.getElementById('add_shelf_room_id')?.addEventListener('change', function() {
            const roomId = parseInt(this.value);
            const rowSelect = document.getElementById('add_shelf_row_id');
            rowSelect.innerHTML = '<option value="">' + translations.selectRow + '</option>';
            
            if (!roomId) return;
            
            const room = rooms.find(r => r.id === roomId);
            if (room) {
                room.rows.forEach(row => {
                    const option = document.createElement('option');
                    option.value = row.id;
                    option.textContent = row.name;
                    rowSelect.appendChild(option);
                });
            }
        });

        // Add Box to Shelf - Cascade from Room to Row to Shelf
        document.getElementById('add_box_room_id')?.addEventListener('change', function() {
            const roomId = parseInt(this.value);
            const rowSelect = document.getElementById('add_box_row_id');
            const shelfSelect = document.getElementById('add_box_shelf_id');
            
            rowSelect.innerHTML = '<option value="">' + translations.selectRow + '</option>';
            shelfSelect.innerHTML = '<option value="">' + translations.firstSelectRow + '</option>';
            
            if (!roomId) return;
            
            const room = rooms.find(r => r.id === roomId);
            if (room) {
                room.rows.forEach(row => {
                    const option = document.createElement('option');
                    option.value = row.id;
                    option.textContent = row.name;
                    rowSelect.appendChild(option);
                });
            }
        });

        document.getElementById('add_box_row_id')?.addEventListener('change', function() {
            const roomId = parseInt(document.getElementById('add_box_room_id').value);
            const rowId = parseInt(this.value);
            const shelfSelect = document.getElementById('add_box_shelf_id');
            shelfSelect.innerHTML = '<option value="">' + translations.selectShelf + '</option>';
            
            if (!roomId || !rowId) return;
            
            const room = rooms.find(r => r.id === roomId);
            if (room) {
                const row = room.rows.find(r => r.id === rowId);
                if (row) {
                    row.shelves.forEach(shelf => {
                        const option = document.createElement('option');
                        option.value = shelf.id;
                        option.textContent = shelf.name;
                        shelfSelect.appendChild(option);
                    });
                }
            }
        });

        // Bulk create selectors: Room -> Row -> Shelf
        document.getElementById('bulk_box_room_id')?.addEventListener('change', function() {
            const roomId = parseInt(this.value);
            const rowSelect = document.getElementById('bulk_box_row_id');
            const shelfSelect = document.getElementById('bulk_box_shelf_id');
            if (!rowSelect || !shelfSelect) return;

            rowSelect.innerHTML = '<option value="">' + translations.selectRow + '</option>';
            shelfSelect.innerHTML = '<option value="">' + translations.firstSelectRow + '</option>';
            if (!roomId) return;

            const room = rooms.find(r => r.id === roomId);
            if (room) {
                room.rows.forEach(row => {
                    const option = document.createElement('option');
                    option.value = row.id;
                    option.textContent = row.name;
                    rowSelect.appendChild(option);
                });
            }
        });

        document.getElementById('bulk_box_row_id')?.addEventListener('change', function() {
            const roomId = parseInt(document.getElementById('bulk_box_room_id')?.value || '');
            const rowId = parseInt(this.value);
            const shelfSelect = document.getElementById('bulk_box_shelf_id');
            if (!shelfSelect) return;
            shelfSelect.innerHTML = '<option value="">' + translations.selectShelf + '</option>';
            if (!roomId || !rowId) return;

            const room = rooms.find(r => r.id === roomId);
            if (room) {
                const row = room.rows.find(r => r.id === rowId);
                if (row) {
                    row.shelves.forEach(shelf => {
                        const option = document.createElement('option');
                        option.value = shelf.id;
                        option.textContent = shelf.name;
                        shelfSelect.appendChild(option);
                    });
                }
            }
        });
    </script>
@endsection
