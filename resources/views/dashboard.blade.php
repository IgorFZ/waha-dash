@extends('layouts.app')

@section('title', 'Dashboard - Waha Dash')

@section('topbar')
    <div class="d-inline-flex align-items-center gap-2 border border-success rounded px-3 py-2 bg-white">
        <span class="fw-semibold">{{ $session->name }}</span>
        <span class="badge text-bg-success">{{ $session->status->label() }}</span>
    </div>
@endsection

@section('content')
    <div class="py-4">
        <h1 class="h3 mb-1">Dashboard</h1>
        <p class="text-secondary mb-0">Vamos montar esta area nas proximas etapas.</p>
    </div>
@endsection
