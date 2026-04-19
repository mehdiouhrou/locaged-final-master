@php
    /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $roles */
@endphp
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h2 class="h4 mb-0 fw-bold">{{ ui_t('pages.roles.title') }}</h2>
    <div class="d-flex flex-wrap gap-2">
        @can('viewAny', \App\Models\User::class)
            <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary">{{ ui_t('pages.roles.users_btn') }}</a>
        @endcan
        @can('create', \Spatie\Permission\Models\Role::class)
            <a href="{{ route('roles.create') }}" class="btn btn-sm btn-dark">{{ ui_t('pages.roles.add_role') }}</a>
        @endcan
    </div>
</div>
<p class="text-muted small">{{ ui_t('pages.roles.manage') }}</p>

<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>{{ ui_t('pages.roles.table.name') }}</th>
                <th>{{ ui_t('pages.roles.table.permissions') }}</th>
                <th>{{ ui_t('pages.roles.table.users') }}</th>
                <th>{{ ui_t('pages.roles.table.created_at') }}</th>
                <th>{{ ui_t('pages.roles.table.updated_at') }}</th>
                <th>{{ ui_t('pages.roles.table.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($roles as $role)
                <tr>
                    <td class="fw-medium">{{ $role->name }}</td>
                    <td>{{ $role->permissions_count ?? 0 }}</td>
                    <td>{{ $role->users_count ?? 0 }}</td>
                    <td class="small text-muted">{{ $role->created_at }}</td>
                    <td class="small text-muted">{{ $role->updated_at }}</td>
                    <td>
                        @can('update', $role)
                            <a href="{{ route('roles.edit', ['role' => $role->id]) }}" class="btn btn-sm btn-outline-primary">{{ ui_t('pages.roles.edit') }}</a>
                        @endcan
                        @can('delete', $role)
                            <button type="button"
                                    data-id="{{ $role->id }}"
                                    data-name="{{ $role->name }}"
                                    data-url="{{ route('roles.destroy', $role->id) }}"
                                    class="btn btn-sm btn-outline-danger trigger-action"
                                    data-method="DELETE"
                                    data-button-text="{{ ui_t('pages.roles.confirm') }}"
                                    data-title="{{ ui_t('pages.roles.delete_title', ['name' => $role->name]) }}"
                                    data-body="{{ ui_t('pages.roles.delete_body') }}">
                                {{ ui_t('pages.roles.delete') }}
                            </button>
                        @endcan
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="d-flex justify-content-center mt-3 master-console-pagination">
    {{ $roles->links('pagination::bootstrap-5') }}
</div>
