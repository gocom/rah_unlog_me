<?php

/**
 * Rah_unlog_me plugin for Textpattern CMS
 *
 * @author Jukka Svahn
 * @date 2009-
 * @license GNU GPLv2
 * @link http://rahforum.biz/plugins/rah_unlog_me
 *
 * Copyright (C) 2012 Jukka Svahn <http://rahforum.biz>
 * Licensed under GNU Genral Public License version 2
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

	if(@txpinterface == 'admin') {
		rah_unlog_me_installer();
		rah_unlog_me();
		add_privs('plugin_prefs.rah_unlog_me', '1,2');
		register_callback('rah_unlog_me_prefs', 'plugin_prefs.rah_unlog_me');
		register_callback('rah_unlog_me_installer', 'plugin_lifecycle.rah_unlog_me');
	}

/**
 * Does installing and uninstalling.
 * @param string $event The admin-side event.
 * @param string $step The admin-side, plugin-lifecycle step.	
 */

	function rah_unlog_me_installer($event='', $step='') {
		
		if($step == 'deleted') {
			safe_delete(
				'txp_prefs',
				"name LIKE 'rah\_unlog\_me\_%'"
			);
			return;
		}
		
		global $prefs, $event, $textarray;
		
		if($event == 'prefs') {
			foreach(
				array(
					'rah_unlog_me_auto' => 'Exclude site authors\' IPs from the logs?',
					'rah_unlog_me_ip' => 'Comma separated list of additional IPs to exclude from the logs',
				) as $string => $translation
			)
				if(!isset($textarray[$string]))
					$textarray[$string] = $translation;
		}

		if(
			isset($prefs['rah_unlog_me_auto']) &&
			isset($prefs['rah_unlog_me_ip'])
		)
			return;
			
		/*
			Run migration and clean-up if older version was
			installed
		*/
		
		@$rs = 
			safe_rows(
				'*',
				'rah_unlog_me',
				'1=1'
			);
		
		$default['auto'] = $default['ip'] = '';
		
		if($rs && is_array($rs)) {
			foreach($rs as $a)
				$default[$a['name']] = $a['value'];
			
			@safe_query(
				'DROP TABLE IF EXISTS '.safe_pfx('rah_unlog_me')
			);
		}
		
		if(!isset($prefs['rah_unlog_me_auto']))
			safe_insert(
				'txp_prefs',
				"prefs_id=1,
				name='rah_unlog_me_auto',
				val=".($default['auto'] == 'no' ? 0 : 1).",
				type=0,
				event='publish',
				html='yesnoradio',
				position=221"
			);
		
		if(!isset($prefs['rah_unlog_me_ip']))
			safe_insert(
				'txp_prefs',
				"prefs_id=1,
				name='rah_unlog_me_ip',
				val='".doSlash($default['ip'])."',
				type=0,
				event='publish',
				html='text_input',
				position=222"
			);
		
		$prefs['rah_unlog_me_ip'] = $default['ip'];
		$prefs['rah_unlog_me_auto'] = 1;
	}

/**
 * Removes IPs from the logs
 */

	function rah_unlog_me() {
		global $prefs, $event;
		
		if($prefs['logging'] == 'none')
			return;
		
		if($prefs['rah_unlog_me_auto'] == 1)
			safe_delete(
				'txp_log',
				"ip='".doSlash(remote_addr())."'"
			);
		
		if($event != 'log' || !trim($prefs['rah_unlog_me_ip']))
			return;
		
		$ips = quote_list(doArray(explode(',',$prefs['rah_unlog_me_ip']), 'trim'));
		
		safe_delete(
			'txp_log',
			'ip in('.implode(', ',$ips).')'
		);
	}

/**
 * Redirects to the preferences panel
 */

	function rah_unlog_me_prefs() {
		header('Location: ?event=prefs#prefs-logging');
		echo 
			'<p id="message">'.n.
			'	<a href="?event=prefs#prefs-logging">'.gTxt('continue').'</a>'.n.
			'</p>';
	}
?>