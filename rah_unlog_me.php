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

	new rah_unlog_me();

class rah_unlog_me {
	
	static public $version = '1.4';

	/**
	 * Constructor
	 */

	public function __construct() {
		add_privs('plugin_prefs.rah_unlog_me', '1,2');
		register_callback(array($this, 'prefs'), 'plugin_prefs.'.__CLASS__);
		register_callback(array(__CLASS__, 'install'), 'plugin_lifecycle.'.__CLASS__);
		$this->clean();
	}

	/**
	 * Does installing and uninstalling.
	 * @param string $event The admin-side event.
	 * @param string $step The admin-side, plugin-lifecycle step.	
	 */

	static public function install($event='', $step='') {
		
		global $prefs;
		
		if($step == 'deleted') {
			safe_delete(
				'txp_prefs',
				"name LIKE 'rah\_unlog\_me\_%'"
			);
			return;
		}

		if((string) get_pref(__CLASS__.'_version') === self::$version) {
			return;
		}
		
		$opt = 
			array(
				'auto' => array('yesnoradio', 1),
				'ip' => array('text_input', ''),
			);
		
		if(in_array(PFX.'rah_unlog_me', getThings('SHOW TABLES'))) {
			$rs = safe_rows('*', 'rah_unlog_me', '1=1');

			foreach($rs as $a) {
				if(!isset($opt[$a['name']])) {
					continue;
				}
				
				if($a['name'] == 'auto') {
					$a['value'] = $a['value'] == 'no' ? 0 : 1;
				}
				
				$opt[$a['name']][1] = $a['value'];
			}

			@safe_query('DROP TABLE IF EXISTS '.safe_pfx('rah_unlog_me'));
		}
		
		$position = 221;
		
		foreach($opt as $name => $val) {
			$n = __CLASS__.'_'.$name;
			
			if(!isset($prefs[$n])) {
				set_pref($n, $val[1], 'publish', PREF_BASIC, $val[0], $position);
				$prefs[$n] = $val[1];
			}
			
			$position++;
		}
		
		set_pref(__CLASS__.'_version', self::$version, __CLASS__, 2, '', 0);
		$prefs[__CLASS__.'_version'] = self::$version;
	}

	/**
	 * Removes IPs from the logs
	 */

	public function clean() {
		global $logging, $event;
		
		if($logging == 'none') {
			return;
		}
		
		if(get_pref('rah_unlog_me_auto')) {
			safe_delete(
				'txp_log',
				"ip='".doSlash(remote_addr())."'"
			);
		}
		
		if($event != 'log' || !trim(get_pref('rah_unlog_me_ip'))) {
			return;
		}
		
		$ips = quote_list(do_list(get_pref('rah_unlog_me_ip')));
		
		safe_delete(
			'txp_log',
			'ip LIKE '.implode(' OR ip LIKE ', $ips)
		);
	}

	/**
	 * Redirects to the preferences panel
	 */

	public function prefs() {
		header('Location: ?event=prefs#prefs-logging');
		echo 
			'<p>'.n.
			'	<a href="?event=prefs#prefs-logging">'.gTxt('continue').'</a>'.n.
			'</p>';
	}
}

?>