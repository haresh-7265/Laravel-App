@extends('layouts.app')
@section('title', 'Active sessions')

@section('content')
<div class="container py-4" style="max-width: 680px">

  <div class="d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-laptop fs-4 text-secondary"></i>
    <div>
      <h5 class="mb-0">Active sessions</h5>
      <small class="text-muted">Devices currently logged in to your account</small>
    </div>
  </div>

  @if($tokens->isEmpty())
    <div class="text-center py-5 border rounded-3">
      <i class="bi bi-device-ssd fs-1 text-muted"></i>
      <p class="fw-500 mt-3 mb-1">No active sessions</p>
      <p class="text-muted small">You have no other devices logged in to your account.</p>
    </div>
  @else
    <p class="text-uppercase text-muted" style="font-size:11px;letter-spacing:.06em">
      {{ $tokens->count() }} {{ Str::plural('device', $tokens->count()) }}
    </p>

    <div class="d-flex flex-column gap-2">
      @foreach($tokens as $token)
        @php
          $icon = match(true) {
            str_contains(strtolower($token->name), 'iphone')  => 'bi-phone',
            str_contains(strtolower($token->name), 'android') => 'bi-phone',
            str_contains(strtolower($token->name), 'postman') => 'bi-braces',
            str_contains(strtolower($token->name), 'mobile')  => 'bi-phone',
            str_contains(strtolower($token->name), 'mac')     => 'bi-laptop',
            default => 'bi-window',
          };
        @endphp

        <div class="d-flex align-items-center gap-3 p-3 border rounded-3 bg-white">
          <div class="d-flex align-items-center justify-content-center rounded-2 bg-light"
               style="width:40px;height:40px;flex-shrink:0">
            <i class="bi {{ $icon }} fs-5 text-secondary"></i>
          </div>

          <div class="flex-grow-1">
            <div class="d-flex align-items-center gap-2 mb-1">
              <span class="fw-500 small">{{ $token->name }}</span>
            </div>
            <div class="d-flex gap-3 text-muted" style="font-size:12px">
              <span>
                <i class="bi bi-clock me-1"></i>
                {{ $token->last_used_at?->diffForHumans() ?? 'Never used' }}
              </span>
              <span>
                <i class="bi bi-calendar me-1"></i>
                Added {{ $token->created_at->diffForHumans() }}
              </span>
              <span>
                <i class="bi bi-clock-history me-1"></i>
                @if($token->expires_at)
                    @if($token->expires_at->isPast())
                        <span class="text-danger">Expired {{ $token->expires_at->diffForHumans() }}</span>
                    @elseif($token->expires_at->diffInDays() <= 3)
                        <span class="text-warning">Expires {{ $token->expires_at->diffForHumans() }}</span>
                    @else
                        <span>Expires {{ $token->expires_at->diffForHumans() }}</span>
                    @endif
                @else
                    <span class="text-muted">No expiry</span>
                @endif
                </span>
            </div>
          </div>

          <form method="POST"
                action="{{ route('customer.devices.revoke', $token->id) }}"
                onsubmit="return confirm('Revoke this session?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger">
              <i class="bi bi-x-circle me-1"></i>Revoke
            </button>
          </form>
        </div>
      @endforeach
    </div>
  @endif

</div>
@endsection