@props(['status'])

{{--
    x-auth-session-status
    Shows a flash session status message (e.g. password reset link sent).
--}}

@if ($status)
    <div {{ $attributes->merge([
        'class' => 'mb-4 text-sm font-medium rounded-lg px-4 py-3 border',
        'style' => 'color: var(--green-vivid); background: rgba(0,200,0,0.08); border-color: rgba(0,200,0,0.22);'
    ]) }}>
        {{ $status }}
    </div>
@endif
