<?php	##################
	#
	#	Rah_unlog_me v1.2
	#	Plugin for Textpattern
	#	by Jukka Svahn
	#	http://rahforum.biz
	#
	#	Copyright (C) 2011 Jukka Svahn <http://rahforum.biz>
	#	Licensed under GNU Genral Public License version 2
	#	http://www.gnu.org/licenses/gpl-2.0.html
	#
	##################

	if(@txpinterface == 'admin') {
		rah_unlog_me();
		add_privs('plugin_prefs.rah_unlog_me','1,2');
		register_callback('rah_unlog_me_prefs','plugin_prefs.rah_unlog_me');
		register_callback('rah_unlog_me_installer','plugin_lifecycle.rah_unlog_me');
		register_callback('rah_unlog_me_head','admin_side','head_end');
	}

/**
	Fixing the pophelp links on the prefs panel.
*/

	function rah_unlog_me_head() {
		
		global $event;
		
		if($event != 'prefs')
			return;
		
		echo <<<EOF
			<script type="text/javascript">
				<!--
				$(document).ready(function(){
					$('tr#prefs-rah_unlog_me_auto a').
						attr('href','?event=plugin&step=plugin_help&name=rah_unlog_me#pref-auto').
						removeAttr('onclick')
					;
					$('tr#prefs-rah_unlog_me_ip a').
						attr('href','?event=plugin&step=plugin_help&name=rah_unlog_me#pref-ip').
						removeAttr('onclick')
					;
				});
				-->
			</script>
EOF;
	}

/**
	Does installing and uninstalling.
	@param $event string The admin-side event.
	@param $step string The admin-side, plugin-lifecycle step.	
*/

	function rah_unlog_me_installer($event='', $step='') {
		
		/*
			If called on plugin uninstall,
			deletes the preferences
		*/
		
		if($step == 'deleted') {
			safe_delete(
				'txp_prefs',
				"name in('rah_unlog_me_auto','rah_unlog_me_ip')"
			);
			return;
		}
		
		global $prefs, $event, $textarray;
		
		if($event == 'prefs') {
			
			/*
				Generate language strings if
				not existing
			*/
			
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
	Removes IPs from the logs
*/

	function rah_unlog_me() {
		
		rah_unlog_me_installer();
		
		global $prefs, $event;
		
		/*
			Logging is off, end here
		*/
		
		if($prefs['logging'] == 'none')
			return;
		
		/*
			Remove user's IP from logs when accessing
			admin-side
		*/
		
		if($prefs['rah_unlog_me_auto'] == 1)
			safe_delete(
				'txp_log',
				"ip='".doSlash(serverSet('REMOTE_ADDR'))."'"
			);
		
		/*
			Remove pre-defined list of IPs (if any)
			when accessing Logs
		*/
		
		if($event != 'log' || !trim($prefs['rah_unlog_me_ip']))
			return;
		
		foreach(explode(',',$prefs['rah_unlog_me_ip']) as $ip)
			$ips[] = "'".doSlash(trim($ip))."'";
		
		safe_delete(
			'txp_log',
			'ip in('.implode(', ',$ips).')'
		);
	}

/**
	Redirects to the preferences panel
*/

	function rah_unlog_me_prefs() {
		header('Location: ?event=prefs#prefs-logging');
		echo 
			'<p id="message">'.n.
			'	<a href="?event=prefs#prefs-logging">'.gTxt('continue').'</a>'.n.
			'</p>';
	}
?>