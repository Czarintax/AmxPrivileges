@php
    $accessList = $admin['access'] !== '' ? str_split($admin['access']) : [];
    $isLow = !$admin['isForever'] && $admin['progress'] < 20;
    $server = $admin['server'];
    $defaultAvatar = asset(config('profile.default_avatar'));
    $avatarUrl = $admin['avatar'] ?? $defaultAvatar;
    $profileLink = (!empty($admin['steamid']) && !$admin['isSensitiveId']) ? url('profile/search/' . $admin['steamid']) : null;
@endphp

<div class="amxpriv-detail">
    <div class="amxpriv-detail__header {{ $admin['isForever'] ? 'amxpriv-detail__header--forever' : ($isLow ? 'amxpriv-detail__header--warning' : '') }}">
        <a @if($profileLink) href="{{ $profileLink }}" @endif class="amxpriv-detail__header-avatar">
            <img src="{{ $avatarUrl }}" alt="{{ $admin['nickname'] ?: $admin['steamid'] }}">
        </a>
        <div class="amxpriv-detail__header-text">
            <h3 class="amxpriv-detail__name">{{ $admin['nickname'] ?: $admin['steamid'] }}</h3>
            <span class="amxpriv-detail__subtitle">{{ $admin['privilegeName'] }}</span>
        </div>
        <span class="amxpriv-detail__status {{ $admin['isForever'] ? 'amxpriv-detail__status--forever' : ($isLow ? 'amxpriv-detail__status--low' : '') }}">
            {{ __('amxprivileges.status_active') }}
        </span>
    </div>

    @if (!$admin['isForever'])
        <div class="amxpriv-detail__progress-wrap">
            <div class="amxpriv-detail__progress-bar" style="--progress: {{ $admin['progress'] }}%"></div>
            <div class="amxpriv-detail__progress-labels">
                <span>{{ __('amxprivileges.time_remaining') }}</span>
                <span>{{ __('amxprivileges.days_left', ['days' => $admin['daysLeft']]) }}</span>
            </div>
        </div>
    @endif

    <div class="amxpriv-detail__grid">
        <div class="amxpriv-detail__row">
            <span class="amxpriv-detail__label">
                <x-icon path="ph.regular.cloud" />
                {{ __('amxprivileges.server') }}
            </span>
            <span class="amxpriv-detail__value">{{ transValue($server->name) }}</span>
        </div>

        @if (!$admin['isSensitiveId'])
            <div class="amxpriv-detail__row">
                <span class="amxpriv-detail__label">
                    <x-icon path="ph.regular.identification-card" />
                    {{ __('amxprivileges.identifier') }}
                </span>
                <span class="amxpriv-detail__value amxpriv-detail__value--mono">{{ $admin['steamid'] }}</span>
            </div>
        @endif

        <div class="amxpriv-detail__row">
            <span class="amxpriv-detail__label">
                <x-icon path="ph.regular.shield-star" />
                {{ __('amxprivileges.privilege') }}
            </span>
            <span class="amxpriv-detail__value">{{ $admin['privilegeName'] }}</span>
        </div>

        <div class="amxpriv-detail__row">
            <span class="amxpriv-detail__label">
                <x-icon path="ph.regular.calendar-plus" />
                {{ __('amxprivileges.created') }}
            </span>
            <span class="amxpriv-detail__value">
                {{ $admin['created'] > 0 ? date('d.m.Y H:i', $admin['created']) : __('amxprivileges.unknown') }}
            </span>
        </div>

        <div class="amxpriv-detail__row">
            <span class="amxpriv-detail__label">
                <x-icon path="ph.regular.calendar-x" />
                {{ __('amxprivileges.expires') }}
            </span>
            <span class="amxpriv-detail__value">
                @if ($admin['isForever'])
                    <span class="amxpriv-detail__value--forever">{{ __('amxprivileges.no_time_limit') }}</span>
                @else
                    {{ date('d.m.Y H:i', $admin['expired']) }}
                @endif
            </span>
        </div>

        @if ($canSeeFlags)
            <div class="amxpriv-detail__row">
                <span class="amxpriv-detail__label">
                    <x-icon path="ph.regular.key" />
                    {{ __('amxprivileges.auth_flags') }}
                </span>
                <span class="amxpriv-detail__value amxpriv-detail__value--mono">{{ $admin['flags'] ?: '—' }}</span>
            </div>
        @endif

        <div class="amxpriv-detail__row">
            <span class="amxpriv-detail__label">
                <x-icon path="ph.regular.lock-simple" />
                {{ __('amxprivileges.status') }}
            </span>
            <span class="amxpriv-detail__value">
                @if ($admin['hasPassword'])
                    <x-icon path="ph.regular.check-circle" class="amxpriv-detail__icon--ok" />
                    {{ __('amxprivileges.has_password') }}
                @else
                    {{ __('amxprivileges.no_password') }}
                @endif
            </span>
        </div>

        @if ($admin['ashow'] !== null)
            <div class="amxpriv-detail__row">
                <span class="amxpriv-detail__label">
                    <x-icon path="ph.regular.eye" />
                    {{ __('amxprivileges.details') }}
                </span>
                <span class="amxpriv-detail__value">
                    {{ $admin['ashow'] ? __('amxprivileges.visible_in_admin_list') : __('amxprivileges.hidden_in_admin_list') }}
                </span>
            </div>
        @endif
    </div>

    @if ($canSeeFlags)
        <div class="amxpriv-detail__section">
            <h4 class="amxpriv-detail__section-title">
                <x-icon path="ph.regular.flag-banner" />
                {{ __('amxprivileges.access_flags') }}
            </h4>
            <div class="amxpriv-detail__flags">
                @foreach ($accessList as $flag)
                    <div class="amxpriv-detail__flag-item">
                        <span class="amxpriv-detail__flag-letter {{ $flag === 'z' ? 'amxpriv-detail__flag-letter--root' : '' }}">{{ $flag }}</span>
                        <span class="amxpriv-detail__flag-desc">{{ __('amxprivileges.flag_descriptions.' . $flag) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
