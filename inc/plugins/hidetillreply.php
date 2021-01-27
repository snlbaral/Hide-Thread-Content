<?php
//disallow unauthorize access
if(!defined("IN_MYBB")) {
	die("You are not authorize to view this");
}
$plugins->add_hook('showthread_start', 'hidetillreply_start');
$plugins->add_hook('postbit', 'hidetillreply_hide');

//Plugin Information
function hidetillreply_info()
{
	return array(
		'name' => 'Hide Thread Content',
		'author' => 'Sunil Baral',
		'website' => 'https://github.com/snlbaral',
		'description' => 'This plugin will hide thread content until user don\'t reply',
		'version' => '1.0',
		'compatibility' => '18*',
		'guid' => '',
	);
}


//Activate Plugin
function hidetillreply_activate()
{
	global $db, $mybb, $settings;

	//Admin CP Settings
	$hidetillreply_group = array(
		'gid' => (int)'',
		'name' => 'hidetillreply',
		'title' => 'Hide Thread Content',
		'description' => 'Settings for Hide Thread Content',
		'disporder' => '1',
		'isdefault' =>  '0',
	);
	$db->insert_query('settinggroups',$hidetillreply_group);
	$gid = $db->insert_id();

	//Enable or Disable
	$hidetillreply_enable = array(
		'sid' => 'NULL',
		'name' => 'hidetillreply_enable',
		'title' => 'Do you want to enable this plugin?',
		'description' => 'If you set this option to yes, this plugin will start working.',
		'optionscode' => 'yesno',
		'value' => '1',
		'disporder' => 1,
		'gid' => intval($gid),
	);

	//Allowed User Group
	$hidetillreply_allowed_group = array(
		'sid' => 'NULL',
		'name' => 'hidetillreply_allowed_group',
		'title' => 'Which groups can use this plugin?',
		'description' => 'Add gid of group that will be able to use this plugin.',
		'optionscode' => 'groupselect',
		'value' => '3,4,6',
		'disporder' => 1,
		'gid' => intval($gid),
	);

	$db->insert_query('settings',$hidetillreply_enable);
	$db->insert_query('settings',$hidetillreply_allowed_group);
	rebuild_settings();
}

//Deactivate Plugin
function hidetillreply_deactivate()
{
	global $db, $mybb, $settings;
	$db->query("DELETE from ".TABLE_PREFIX."settinggroups WHERE name IN ('hidetillreply')");
	$db->query("DELETE from ".TABLE_PREFIX."settings WHERE name IN ('hidetillreply_enable')");
	$db->query("DELETE from ".TABLE_PREFIX."settings WHERE name IN ('hidetillreply_allowed_group')");
	rebuild_settings();
}

function hidetillreply_start()
{
	global $db, $mybb, $mainpostpid;
	//Thread tid
	$maintid = (int)$mybb->input['tid'];
	$query = $db->simple_select("threads","*","tid='$maintid'");
	$row = $db->fetch_array($query);
	//Get the post id of thread
	$mainpostpid = (int)$row['firstpost'];
}


function hidetillreply_hide(&$post)
{
	global $db, $mybb, $settings, $mainpostpid;

	//If Plugin is enabled
    if($settings['hidetillreply_enable'] == 1) {

		//We are just modifying first post in thread
		if((int)$post['pid']===$mainpostpid) {
			$allowed_group = explode(',', $mybb->settings['hidetillreply_allowed_group']);
			$usergroup = $mybb->user['usergroup'];
			$postusergroup = $post['usergroup'];
			//If post is from allowed usergroup to hide content or user is guest
			if($usergroup==1 || in_array($postusergroup, $allowed_group) || in_array('-1', $allowed_group)) {
				//If user is guest
				if($usergroup==1) {
					$post['message'] = "<div class='red_alert'><b>Please login to unlock this content.</b></div>";
				} else {
					$maintid = (int)$post['tid'];
					$mainuid = (int)$mybb->user['uid'];
					$query = $db->simple_select("posts","*","tid='$maintid' AND uid='$mainuid'");
					$rows = $db->num_rows($query);
					//If user has replied to the thread return else hide
					if($rows>0) {
					} else {
						$post['message'] = "<div class='red_alert'><b>Please reply to this thread to unlock the content.</b></div>";			
					}
				}
			}
		}
	}

}