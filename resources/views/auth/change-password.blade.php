@extends('layouts.guest')

@section('title', 'Set your password — Bus Depot MS')

@section('content')

<div class="modal-overlay is-open">
  <div class="modal" style="max-width:360px;">

    <div class="modal-header">
      <div>
        <h2 class="modal-title">Set your password</h2>
        <p style="font-size:12px;color:var(--text-muted);margin-top:3px;">
          Your account uses a temporary password. Please set a new one to continue.
        </p>
      </div>
    </div>

    <form method="POST" action="{{ route('password.change.submit') }}" novalidate>
      @csrf

      <div class="modal-body">

        @if ($errors->hasBag('changePassword'))
          <div class="alert alert--error">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <div>
              @foreach ($errors->getBag('changePassword')->all() as $error)
                <div>{{ $error }}</div>
              @endforeach
            </div>
          </div>
        @endif

        <div class="field">
          <label for="password">New Password</label>
          <input
            type="password"
            id="password"
            name="password"
            placeholder="Min. 8 characters"
            autocomplete="new-password"
            autofocus
            required
          />
        </div>

        <div class="field">
          <label for="password_confirmation">Confirm Password</label>
          <input
            type="password"
            id="password_confirmation"
            name="password_confirmation"
            placeholder="Repeat new password"
            autocomplete="new-password"
            required
          />
        </div>

      </div>

      <div class="modal-footer">
        <button type="submit" class="btn-primary">Update Password</button>
      </div>

    </form>
  </div>
</div>

@endsection
