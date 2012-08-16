<?php
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("usercp_usergroups_join_group", "pog_group_join");
$plugins->add_hook("managegroup_do_joinrequests_start", "pog_group_joinrequest");

function pog_info()
{
	return array(
		"name"			=> "PM on Groupjoin",
		"description"	=> "Schreibt eine PN an Nutzer welche einer neuen Gruppe beitreten",
		"website"		=> "http://mybbdemo.tk/",
		"author"		=> "Jones",
		"authorsite"	=> "http://mybbdemo.tk",
		"version"		=> "1.0",
		"guid" 			=> "",
		"compatibility" => "*"
	);
}

function pog_activate()
{
	global $db;

    $group = array(
        "name" => "pog",
        "title" => "PM on Groupjoin",
        "description" => "",
        "disporder" => "1",
        "isdefault" => "0",
        );
    $gid = $db->insert_query("settinggroups", $group);

    $setting = array(
        "name" => "pog_id",
        "title" => "Welcher Nutzer soll als Absender fungieren? (ID)",
        "optionscode" => "text",
        "value" => "1",
        "disporder" => "1",
        "gid" => intval($gid),
        );
    $db->insert_query("settings", $setting);

    $setting = array(
        "name" => "pog_subject",
        "title" => "PN Title",
        "description" => "{user} -> Benutzername<br />{group} -> Gruppenname",
        "optionscode" => "text",
        "value" => "Du wurdest zu {group} hinzugefügt",
        "disporder" => "2",
        "gid" => intval($gid),
        );
    $db->insert_query("settings", $setting);

    $setting = array(
        "name" => "pog_message",
        "title" => "PN Nachricht",
        "description" => "{user} -> Benutzername<br />{group} -> Gruppenname",
        "optionscode" => "textarea",
        "value" => "Hi {user},\nDu wurdest zu der Benutzergruppe {group} hinzugefügt.\nGruß,\ndas Team",
        "disporder" => "3",
        "gid" => intval($gid),
        );
    $db->insert_query("settings", $setting);
    rebuild_settings();

}

function pog_deactivate()
{
    global $db;
    $query = $db->simple_select("settinggroups", "gid", "name='pog'");
    $g = $db->fetch_array($query);
    $db->delete_query("settinggroups", "gid='".$g['gid']."'");
    $db->delete_query("settings", "gid='".$g['gid']."'");
    rebuild_settings();
}

function pog_group_join()
{
	global $mybb, $usergroup;
	
	pog_pm($mybb->user, $usergroup);
}

function pog_group_joinrequest()
{
	global $mybb, $groupscache, $gid;

	if(is_array($mybb->input['request'])) {
		foreach($mybb->input['request'] as $uid => $what) {
			if($what == "accept") {
				$user = get_user($uid);
				pog_pm($user, $groupscache[$gid]);
			}
		}
	}
}

function pog_pm($user, $group)
{
	global $mybb;
	
	$subject = str_replace("{user}", $user['username'], $mybb->settings['pog_subject']);
	$subject = str_replace("{group}", $group['title'], $subject);
	$message = str_replace("{user}", $user['username'], $mybb->settings['pog_message']);
	$message = str_replace("{group}", $group['title'], $message);
	//Write PM
	require_once MYBB_ROOT."inc/datahandlers/pm.php";
	$pmhandler = new PMDataHandler();

	$pm = array(
		"subject" => $subject,
		"message" => $message,
		"icon" => "",
		"fromid" => $mybb->settings['pog_id'],
		"do" => "",
		"pmid" => "",
	);
	$pm['toid'][] = $user['uid'];
	$pmhandler->set_data($pm);

	// Now let the pm handler do all the hard work.
	if($pmhandler->validate_pm())
	{
		$pminfo = $pmhandler->insert_pm();
	}else {
		$pm_errors = $pmhandler->get_friendly_errors();
		$send_errors = inline_error($pm_errors);
		echo $send_errors;
	}
}
?>