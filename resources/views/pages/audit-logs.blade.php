@extends('layouts.app')

@section('title', 'System Log')

@section('content')
    <div class="page-header p-3 p-lg-4 mb-3">
        <div class="row align-items-center position-relative" style="z-index: 1;">
            <div class="col">
                <h2 class="h4 fw-bold mb-1"><i class="bi bi-clock-history me-2"></i>System Log</h2>
                <p class="small mb-0 opacity-75">See what changed, when it changed, and who made the change</p>
            </div>
            <div class="col-auto">
                <span class="badge bg-white text-yg rounded-pill px-2 py-1">
                    <i class="bi bi-list-check me-1"></i>{{ number_format($logs->total()) }} entries
                </span>
            </div>
        </div>
    </div>

    <form method="GET" class="card yg-card p-3 mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="logUserName" class="form-label fw-semibold">Search</label>
                <input id="logUserName" name="user_name" value="{{ request('user_name') }}" class="form-control" placeholder="Search user name" autocomplete="off">
            </div>
            <div class="col-md-3">
                <label for="logType" class="form-label fw-semibold">Filter by area</label>
                <select id="logType" name="type" class="form-select">
                    <option value="">All areas</option>
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="logAction" class="form-label fw-semibold">Filter by action</label>
                <select id="logAction" name="action" class="form-select">
                    <option value="">All actions</option>
                    @foreach ($actions as $value => $label)
                        <option value="{{ $value }}" @selected(request('action') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-yg flex-fill d-none" type="submit"><i class="bi bi-funnel me-1"></i>Apply filters</button>
                <a href="{{ route('audit-logs.index') }}" class="btn btn-outline-secondary" title="Clear filters"><i class="bi bi-x-lg"></i></a>
            </div>
        </div>
    </form>

    <div class="card yg-card overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr class="small text-secondary text-uppercase">
                        <th class="ps-4 py-3">Date and time</th>
                        <th class="py-3">Changed by</th>
                        <th class="py-3">Action</th>
                        <th class="py-3">Record</th>
                        <th class="py-3">Duration</th>
                        <th class="pe-4 py-3">Changes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        @php
                            $record = $log->auditable;
                            $recordName = $record?->name ?? $record?->title ?? ($record?->game ? ucfirst(str_replace('-', ' ', $record->game)) : ($record?->theme?->title ?? ($record ? class_basename($record) . ' #' . $log->auditable_id : 'Deleted record')));
                            $recordLabel = $recordName ? class_basename($log->auditable_type) . ': ' . $recordName : 'Deleted record';
                        @endphp
                        <tr>
                            <td class="ps-4 text-secondary text-nowrap">{{ $log->created_at->format('M j, Y g:i A') }}</td>
                            <td>
                                <div class="fw-semibold">{{ $log->user?->name ?? 'System' }}</div>
                                @if ($log->actor_unique_id)
                                    <div class="small text-secondary font-monospace">{{ $log->actor_unique_id }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $log->action === 'login' ? 'bg-primary-subtle text-primary-emphasis' : ($log->action === 'deleted' ? 'bg-danger-subtle text-danger-emphasis' : ($log->action === 'created' ? 'bg-success-subtle text-success-emphasis' : 'bg-warning-subtle text-warning-emphasis')) }} rounded-pill">
                                    {{ $log->action === 'login' ? 'Login' : ucfirst($log->action) }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $recordLabel }}</div>
                                <div class="small text-secondary">{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}</div>
                            </td>
                            <td class="text-secondary text-nowrap">
                                @if ($log->duration_ms !== null)
                                    {{ intdiv($log->duration_ms, 60000) ? intdiv($log->duration_ms, 60000) . 'm ' : '' }}{{ intdiv($log->duration_ms % 60000, 1000) }}s {{ $log->duration_ms % 1000 }}ms
                                @else
                                    Not recorded
                                @endif
                            </td>
                            <td class="pe-4">
                                @if ($log->new_values)
                                    <details>
                                        <summary class="small fw-semibold text-yg">View details</summary>
                                        <div class="small mt-2">
                                            @foreach ($log->new_values as $field => $value)
                                                <div><span class="text-secondary">{{ str_replace('_', ' ', ucfirst($field)) }}:</span> {{ is_array($value) ? json_encode($value) : ($value === null ? 'None' : $value) }}</div>
                                            @endforeach
                                        </div>
                                    </details>
                                @else
                                    <span class="small text-secondary">No value details</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-5">No system changes have been recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($logs->hasPages())
        <nav class="mt-4" aria-label="System log pagination">
            {{ $logs->links() }}
        </nav>
    @endif
@endsection

@push('scripts')
<script>
    (() => {
        const form = document.getElementById('logUserName')?.form;
        const input = document.getElementById('logUserName');
        const type = document.getElementById('logType');
        const action = document.getElementById('logAction');
        let timer;

        input?.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(() => form?.requestSubmit(), 350);
        });

        [type, action].forEach((select) => select?.addEventListener('change', () => form?.requestSubmit()));
    })();
</script>
@endpush
