@php
    $defaultAvatar = asset(config('profile.default_avatar'));
@endphp

<div class="amxpriv-grid">
    @foreach ($admins as $index => $admin)
        @php
            $isLow = !$admin['isForever'] && $admin['progress'] < 20;
            $avatarUrl = $admin['avatar'] ?: $defaultAvatar;
            $flags = $admin['access'] !== '' ? str_split($admin['access']) : [];
            $hasFullAccess = in_array('z', $flags, true);
            $flagLimit = 6;
            $visibleFlags = $hasFullAccess ? [] : array_slice($flags, 0, $flagLimit);
            $hiddenCount = $hasFullAccess ? 0 : max(0, count($flags) - $flagLimit);
            $hiddenTooltip = $hiddenCount > 0
                ? implode(', ', array_map(fn($f) => $f . ' — ' . __('amxprivileges.flag_descriptions.' . $f), array_slice($flags, $flagLimit)))
                : '';
        @endphp
        <div class="amxpriv-card {{ $isLow ? 'amxpriv-card--warning' : '' }} {{ $admin['isForever'] ? 'amxpriv-card--forever' : '' }}"
            style="--priv-progress: {{ $admin['progress'] }}%; --anim-delay: {{ $index * 40 }}ms"
            hx-get="{{ route('amxprivileges.detail', ['serverId' => $currentServerId, 'adminId' => $admin['id']]) }}"
            hx-target="#amxpriv-detail-modal .modal__content"
            hx-swap="innerHTML"
            data-disable-loading-states>

            <div class="amxpriv-card__body">
                <div class="amxpriv-card__head">
                    @if (!empty($admin['steamid']) && !$admin['isSensitiveId'])
                        <a href="{{ url('profile/search/' . $admin['steamid']) }}"
                            class="amxpriv-card__user"
                            data-user-card
                            hx-boost="true" hx-target="#main"
                            hx-swap="outerHTML transition:true"
                            yoyo:ignore
                            onclick="event.stopPropagation()">
                            <div class="amxpriv-card__avatar">
                                <img src="{{ $avatarUrl }}" alt="{{ $admin['nickname'] ?: $admin['steamid'] }}" loading="lazy">
                            </div>
                            <div class="amxpriv-card__info">
                                <h4 class="amxpriv-card__name">{{ $admin['nickname'] ?: $admin['steamid'] }}</h4>
                                <span class="amxpriv-card__privilege">{{ $admin['privilegeName'] }}</span>
                            </div>
                        </a>
                    @else
                        <div class="amxpriv-card__user">
                            <div class="amxpriv-card__avatar">
                                <img src="{{ $avatarUrl }}" alt="{{ $admin['nickname'] ?: $admin['steamid'] }}" loading="lazy">
                            </div>
                            <div class="amxpriv-card__info">
                                <h4 class="amxpriv-card__name">{{ $admin['nickname'] ?: $admin['steamid'] }}</h4>
                                <span class="amxpriv-card__privilege">{{ $admin['privilegeName'] }}</span>
                            </div>
                        </div>
                    @endif
                    <div class="amxpriv-card__status">
                        @if ($admin['isForever'])
                            <span class="amxpriv-card__badge amxpriv-card__badge--forever">
                                <x-icon path="ph.regular.infinity" />
                                <span>{{ __('amxprivileges.forever') }}</span>
                            </span>
                        @else
                            <span class="amxpriv-card__badge {{ $isLow ? 'amxpriv-card__badge--low' : '' }}"
                                data-tooltip="{{ date('d.m.Y H:i', $admin['expired']) }}">
                                <x-icon path="ph.regular.clock" />
                                <span>{{ __('amxprivileges.days_left', ['days' => $admin['daysLeft']]) }}</span>
                            </span>
                        @endif
                    </div>
                </div>

                @if (!empty($admin['steamid']) && !$admin['isSensitiveId'])
                    <div class="amxpriv-card__steamid">
                        <x-icon path="ph.regular.identification-badge" />
                        <span>{{ $admin['steamid'] }}</span>
                    </div>
                @endif

                @if (!empty($flags) && $canSeeFlags)
                    <div class="amxpriv-card__flags">
                        @if ($hasFullAccess)
                            <span class="amxpriv-card__flag amxpriv-card__flag--root"
                                data-tooltip="{{ __('amxprivileges.flag_descriptions.z') }}">
                                <x-icon path="ph.bold.crown" />
                                <span>{{ __('amxprivileges.flag_descriptions.z') }}</span>
                            </span>
                        @else
                            @foreach ($visibleFlags as $flag)
                                <span class="amxpriv-card__flag"
                                    data-tooltip="{{ $flag }} — {{ __('amxprivileges.flag_descriptions.' . $flag) }}">
                                    {{ $flag }}
                                </span>
                            @endforeach
                            @if ($hiddenCount > 0)
                                <span class="amxpriv-card__flag amxpriv-card__flag--more"
                                    data-tooltip="{{ $hiddenTooltip }}">
                                    +{{ $hiddenCount }}
                                </span>
                            @endif
                        @endif
                    </div>
                @endif

                @if (!$admin['isForever'])
                    <div class="amxpriv-card__progress">
                        <div class="amxpriv-card__progress-bar"></div>
                    </div>
                @endif
            </div>
        </div>
    @endforeach
</div>
