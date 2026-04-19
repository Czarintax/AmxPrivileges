<?php

namespace Flute\Modules\AmxPrivileges\Services;

use Flute\Core\Database\Entities\Server;

class AmxPrivilegesService
{
    protected const MOD_KEYS = ['Amx', 'AmxModX'];

    protected ?array $customPrivilegeNames = null;

    /**
     * @return array<int, array{server: Server, dbname: string, prefix: string}>
     */
    public function getAmxServers(): array
    {
        $servers = Server::query()->where('enabled', true)->fetchAll();
        $result = [];

        foreach ($servers as $server) {
            $dbConnection = null;
            foreach (self::MOD_KEYS as $modKey) {
                $dbConnection = $server->getDbConnection($modKey);
                if ($dbConnection) {
                    break;
                }
            }
            if (!$dbConnection) {
                continue;
            }

            $result[] = [
                'server' => $server,
                'dbname' => $dbConnection->dbname,
                'prefix' => $this->getPrefix($dbConnection->dbname),
            ];
        }

        return $result;
    }

    /**
     * @return array{admins: array, server: Server}[]
     */
    public function getActiveAdminsByServer(?int $serverId = null): array
    {
        $amxServers = $this->getAmxServers();
        $result = [];
        $dbCache = [];

        foreach ($amxServers as $entry) {
            $server = $entry['server'];

            if ($serverId !== null && $server->id !== $serverId) {
                continue;
            }

            $dbKey = $entry['dbname'];
            $prefix = $entry['prefix'];

            if (!isset($dbCache[$dbKey])) {
                try {
                    $db = db($dbKey);

                    $dbCache[$dbKey] = [
                        'admins' => $db->select()
                            ->from($prefix . 'amxadmins')
                            ->where(static function ($q) {
                                $q->where('expired', 0)->orWhere('expired', '>', time());
                            })
                            ->fetchAll(),
                        'serverAdminIds' => $this->loadServerAdminMapping($db, $prefix),
                        'amxServerMap' => $this->loadAmxServerMap($db, $prefix),
                    ];
                } catch (\Throwable $e) {
                    logs('modules')->warning('AmxPrivileges: failed to read DB ' . $dbKey, [
                        'error' => $e->getMessage(),
                    ]);
                    $dbCache[$dbKey] = [
                        'admins' => [],
                        'serverAdminIds' => [],
                        'amxServerMap' => [],
                    ];
                }
            }

            $cached = $dbCache[$dbKey];
            $allAdmins = $cached['admins'];
            $serverAdminIds = $cached['serverAdminIds'];
            $amxServerMap = $cached['amxServerMap'];

            $filteredAdmins = $this->filterAdminsByServer(
                $allAdmins,
                $server,
                $serverAdminIds,
                $amxServerMap
            );

            if (!empty($filteredAdmins)) {
                $showHidden = config('amxprivileges.show_hidden', false);
                $enriched = [];
                foreach ($filteredAdmins as $admin) {
                    if (!$showHidden && ((int) ($admin['ashow'] ?? 1)) === 0) {
                        continue;
                    }
                    $enriched[] = $this->enrichAdmin($admin);
                }

                if (!empty($enriched)) {
                    $this->resolveAvatars($enriched);

                    $result[] = [
                        'admins' => $enriched,
                        'server' => $server,
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Load admin-to-server mapping from amx_admins_servers.
     * Returns [amx_server_id => [admin_id, ...]]
     */
    protected function loadServerAdminMapping($db, string $prefix): array
    {
        try {
            $rows = $db->select()
                ->from($prefix . 'admins_servers')
                ->fetchAll();

            $map = [];
            foreach ($rows as $row) {
                $sid = (int) $row['server_id'];
                $map[$sid][] = (int) $row['admin_id'];
            }

            return $map;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Load AMX server info to map address -> amx_server_id.
     * Returns [ip:port => amx_server_id]
     */
    protected function loadAmxServerMap($db, string $prefix): array
    {
        try {
            $rows = $db->select()
                ->from($prefix . 'serverinfo')
                ->fetchAll();

            $map = [];
            foreach ($rows as $row) {
                $address = trim((string) ($row['address'] ?? ''));
                if (!empty($address)) {
                    $map[$address] = (int) $row['id'];
                }
            }

            return $map;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Filter admins for a specific Flute server using amx_admins_servers binding.
     * Falls back to all admins if no mapping exists.
     */
    protected function filterAdminsByServer(
        array $allAdmins,
        Server $server,
        array $serverAdminIds,
        array $amxServerMap
    ): array {
        if (empty($serverAdminIds)) {
            return $allAdmins;
        }

        $address = $server->ip . ':' . $server->port;
        $amxServerId = $amxServerMap[$address] ?? null;

        if ($amxServerId === null) {
            return $allAdmins;
        }

        $allowedAdminIds = $serverAdminIds[$amxServerId] ?? null;

        if ($allowedAdminIds === null) {
            return $allAdmins;
        }

        return array_filter($allAdmins, static function ($admin) use ($allowedAdminIds) {
            return in_array((int) $admin['id'], $allowedAdminIds, true);
        });
    }

    protected function enrichAdmin(array $admin): array
    {
        $expired = (int) ($admin['expired'] ?? 0);
        $created = (int) ($admin['created'] ?? 0);
        $isForever = $expired === 0;

        $daysLeft = null;
        $progress = 100;
        if (!$isForever && $expired > 0) {
            $daysLeft = max(0, (int) ceil(($expired - time()) / 86400));
            $total = $expired - $created;
            $remaining = $expired - time();
            $progress = $total > 0 ? max(0, min(100, (int) round($remaining / $total * 100))) : 100;
        }

        $nickname = trim((string) ($admin['nickname'] ?? ($admin['username'] ?? '')));
        $steamid = trim((string) ($admin['steamid'] ?? ''));
        $access = preg_replace('/[^a-z]/i', '', (string) ($admin['access'] ?? ''));
        $flags = preg_replace('/[^a-z]/i', '', (string) ($admin['flags'] ?? ''));
        $isSensitiveId = (bool) filter_var($steamid, FILTER_VALIDATE_IP);

        return [
            'id' => (int) $admin['id'],
            'steamid' => $steamid,
            'isSensitiveId' => $isSensitiveId,
            'nickname' => $nickname,
            'access' => $access,
            'flags' => $flags,
            'created' => $created,
            'expired' => $expired,
            'isForever' => $isForever,
            'daysLeft' => $daysLeft,
            'progress' => $progress,
            'hasPassword' => !empty($admin['password']),
            'usesPassword' => str_contains($flags, 'a') || str_contains($flags, 'e'),
            'ashow' => (int) ($admin['ashow'] ?? 1),
            'privilegeName' => $this->resolvePrivilegeName($access),
            'avatar' => null,
        ];
    }

    protected function resolvePrivilegeName(string $access): string
    {
        $customRules = $this->getCustomPrivilegeNames();

        if (!empty($customRules)) {
            $sortedKeys = array_keys($customRules);
            usort($sortedKeys, static fn($a, $b) => strlen($b) - strlen($a));

            foreach ($sortedKeys as $pattern) {
                if ($this->matchesFlags($access, $pattern)) {
                    return $customRules[$pattern];
                }
            }
        }

        return $this->resolvePrivilegeNameDefault($access);
    }

    protected function matchesFlags(string $access, string $pattern): bool
    {
        if (empty($pattern)) {
            return false;
        }

        $patternChars = str_split(strtolower($pattern));
        $accessLower = strtolower($access);

        foreach ($patternChars as $char) {
            if (!str_contains($accessLower, $char)) {
                return false;
            }
        }

        return strlen($access) <= strlen($pattern) + 2;
    }

    protected function resolvePrivilegeNameDefault(string $access): string
    {
        if (empty($access)) {
            return __('amxprivileges.privilege_names.none');
        }

        $len = strlen($access);

        if (str_contains($access, 'z') || $len >= 20) {
            return __('amxprivileges.privilege_names.root');
        }
        if (str_contains($access, 'm')) {
            return __('amxprivileges.privilege_names.rcon_admin');
        }
        if ($len >= 10) {
            return __('amxprivileges.privilege_names.admin');
        }
        if (str_contains($access, 'd') || str_contains($access, 'c')) {
            return __('amxprivileges.privilege_names.moderator');
        }
        if (str_contains($access, 'b') && $len <= 3) {
            return __('amxprivileges.privilege_names.vip');
        }

        return __('amxprivileges.privilege_names.custom');
    }

    protected function getCustomPrivilegeNames(): array
    {
        if ($this->customPrivilegeNames !== null) {
            return $this->customPrivilegeNames;
        }

        $raw = config('amxprivileges.privilege_names', '');
        $this->customPrivilegeNames = [];

        if (empty($raw) || !is_string($raw)) {
            return $this->customPrivilegeNames;
        }

        $lines = preg_split('/\r?\n/', $raw);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || !str_contains($line, '=')) {
                continue;
            }

            [$flags, $name] = explode('=', $line, 2);
            $flags = preg_replace('/[^a-z]/i', '', trim($flags));
            $name = trim($name);

            if (!empty($flags) && !empty($name)) {
                $this->customPrivilegeNames[$flags] = $name;
            }
        }

        return $this->customPrivilegeNames;
    }

    /**
     * @param array<int, array> &$admins
     */
    protected function resolveAvatars(array &$admins): void
    {
        $steamIds = [];
        foreach ($admins as $admin) {
            $sid = $admin['steamid'];
            if (!empty($sid) && preg_match('/^STEAM_/', $sid)) {
                $steamIds[] = $sid;
            }
        }

        if (empty($steamIds)) {
            return;
        }

        try {
            $steamInfoMap = steam()->getUsersInfo($steamIds);
        } catch (\Throwable $e) {
            return;
        }

        foreach ($admins as &$admin) {
            $sid = $admin['steamid'];
            if (isset($steamInfoMap[$sid])) {
                $info = $steamInfoMap[$sid];
                $admin['avatar'] = $info['avatar'] ?? null;
                if (empty($admin['nickname']) && !empty($info['name'])) {
                    $admin['nickname'] = $info['name'];
                }
            }
        }
    }

    /**
     * Get all privileges for a specific SteamID across all AMX servers.
     * Used for the profile tab.
     *
     * @return array<int, array>
     */
    public function getPrivilegesForSteamId(string $steamId): array
    {
        $variants = $this->getSteamIdVariants($steamId);
        $amxServers = $this->getAmxServers();
        $result = [];
        $seen = [];

        foreach ($amxServers as $entry) {
            $dbKey = $entry['dbname'];

            if (isset($seen[$dbKey])) {
                continue;
            }
            $seen[$dbKey] = true;

            try {
                $db = db($dbKey);
                $prefix = $entry['prefix'];

                $query = $db->select()->from($prefix . 'amxadmins');
                $query->where(static function ($q) use ($variants) {
                    foreach ($variants as $i => $v) {
                        if ($i === 0) {
                            $q->where('steamid', $v);
                        } else {
                            $q->orWhere('steamid', $v);
                        }
                    }
                });
                $rows = $query->fetchAll();

                foreach ($rows as $row) {
                    $expired = (int) ($row['expired'] ?? 0);
                    $isForever = $expired === 0;
                    $isActive = $isForever || $expired > time();

                    $enriched = $this->enrichAdmin($row);
                    $enriched['isActive'] = $isActive;
                    $enriched['serverName'] = transValue($entry['server']->name);
                    $enriched['serverId'] = $entry['server']->id;

                    $result[] = $enriched;
                }
            } catch (\Throwable $e) {
                logs('modules')->warning('AmxPrivileges: failed to query for steamid', [
                    'steamid' => $steamId,
                    'db' => $dbKey,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (!empty($result)) {
            $this->resolveAvatars($result);
        }

        return $result;
    }

    /**
     * Change the password for a specific admin by SteamID.
     */
    public function changePassword(string $steamId, string $newPassword): bool
    {
        $variants = $this->getSteamIdVariants($steamId);
        $amxServers = $this->getAmxServers();
        $updated = false;

        $seen = [];
        foreach ($amxServers as $entry) {
            $dbKey = $entry['dbname'];
            if (isset($seen[$dbKey])) {
                continue;
            }
            $seen[$dbKey] = true;

            try {
                $db = db($dbKey);
                $prefix = $entry['prefix'];

                foreach ($variants as $v) {
                    $admin = $db->select()
                        ->from($prefix . 'amxadmins')
                        ->where('steamid', $v)
                        ->fetchAll();

                    if (!empty($admin)) {
                        $db->update($prefix . 'amxadmins', ['password' => $newPassword])
                            ->where('steamid', $v)
                            ->run();
                        $updated = true;
                    }
                }
            } catch (\Throwable $e) {
                logs('modules')->warning('AmxPrivileges: failed to change password', [
                    'steamid' => $steamId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $updated;
    }

    /**
     * Generate STEAM_0 and STEAM_1 variants for a SteamID.
     * AMX databases may store either format.
     *
     * @return string[]
     */
    protected function getSteamIdVariants(string $steamId): array
    {
        if (preg_match('/^STEAM_(\d):(\d):(\d+)$/', $steamId, $m)) {
            return [
                'STEAM_0:' . $m[2] . ':' . $m[3],
                'STEAM_1:' . $m[2] . ':' . $m[3],
            ];
        }

        return [$steamId];
    }

    public function getAdminById(int $serverId, int $adminId): ?array
    {
        $amxServers = $this->getAmxServers();

        foreach ($amxServers as $entry) {
            if ($entry['server']->id !== $serverId) {
                continue;
            }

            try {
                $db = db($entry['dbname']);
                $prefix = $entry['prefix'];

                $rows = $db->select()
                    ->from($prefix . 'amxadmins')
                    ->where('id', $adminId)
                    ->fetchAll();

                if (empty($rows)) {
                    return null;
                }

                $row = $rows[0];
                if (!config('amxprivileges.show_hidden', false) && ((int) ($row['ashow'] ?? 1)) === 0) {
                    return null;
                }

                $enriched = $this->enrichAdmin($row);
                $enriched['username'] = $row['username'] ?? '';
                $enriched['days'] = (int) ($row['days'] ?? 0);
                $enriched['server'] = $entry['server'];

                $arr = [$enriched];
                $this->resolveAvatars($arr);
                $enriched = $arr[0];

                return $enriched;
            } catch (\Throwable $e) {
                logs('modules')->warning('AmxPrivileges: failed to find admin', [
                    'server_id' => $serverId,
                    'admin_id' => $adminId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return null;
    }

    protected function getPrefix(string $dbname): string
    {
        $configPrefix = config("database.databases.{$dbname}.prefix");

        if (!empty($configPrefix)) {
            return '';
        }

        return 'amx_';
    }
}
