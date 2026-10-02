@php
    /** @var \App\Models\Profile|null $profile */
    $isEdit = isset($profile);
@endphp

<div class="mb-3">
    <label for="name" class="form-label">{{ __('Nom') }} <span class="text-danger">*</span></label>
    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
           value="{{ old('name', $profile->name ?? '') }}" required maxlength="255">
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="description" class="form-label">{{ __('Description') }}</label>
    <textarea name="description" id="description" rows="3" class="form-control @error('description') is-invalid @enderror" maxlength="2000">{{ old('description', $profile->description ?? '') }}</textarea>
    @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="category_ids" class="form-label">{{ __('Dossiers') }}</label>
    <select name="category_ids[]" id="category_ids" class="form-select" multiple size="12">
        @foreach($categories as $cat)
            <option value="{{ $cat->id }}"
                {{ in_array((string) $cat->id, old('category_ids', $isEdit ? $profile->categories->pluck('id')->map(fn ($id) => (string) $id)->all() : []), true) ? 'selected' : '' }}>
                {{ $cat->name }} (ID {{ $cat->id }})
            </option>
        @endforeach
    </select>
    <div class="form-text">{{ __('Ctrl/Cmd + clic pour plusieurs choix.') }}</div>
    @error('category_ids')
        <div class="text-danger small">{{ $message }}</div>
    @enderror
    @error('category_ids.*')
        <div class="text-danger small">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="user_ids" class="form-label">{{ __('Utilisateurs') }}</label>
    <select name="user_ids[]" id="user_ids" class="form-select" multiple size="10">
        @foreach($users as $user)
            <option value="{{ $user->id }}"
                {{ in_array((string) $user->id, old('user_ids', $isEdit ? $profile->users->pluck('id')->map(fn ($id) => (string) $id)->all() : []), true) ? 'selected' : '' }}>
                {{ $user->full_name ?: $user->email }} — {{ $user->email }}
            </option>
        @endforeach
    </select>
    @error('user_ids')
        <div class="text-danger small">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="role_ids" class="form-label">{{ __('Types d’utilisateur (rôles)') }}</label>
    <select name="role_ids[]" id="role_ids" class="form-select" multiple size="8">
        @foreach($roles as $role)
            <option value="{{ $role->id }}"
                {{ in_array((string) $role->id, old('role_ids', $isEdit ? $profile->roles->pluck('id')->map(fn ($id) => (string) $id)->all() : []), true) ? 'selected' : '' }}>
                {{ $role->name }}
            </option>
        @endforeach
    </select>
    <div class="form-text">{{ __('Exemple : tous les utilisateurs ayant le rôle Chef de Pôle.') }}</div>
    @error('role_ids')
        <div class="text-danger small">{{ $message }}</div>
    @enderror
    @error('role_ids.*')
        <div class="text-danger small">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="department_ids" class="form-label">{{ __('Pôles (départements)') }}</label>
    <select name="department_ids[]" id="department_ids" class="form-select" multiple size="8">
        @foreach($departments as $department)
            <option value="{{ $department->id }}"
                {{ in_array((string) $department->id, old('department_ids', $isEdit ? $profile->departments->pluck('id')->map(fn ($id) => (string) $id)->all() : []), true) ? 'selected' : '' }}>
                {{ $department->name }}
            </option>
        @endforeach
    </select>
    <div class="form-text">{{ __('Donne accès via la structure (ex: tout le pôle financier).') }}</div>
    @error('department_ids')
        <div class="text-danger small">{{ $message }}</div>
    @enderror
    @error('department_ids.*')
        <div class="text-danger small">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="service_ids" class="form-label">{{ __('Services') }}</label>
    <select name="service_ids[]" id="service_ids" class="form-select" multiple size="10">
        @foreach($services as $service)
            @php
                $deptName = optional(optional($service->subDepartment)->department)->name;
                $subName = optional($service->subDepartment)->name;
                $label = $service->name;
                if ($subName || $deptName) {
                    $label .= ' — ' . trim(implode(' / ', array_filter([$deptName, $subName])));
                }
            @endphp
            <option value="{{ $service->id }}"
                {{ in_array((string) $service->id, old('service_ids', $isEdit ? $profile->services->pluck('id')->map(fn ($id) => (string) $id)->all() : []), true) ? 'selected' : '' }}>
                {{ $label }}
            </option>
        @endforeach
    </select>
    @error('service_ids')
        <div class="text-danger small">{{ $message }}</div>
    @enderror
</div>
