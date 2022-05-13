<?php

/*
 * rah_unlog_me - XML sitemap plugin for Textpattern CMS
 * https://github.com/gocom/rah_unlog_me
 *
 * Copyright (C) 2022 Jukka Svahn
 *
 * This file is part of rah_unlog_me.
 *
 * rah_unlog_me is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, version 2.
 *
 * rah_unlog_me is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with rah_unlog_me. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Plugin class.
 */
final class Rah_Unlog_Me
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_privs('plugin_prefs.rah_unlog_me', '1,2');
        add_privs('prefs.rah_unlog_me', '1,2');
        register_callback([$this, 'prefs'], 'plugin_prefs.rah_unlog_me');
        register_callback([$this, 'install'], 'plugin_lifecycle.rah_unlog_me', 'installed');
        register_callback([$this, 'uninstall'], 'plugin_lifecycle.rah_unlog_me', 'deleted');
        register_callback([$this, 'prevent'], 'log_hit');
    }

    /**
     * Installer.
     */
    public function install(): void
    {
        $options = [
            'rah_unlog_me_auto' => ['yesnoradio', 1],
            'rah_unlog_me_ip' => ['pref_longtext_input', ''],
        ];

        $position = 221;

        foreach ($options as $name => $value) {
            create_pref($name, $value[1], 'rah_unlog_me', PREF_PLUGIN, $value[0], $position++);
        }
    }

    /**
     * Uninstaller.
     */
    public function uninstall(): void
    {
        remove_pref(null, 'rah_unlog_me');
    }

    /**
     * Prevent hits from being logged.
     */
    public function prevent(): void
    {
        global $logging, $nolog;

        if ($logging === 'none' || $nolog) {
            return;
        }

        if ((get_pref('rah_unlog_me_auto') && is_logged_in())
            || $this->isAddressExcluded()
        ) {
            $nolog = true;
        }
    }

    /**
     * Options panel.
     *
     * Redirects to preferences.
     */
    public function prefs(): void
    {
        header('Location: ?event=prefs#prefs_group_rah_unlog_me');
        echo graf(href(gTxt('continue'), ['href' => '?event=prefs#prefs_group_rah_unlog_me']));
    }

    /**
     * Whether the current address is excluded from logs.
     *
     * @return bool
     */
    private function isAddressExcluded(): bool
    {
        $ips = quote_list(do_list(get_pref('rah_unlog_me_ip')));
        $remoteAddress = doSlash(remote_addr());
        $joinBy = sprintf(" OR '%s' LIKE ", $remoteAddress);

        $sql = sprintf(
            "SELECT '%s' LIKE %s",
            $remoteAddress,
            implode($joinBy, $ips)
        );

        return (bool) getThing($sql);
    }
}
