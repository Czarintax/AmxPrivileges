@php
    $defaultAvatar = asset(config('profile.default_avatar'));
@endphp

<x-card withoutPadding>
    <div class="table-responsive amxpriv-table-view">
        <table class="table">
            <thead>
                <tr>
                    <th>{{ __('amxprivileges.table.player') }}</th>
                    <th>{{ __('amxprivileges.table.privilege') }}</th>
                    @if ($canSeeFlags)
                        <th>{{ __('amxprivileges.table.access') }}</th>
                    @endif
                    <th>{{ __('amxprivileges.table.expires') }}</th>
                    <th>{{ __('amxprivileges.table.status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($admins as $admin)
                    @php
                        $isLow = !$admin['isForever'] && $admin['progress'] < 20;
                        $avatarUrl = $admin['avatar'] ?: $defaultAvatar;
                        $flags = $admin['access'] !== '' ? str_split($admin['access']) : [];
                        $hasFullAccess = in_array('z', $flags, true);
                        $flagLimit = 8;
                        $visibleFlags = $hasFullAccess ? [] : array_slice($flags, 0, $flagLimit);
                        $hiddenCount = $hasFullAccess ? 0 : max(0, count($flags) - $flagLimit);
                        $hiddenTooltip = $hiddenCount > 0
                            ? implode(', ', array_map(fn($f) => $f . ' — ' . __('amxprivileges.flag_descriptions.' . $f), array_slice($flags, $flagLimit)))
                            : '';
                    @endphp
                    <tr hx-get="{{ route('amxprivileges.detail', ['serverId' => $currentServerId, 'adminId' => $admin['id']]) }}"
                        hx-target="#amxpriv-detail-modal .modal__content"
                        hx-swap="innerHTML"
                        data-disable-loading-states>
                        <td>
                            @if (!empty($admin['fluteProfileUrl']))
                                <a href="{{ $admin['fluteProfileUrl'] }}"
                                    class="amxpriv-table-view__player"
                                    data-user-card
                                    hx-boost="true" hx-target="#main"
                                    hx-swap="outerHTML transition:true"
                                    yoyo:ignore
                                    onclick="event.stopPropagation()">
                                    <img class="amxpriv-table-view__avatar-img"
                                        src="{{ $avatarUrl }}"
                                        alt="{{ $admin['nickname'] }}"
                                        loading="lazy">
                                    <span class="amxpriv-table-view__player-info">
                                        <span class="amxpriv-table-view__player-name">{{ $admin['nickname'] }}</span>
                                    </span>
                                </a>
                            @else
                                <div class="amxpriv-table-view__player">
                                    <img class="amxpriv-table-view__avatar-img"
                                        src="{{ $avatarUrl }}"
                                        alt="{{ $admin['nickname'] }}"
                                        loading="lazy">
                                    <div class="amxpriv-table-view__player-info">
                                        <span class="amxpriv-table-view__player-name">{{ $admin['nickname'] }}</span>
                                    </div>
                                </div>
                            @endif
                        </td>
                        <td>
                            <span class="amxpriv-table-view__privilege-name">{{ $admin['privilegeName'] }}</span>
                        </td>
                        @if ($canSeeFlags)
                            <td>
                                <div class="amxpriv-table-view__flags">
                                    @if ($hasFullAccess)
                                        <span class="amxpriv-card__flag amxpriv-card__flag--root"
                                            data-tooltip="{{ __('amxprivileges.flag_descriptions.z') }}">
                                            <x-icon path="ph.bold.crown" />
                                            <span>{{ __('amxprivileges.flag_descriptions.z') }}</span>
                                        </span>
                                    @else
                                        @foreach ($visibleFlags as $flag)
                                            <span class="amxpriv-card__flag"
                                                data-tooltip="{{ $flag }} — {{ __('amxprivileges.flag_descriptions.' . $flag) }}">{{ $flag }}</span>
                                        @endforeach
                                        @if ($hiddenCount > 0)
                                            <span class="amxpriv-card__flag amxpriv-card__flag--more"
                                                data-tooltip="{{ $hiddenTooltip }}">+{{ $hiddenCount }}</span>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        @endif
                        <td>
                            @if ($admin['isForever'])
                                <span class="amxpriv-table-view__badge amxpriv-table-view__badge--forever">{{ __('amxprivileges.forever') }}</span>
                            @else
                                <span class="amxpriv-table-view__badge {{ $isLow ? 'amxpriv-table-view__badge--low' : '' }}"
                                    data-tooltip="{{ date('d.m.Y H:i', $admin['expired']) }}">
                                    {{ __('amxprivileges.days_left', ['count' => $admin['daysLeft']]) }}
                                </span>
                            @endif
                        </td>
                        <td>
                            <div class="amxpriv-table-view__progress-cell">
                                <div class="amxpriv-table-view__progress-bar">
                                    <div class="amxpriv-table-view__progress-fill {{ $admin['isForever'] ? 'amxpriv-table-view__progress-fill--forever' : ($isLow ? 'amxpriv-table-view__progress-fill--low' : '') }}"
                                        style="width: {{ $admin['progress'] }}%"></div>
                                </div>
                                <span class="amxpriv-table-view__progress-text">{{ $admin['progress'] }}%</span>
                            </div>
                        </td>
                        <td>
                            <div class="amxpriv-table-view__arrow">
                                <x-icon path="ph.regular.arrow-right" />
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-card>
