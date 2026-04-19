@extends('flute::layouts.app')

@section('title', $pageTitle)

@push('content')
    <section class="container mt-3">
        <div class="col-md-12">
            <x-legend title="{{ $pageTitle }}" description="{{ $pageDescription }}">
                @if (count($servers) > 0)
                    <div hx-get="{{ url()->current() }}"
                        hx-trigger="change from:find select, fs:change from:find .fs-container"
                        hx-push-url="true" hx-swap="morph" hx-target="#main"
                        hx-include="find [name=server_id]">
                        <x-fields.select name="server_id" :searchable="true">
                            @foreach ($servers as $server)
                                <option value="{{ $server->id }}"
                                    data-description="{{ $server->getConnectionString() }}"
                                    {{ $currentServerId == $server->id ? 'selected' : '' }}>
                                    {{ transValue($server->name) }}
                                </option>
                            @endforeach
                        </x-fields.select>
                    </div>
                @endif
            </x-legend>

            @if (empty($servers))
                <x-card>
                    <div class="amxpriv-empty">
                        <div class="amxpriv-empty__icon">
                            <x-icon path="ph.regular.shield-slash" />
                        </div>
                        <p>{{ __('amxprivileges.no_servers') }}</p>
                    </div>
                </x-card>
            @elseif (empty($admins))
                <x-card>
                    <div class="amxpriv-empty">
                        <div class="amxpriv-empty__icon">
                            <x-icon path="ph.regular.user-list" />
                        </div>
                        <p>{{ __('amxprivileges.no_admins') }}</p>
                    </div>
                </x-card>
            @elseif ($displayMode === 'table')
                @include('amxprivileges::partials.table', ['admins' => $admins, 'currentServerId' => $currentServerId, 'canSeeFlags' => $canSeeFlags])
            @else
                @include('amxprivileges::partials.cards', ['admins' => $admins, 'currentServerId' => $currentServerId, 'canSeeFlags' => $canSeeFlags])
            @endif

            @if ($totalPages > 1)
                <div class="d-flex justify-center mt-3">
                    <ul class="table__pagination">
                        @for ($i = 1; $i <= $totalPages; $i++)
                            <li class="table__pagination-item {{ $i === $page ? 'active' : '' }}">
                                <a hx-get="{{ route('amxprivileges.index', array_filter(['server_id' => $currentServerId, 'page' => $i > 1 ? $i : null])) }}"
                                    hx-push-url="true" hx-swap="morph" hx-target="#main">
                                    {{ $i }}
                                </a>
                            </li>
                        @endfor
                    </ul>
                </div>
            @endif
        </div>
    </section>

    <x-modal id="amxpriv-detail-modal" title="{{ __('amxprivileges.privilege_info') }}" size="md">
        <div class="amxpriv-detail-skeleton">
            <div class="skeleton" style="height: 48px; border-radius: var(--border1);"></div>
            <div class="skeleton" style="height: 120px; border-radius: var(--border1); margin-top: 12px;"></div>
            <div class="skeleton" style="height: 60px; border-radius: var(--border1); margin-top: 12px;"></div>
        </div>
    </x-modal>
@endpush
