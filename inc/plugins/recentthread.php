<?php
	
/* Hooks */

$plugins->add_hook("index_end", "recentthread_list_threads");
$plugins->add_hook("global_start", "recentthread_get_templates");

function recentthread_info()
{
	return array(
		"name"		=> "Recent Threads",
		"description"		=> "A plug-in that shows the most recent threads on the index.",
		"author"		=> "Mark Janssen",
		"version"		=> "3.0",
		"codename" 			=> "recentthreads",
		"compatibility"	=> "16*, 18*"
		);
}

function recentthread_install()
{
    global $db;
    // Add some settings
    $new_setting_group = array(
    "name" => "recentthreads",
    "title" => "Recent Threads Settings",
    "disporder" => 77,
    "isdefault" => 0
    );

    $gid = $db->insert_query("settinggroups", $new_setting_group);

    $new_setting[] = array(
    "name" => "recentthread_threadcount",
    "title" => "Number of Threads to show",
    "optionscode" => "text",
    "disporder" => 1,
    "value" => 15,
    "gid" => $gid
    );

    $new_setting[] = array(
    "name" => "recentthread_threadavatar",
    "title" => "Show thread starter\'s avatar",
    "optionscode" => "yesno",
    "disporder" => 2,
    "value" => 0,
    "gid" => $gid
    );

    $new_setting[] = array(
    "name" => "recentthread_lastavatar",
    "title" => "Show last poster\'s avatar",
    "optionscode" => "yesno",
    "disporder" => 3,
    "value" => 0,
    "gid" => $gid
    );

    $new_setting[] = array(
    "name" => "recentthread_forumskip",
    "title" => "Forums To Ignore",
    "description" => "The fids of the forums to ignore.  Separate with a comma.",
    "optionscode" => "text",
    "disporder" => 4,
    "gid" => $gid
    );

    foreach($new_setting as $array => $content)
    {
        $db->insert_query("settings", $content);
    }
    rebuild_settings();
}

function recentthread_is_installed()
{
    global $db;
    $query = $db->simple_select("settinggroups", "*", "name='recentthreads'");
    if($db->num_rows($query))
    {
        return TRUE;
    }
    return FALSE;
}

