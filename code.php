<?php	##################
	#
	#	rah_unlog_me-plugin for Textpattern
	#	version 0.1
	#	by Jukka Svahn
	#	http://rahforum.biz
	#
	###################

	if (@txpinterface == 'admin') {
		rah_unlog_me_install();
		rah_unlog_me_do();
		add_privs('rah_unlog_me', '1,2');
		register_tab("extensions", "rah_unlog_me","Unlog me");
		register_callback("rah_unlog_me", "rah_unlog_me");
	}

	function rah_unlog_me_install() {
		safe_query(
			"CREATE TABLE IF NOT EXISTS ".safe_pfx('rah_unlog_me')." (
				`name` varchar(255) NOT NULL default '',
				`value` longtext NOT NULL default '',
				PRIMARY KEY (`name`)
			)"
		);
		if(safe_count('rah_unlog_me',"name='ip'") == 0) safe_insert('rah_unlog_me',"name='ip', value=''");
		if(safe_count('rah_unlog_me',"name='auto'") == 0) safe_insert('rah_unlog_me',"name='auto', value='no'");
	}

	function rah_unlog_me_do() {
		@$ips = fetch('value','rah_unlog_me','name','ip');
		@$auto = fetch('value','rah_unlog_me','name','auto');
		if($ips) {
			$ips = explode(',',$ips);
			foreach($ips as $ip) {
				$ip = doSlash(trim($ip));
				safe_delete(
					'txp_log',"ip='$ip'"
				);
			}
		}
		if($auto == 'yes' && isset($_SERVER['REMOTE_ADDR'])) {
			safe_delete(
				'txp_log',"ip='".doSlash($_SERVER['REMOTE_ADDR'])."'"
			);
		}
	}

	function rah_unlog_me() {
		global $step;
		require_privs('rah_unlog_me');
		if(in_array($step,array(
			'rah_unlog_me_save'
		))) $step();
		else rah_unlog_me_form();
	}

	function rah_unlog_me_form($message='') {
		pagetop('Unlog IPs',$message);
		$value = fetch('value','rah_unlog_me','name','ip');
		$auto = fetch('value','rah_unlog_me','name','auto');
		echo n.
			'	<form method="post" action="index.php" style="width:950px;margin:0 auto;">'.n.
			'		<h1><strong>rah_unlog_me</strong> | Unlog IPs</h1>'.n.
			'		<p>&#187; <a href="?event=plugin&amp;step=plugin_help&amp;name=rah_unlog_me">Documentation</a></p>'.n.
			'		<p><label for="rah_unlog_me_value">Unloged IPs:</label></p>'.n.
			'		<p><textarea name="rah_unlog_me_value" id="rah_unlog_me_value" cols="150" rows="20">'.htmlspecialchars($value).'</textarea></p>'.n.
			'		<p>Define IPs that you don\'t want to log to the field above. Comma (<code>,</code>) seperated list if multiple.'.
			(
				(isset($_SERVER['REMOTE_ADDR'])) ? 
					' Currently your IP address returned is <code>'.$_SERVER['REMOTE_ADDR'].'</code>'
				:
					''
			).'</p>'.n.
			'		<p>'.n.
			'			<label for="rah_unlog_me_auto">Automatically remove authors\' IPs from logs when they login?</label>'.n.
			'			<select id="rah_unlog_me_auto" name="rah_unlog_me_auto">'.n.
			'				<option value="yes"'.(($auto == 'yes') ? ' selected="selected"' : '').'>Yes</option>'.n.
			'				<option value="no"'.(($auto == 'no') ? ' selected="selected"' : '').'>No</option>'.n.
			'			</select>'.n.
			'		</p>'.n.
			'		<input type="hidden" name="event" value="rah_unlog_me" />'.n.
			'		<input type="hidden" name="step" value="rah_unlog_me_save" />'.n.
			'		<p><input type="submit" name="" value="Save" class="publish" /></p>'.n.
			'	</form>'.n;
	}

	function rah_unlog_me_save() {
		extract(doSlash(gpsa(array(
			'rah_unlog_me_value',
			'rah_unlog_me_auto'
		))));
		safe_update(
			'rah_unlog_me',
			"value='$rah_unlog_me_value'",
			"name='ip'"
		);
		safe_update(
			'rah_unlog_me',
			"value='$rah_unlog_me_auto'",
			"name='auto'"
		);
		rah_unlog_me_form('Updated.');
	}?>