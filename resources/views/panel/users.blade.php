@extends('layouts.panel')

@section('title', 'User Management')
@section('page-label', 'User Management')

@section('content')

<div class="page-header">
  <h1 class="page-title">User Management</h1>
  <p class="page-subtitle">Create and manage system user accounts.</p>
</div>

{{-- Flash message --}}
@if (session('success'))
  <div class="alert alert--success">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
    </svg>
    {{ session('success') }}
  </div>
@endif

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">All Users <span class="table-count">({{ $users->total() }})</span></span>
    <button class="btn-primary" onclick="openModal()">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
      </svg>
      Add User
    </button>
  </div>

  <table class="data-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Name</th>
        <th>Email</th>
        <th>Role</th>
        <th>Status</th>
        <th>Joined</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      @forelse ($users as $user)
        <tr>
          <td style="color:var(--text-muted);font-size:12px;">{{ $users->firstItem() + $loop->index }}</td>
          <td>
            <div style="display:flex;align-items:center;gap:9px;">
              <div>
                <div>{{ $user->name }}</div>
                @if ($user->must_change_password)
                  <div style="font-size:11px;color:var(--warning);margin-top:1px;">Temporary password</div>
                @endif
              </div>
            </div>
          </td>
          <td style="color:var(--text-muted);">{{ $user->email }}</td>
          <td>
            @if ($user->role === 'supervisor')
              <span class="badge badge--blue">Supervisor</span>
            @else
              <span class="badge">{{ $user->getRoleLabel() }}</span>
            @endif
          </td>
          <td>
            @if ($user->is_active)
              <span class="badge badge--green">Active</span>
            @else
              <span class="badge badge--red">Inactive</span>
            @endif
          </td>
          <td style="color:var(--text-muted);font-size:12px;">{{ $user->created_at->format('d M Y') }}</td>
          <td>
            <form method="POST" action="{{ route('panel.users.toggle-status', $user) }}">
              @csrf
              @method('PATCH')
              <button
                type="submit"
                class="btn-ghost btn-ghost--sm"
                title="{{ $user->is_active ? 'Deactivate' : 'Activate' }} account"
              >
                {{ $user->is_active ? 'Deactivate' : 'Activate' }}
              </button>
            </form>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="7" style="text-align:center;color:var(--text-muted);padding:40px 16px;">
            No users found. Create one using the button above.
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>

  {{-- Pagination --}}
  @if ($users->hasPages())
    <div class="pagination-bar">
      {{-- Previous --}}
      @if ($users->onFirstPage())
        <span class="page-btn page-btn--disabled">&lsaquo;</span>
      @else
        <a href="{{ $users->previousPageUrl() }}" class="page-btn">&lsaquo;</a>
      @endif

      {{-- Page numbers --}}
      @foreach ($users->getUrlRange(1, $users->lastPage()) as $page => $url)
        @if ($page == $users->currentPage())
          <span class="page-btn page-btn--active">{{ $page }}</span>
        @else
          <a href="{{ $url }}" class="page-btn">{{ $page }}</a>
        @endif
      @endforeach

      {{-- Next --}}
      @if ($users->hasMorePages())
        <a href="{{ $users->nextPageUrl() }}" class="page-btn">&rsaquo;</a>
      @else
        <span class="page-btn page-btn--disabled">&rsaquo;</span>
      @endif
    </div>
  @endif

</div>


{{-- ── Create User Modal ── --}}
<div class="modal-overlay" id="userModal" onclick="closeModalOnOverlay(event)">
  <div class="modal">

    <div class="modal-header">
      <h2 class="modal-title">Create User Account</h2>
      <button class="modal-close" onclick="closeModal()" aria-label="Close">&times;</button>
    </div>

    <form method="POST" action="{{ route('panel.users.store') }}" novalidate>
      @csrf

      <div class="modal-body">

        {{-- Validation errors --}}
        @if ($errors->any())
          <div class="alert alert--error">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <div>
              @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
              @endforeach
            </div>
          </div>
        @endif

        {{-- Name --}}
        <div class="field">
          <label for="name">Full Name</label>
          <input
            type="text"
            id="name"
            name="name"
            value="{{ old('name') }}"
            placeholder="e.g. John Smith"
            required
            autocomplete="name"
          />
        </div>

        {{-- Email --}}
        <div class="field">
          <label for="email">Email Address</label>
          <input
            type="email"
            id="email"
            name="email"
            value="{{ old('email') }}"
            placeholder="user@example.com"
            required
            autocomplete="email"
          />
        </div>

        {{-- Role --}}
        <div class="field">
          <label for="role">Role</label>
          <select id="role" name="role" required>
            <option value="" disabled {{ old('role') ? '' : 'selected' }}>Select a role…</option>
            <option value="supervisor" {{ old('role') === 'supervisor' ? 'selected' : '' }}>Supervisor</option>
          </select>
        </div>

        {{-- Status --}}
        <div class="field">
          <label for="is_active">Account Status</label>
          <select id="is_active" name="is_active" required>
            <option value="1" {{ old('is_active', '1') === '1' ? 'selected' : '' }}>Active</option>
            <option value="0" {{ old('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
          </select>
        </div>

      </div>{{-- /modal-body --}}

      <div class="modal-footer">
        <button type="button" class="btn-ghost" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn-primary">Create Account</button>
      </div>

    </form>
  </div>
</div>


<script>
  const modal      = document.getElementById('userModal');
  const firstInput = modal.querySelector('input, select');

  function openModal() {
    modal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    // Always focus the first field whenever the modal opens
    requestAnimationFrame(() => firstInput?.focus());
  }

  function closeModal() {
    modal.classList.remove('is-open');
    document.body.style.overflow = '';
  }

  function closeModalOnOverlay(e) {
    if (e.target === modal) closeModal();
  }

  // Re-open the modal if there were validation errors (old input present)
  @if ($errors->any())
    openModal();
  @endif
</script>

@endsection
