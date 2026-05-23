@extends('layouts.guest')

@section('title', 'Reset password — Bus Depot MS')

@section('content')
<div class="guest-card">

  <div class="guest-logo">
    <div class="guest-logo-icon">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <rect x="1" y="3" width="15" height="13" rx="2"/>
        <path d="M16 8h4l3 3v5h-7V8z"/>
        <circle cx="5.5" cy="18.5" r="2.5"/>
        <circle cx="18.5" cy="18.5" r="2.5"/>
      </svg>
    </div>
    <div class="guest-logo-text">
      Bus Depot MS
      <span>Management System</span>
    </div>
  </div>

  <a href="{{ route('login') }}" class="back-link">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
    </svg>
    Back to sign in
  </a>

  <h1 class="guest-heading">Reset your password</h1>
  <p class="guest-subheading">Enter your email and we'll send you a reset link.</p>

  {{-- Success --}}
  @if (session('status'))
    <div class="alert alert--success" style="margin-bottom:14px;">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px"><polyline points="20 6 9 17 4 12"/></svg>
      <span>{{ session('status') }}</span>
    </div>
  @endif

  {{-- Error --}}
  @if ($errors->any())
    <div class="alert alert--error" style="margin-bottom:14px;">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <span>{{ $errors->first() }}</span>
    </div>
  @endif

  <form class="guest-form" method="POST" action="{{ route('password.email') }}">
    @csrf

    <div class="field">
      <label for="email">Email address</label>
      <input
        type="email"
        id="email"
        name="email"
        value="{{ old('email') }}"
        placeholder="you@example.com"
        autocomplete="email"
        autofocus
        required
      />
      <span class="field-hint">We'll send a password reset link to this address.</span>
    </div>

    <button type="submit" class="btn-primary" style="margin-top:4px;">
      Send reset link
    </button>

  </form>

</div>
@endsection