function recentthread_activate()
{
    global $db;
    $new_template['recentthread'] = '<table border="0" cellspacing="1" cellpadding="6" class="tborder" style="clear: both;">
    <tr>
    <th class="thead" colspan="4" style="text-align:left; font-size: 10pt;">~ Recent Threads ~</th>
    </tr>
    </table>
    <div class="scroll">
    <table border="0" cellspacing="1" cellpadding="6" class="tborder">
    <tr>
    <td class="tcat" width="230" style="font-size: 9pt; text-align: center;"><strong>Thread / Author</strong></td>
    <td class="tcat" width="30" style="font-size: 9pt; text-align: center;"><strong>Posts</strong></td>
    <td class="tcat" width="30" style="font-size: 9pt; text-align: center;"><strong>Views</strong></td>
    <td class="tcat" width="140" style="font-size: 9pt; text-align: center;"><strong>Last Post</strong></td>
    </tr>
    {$recentthreads}
    </table>';

    $new_template['recentthread_thread'] = '<tr>
    <td class="trow1"><a href="{$threadlink}">{$thread[\'subject\']}</a><br />{$thread[\'author\']}<br />{$posteravatar}</td>
    <td class="trow1">{$thread[\'replies\']}</td>
    <td class="trow1">{$thread[\'views\']}</td>
    <td class="trow1">{$lastposttime}<br />
    <a href="{$lastpostlink}">Last Post:</a> {$lastposterlink}<br />{$lastavatar}</td>
    </tr>';

    $new_template['recentthread_avatar'] = '<img src="{$avatarurl}" {$dimensions} />';

    foreach($new_template as $title => $template)
	{
		$new_template = array('title' => $db->escape_string($title), 'template' => $db->escape_string($template), 'sid' => '-1', 'version' => '1600', 'dateline' => TIME_NOW);
		$db->insert_query('templates', $new_template);
	}
}

function recentthread_deactivate()
{
    global $db;
    $db->delete_query("templates", "title IN('recentthread','recentthread_thread','recentthread_avatar')");
}

function recentthread_uninstall()
{
    global $db;
    $query = $db->simple_select("settinggroups", "gid", "name='recentthreads'");
    $gid = $db->fetch_field($query, "gid");
    if(!$gid)
    {
        return;
    }
    $db->delete_query("settinggroups", "name='recentthreads'");
    $db->delete_query("settings", "gid=$gid");
    rebuild_settings();
}

function recentthread_list_threads()
{
	global $mybb, $db, $templates, $recentthreadtable, $recentthreads;
	require_once MYBB_ROOT."inc/functions_search.php";
    $threadlimit = (int) $mybb->settings['recentthread_threadcount'];
    if(!$threadlimit) // Provide a fallback
    {
	    $threadlimit = 15;
    }
	$fpermissions = forum_permissions();
	$onlyusfids = array();
		// Check group permissions if we can't view threads not started by us
		$group_permissions = forum_permissions();
		foreach($group_permissions as $fid => $forum_permissions)
		{
			if($forum_permissions['canonlyviewownthreads'] == 1)
			{
				$onlyusfids[] = $fid;
			}
		}
		if(!empty($onlyusfids))
		{
			$where .= "AND ((t.fid IN(".implode(',', $onlyusfids).") AND t.uid='{$mybb->user['uid']}') OR t.fid NOT IN(".implode(',', $onlyusfids)."))";
		}
		// First get the unviewable forums
	$unviewableforums = get_unsearchable_forums();
	$approved = 0;
	
	// Moderators can view unapproved threads
	if($mybb->usergroup['canmodcp']==1) {
		$approved = -1;
	}
	$unsearchableforums = get_unsearchable_forums();
    if($unsearchableforums)
    {
        $unsearchableforums = " AND t.fid NOT IN ($unsearchableforums) ";
    }
    // Take into account any ignored forums
    if($mybb->settings['recentthread_forumskip'])
    {
        $ignoreforums = " AND t.fid NOT IN(" . $mybb->settings['recentthread_forumskip'] . ") ";
    }
	$query = $db->query("
			SELECT t.*, u.username AS userusername, u.usergroup, u.displaygroup, u.avatar as threadavatar, u.avatardimensions as threaddimensions, lp.usergroup AS lastusergroup, lp.avatar as lastavatar, lp.avatardimensions as lastdimensions, lp.displaygroup as lastdisplaygroup
			FROM ".TABLE_PREFIX."threads t
			LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=t.uid)
			LEFT JOIN ".TABLE_PREFIX."users lp ON (t.lastposteruid=lp.uid)
			WHERE 1=1 $where AND t.visible > {$approved} {$unsearchableforums} {$ignoreforums}
			ORDER BY t.lastpost DESC
			LIMIT $threadlimit
		");
		while($thread = $db->fetch_array($query))
		{
            $threadlink = get_thread_link($thread['tid'], "", "newpost");
            $lastpostlink = get_thread_link($thread['tid'], "", "lastpost");
			$lastpostdate = my_date($mybb->settings['dateformat'], $thread['lastpost']);
			$lastposttime = my_date($mybb->settings['timeformat'], $thread['lastpost']);
			$lastposter = $thread['lastposter'];
			$lastposteruid = $thread['lastposteruid'];
			$thread['author'] = build_profile_link(format_name($thread['userusername'], $thread['usergroup'], $thread['displaygroup']), $thread['uid']);
			// Don't link to guest's profiles (they have no profile).
			if($lastposteruid == 0)
			{
				$lastposterlink = $lastposter;
			}
			else
			{
				$lastposterlink = build_profile_link(format_name($lastposter, $thread['lastusergroup'], $thread['lastdisplaygroup']), $lastposteruid);
			}
            if($mybb->settings['recentthread_threadavatar'])
            {
                $threadavatar = format_avatar($thread['threadavatar'], $thread['threaddimensions']);
                $avatarurl = $threadavatar['image'];
                $dimensions = $threadavatar['width_height'];
                eval("\$posteravatar = \"".$templates->get("recentthread_avatar")."\";");
            }
            if($mybb->settings['recentthread_lastavatar'])
            {
                $lastposteravatar = format_avatar($thread['lastavatar'], $thread['lastdimensions']);
                $avatarurl = $lastposteravatar['image'];
                $dimensions = $lastposteravatar['width_height'];
                eval("\$lastavatar = \"".$templates->get("recentthread_avatar")."\";");
            }
			eval("\$recentthreads .= \"".$templates->get("recentthread_thread")."\";");
            unset($posteravatar);
            unset($lastavatar);
		}
        eval("\$recentthreadtable = \"".$templates->get("recentthread")."\";");
}

function recentthread_get_templates()
{
    global $templatelist;
    $templatelist .= ",recentthread,recentthread_thread";
}

?>
