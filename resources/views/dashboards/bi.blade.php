@extends('layouts.app')

@section('content')
    <livewire:dashboards.bi-dashboard
        :ano-inicial="request()->integer('ano') ?: null"
        :municipio-id-inicial="request()->integer('municipio_id') ?: null" />
@endsection
