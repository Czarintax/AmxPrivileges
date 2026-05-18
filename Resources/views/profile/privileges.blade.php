<div class="amxpriv-profile">
    @if (empty($activePrivileges) && empty($expiredPrivileges))
        <div class="amxpriv-profile__empty">
            <p>{{ $isOwner ? __('amxprivileges.profile.no_privileges') : __('amxprivileges.profile.no_privileges_other') }}</p>
            @if ($isOwner)
                <a href="{{ url('privileges') }}" class="amxpriv-profile__empty-link">
                    {{ __('amxprivileges.profile.go_to_page') }} <x-icon path="ph.regular.arrow-right" />
                </a>
            @endif
        </div>
    @else
        <div class="amxpriv-profile__list">
            @foreach ($activePrivileges as $entry)
                @php
                    $progress = $entry['progress'];
                    $isLow = $progress < 20 && !$entry['isForever'];
                @endphp
                <div class="amxpriv-profile__card {{ $isLow ? 'amxpriv-profile__card--warning' : '' }}"
                    style="--priv-fill: {{ $progress }}%">

                    <div class="amxpriv-profile__head">
                        <div class="amxpriv-profile__head-left">
                            @if (!empty($entry['avatar']))
                                <img src="{{ $entry['avatar'] }}" alt="" class="amxpriv-profile__avatar" loading="lazy">
                            @endif
                            <div>
                                <h4 class="amxpriv-profile__name">{{ $entry['privilegeName'] }}</h4>
                                @if ($isOwner)
                                    <span class="amxpriv-profile__steamid">{{ $entry['steamid'] }}</span>
                                @endif
                            </div>
                        </div>
                        @if (!$entry['isForever'])
                            <span class="amxpriv-profile__time {{ $isLow ? 'amxpriv-profile__time--low' : '' }}"
                                data-tooltip="{{ date('d.m.Y H:i', $entry['expired']) }}">
                                {{ __('amxprivileges.profile.expires_in', ['count' => $entry['daysLeft']]) }}
                            </span>
                        @else
                            <span class="amxpriv-profile__time amxpriv-profile__time--forever"
                                data-tooltip="{{ __('amxprivileges.no_time_limit') }}">
                                {{ __('amxprivileges.forever') }}
                            </span>
                        @endif
                    </div>

                    <div class="amxpriv-profile__body">
                        <div class="amxpriv-profile__reply">
                            <span class="amxpriv-profile__reply-line"></span>
                            <div class="amxpriv-profile__props">
                                <span class="amxpriv-profile__prop" data-tooltip="{{ __('amxprivileges.server') }}">
                                    {{ $entry['serverName'] }}
                                </span>
                                @if ($isOwner && $canSeeFlags)
                                    <span class="amxpriv-profile__sep">/</span>
                                    <span class="amxpriv-profile__prop">
                                        {{ $entry['access'] ?: '—' }}
                                    </span>
                                @endif
                                @if ($entry['created'] > 0)
                                    <span class="amxpriv-profile__sep">/</span>
                                    <span class="amxpriv-profile__prop" data-tooltip="{{ __('amxprivileges.profile.granted_date') }}">
                                        {{ date('d.m.Y', $entry['created']) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if ($isOwner && ($entry['usesPassword'] || $entry['hasPassword']))
                        <div class="amxpriv-profile__foot">
                            @if ($entry['usesPassword'])
                                <button type="button" class="amxpriv-profile__act"
                                    data-modal-open="amxpriv-password-modal">
                                    <x-icon path="ph.regular.key" />
                                    {{ __('amxprivileges.profile.change_password') }}
                                    <x-icon path="ph.regular.caret-right" class="amxpriv-profile__act-arrow" />
                                </button>
                            @else
                                <span class="amxpriv-profile__act amxpriv-profile__act--disabled"
                                    style="cursor: help; opacity: 0.5;"
                                    data-tooltip="{{ __('amxprivileges.profile.password_not_checked') }}">
                                    <x-icon path="ph.regular.key" />
                                    {{ __('amxprivileges.profile.change_password') }}
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach

            @foreach ($expiredPrivileges as $entry)
                <div class="amxpriv-profile__card amxpriv-profile__card--expired">
                    <div class="amxpriv-profile__head">
                        <div class="amxpriv-profile__head-left">
                            @if (!empty($entry['avatar']))
                                <img src="{{ $entry['avatar'] }}" alt="" class="amxpriv-profile__avatar" loading="lazy">
                            @endif
                            <div>
                                <h4 class="amxpriv-profile__name">{{ $entry['privilegeName'] }}</h4>
                                @if ($isOwner)
                                    <span class="amxpriv-profile__steamid">{{ $entry['steamid'] }}</span>
                                @endif
                            </div>
                        </div>
                        <span class="amxpriv-profile__time amxpriv-profile__time--expired">
                            {{ $entry['expired'] > 0 ? date('d.m.Y', $entry['expired']) : '' }}
                        </span>
                    </div>
                    <div class="amxpriv-profile__body">
                        <div class="amxpriv-profile__reply">
                            <span class="amxpriv-profile__reply-line"></span>
                            <div class="amxpriv-profile__props">
                                <span class="amxpriv-profile__prop">{{ $entry['serverName'] }}</span>
                                @if ($isOwner && $canSeeFlags)
                                    <span class="amxpriv-profile__sep">/</span>
                                    <span class="amxpriv-profile__prop">{{ $entry['access'] ?: '—' }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($isOwner && $showPasswordModal)
            <x-modal id="amxpriv-password-modal" :title="__('amxprivileges.profile.change_password')" size="small" :inline="true">
                <form hx-post="{{ $changePasswordUrl }}" hx-swap="none"
                    hx-on::after-request="if(event.detail.successful) { closeModal('amxpriv-password-modal'); }">
                    @csrf
                    <div class="profile-admin-modal__field" style="margin-bottom: var(--space-md)">
                        <label for="amxpriv-new-pw" class="profile-admin-modal__label">
                            {{ __('amxprivileges.profile.new_password') }}
                        </label>
                        <x-fields.input type="password" name="new_password" id="amxpriv-new-pw"
                            minlength="3" maxlength="32"
                            :placeholder="__('amxprivileges.profile.password_placeholder')" required />
                    </div>
                    <div class="profile-admin-modal__actions">
                        <x-button type="outline-primary" data-a11y-dialog-hide>
                            {{ __('def.cancel') }}
                        </x-button>
                        <x-button type="accent" submit withLoading>
                            <x-icon path="ph.regular.key" />
                            {{ __('amxprivileges.profile.change_password') }}
                        </x-button>
                    </div>
                </form>
            </x-modal>
        @endif
    @endif
</div>
