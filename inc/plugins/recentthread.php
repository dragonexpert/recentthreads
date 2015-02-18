<?php
	
/* Hooks */

$plugins->add_hook("index_end", "recentthread_list_threads");
$plugins->add_hook("global_start", "recentthread_get_templates");
$plugins->add_hook("global_intermediate", "recentthread_global_intermediate");
$plugins->add_hook("xmlhttp", "recentthread_refresh_threads");

function recentthread_info()
{
	return array(
		"name"		=> "Recent Threads",
		"description"		=> "A plug-in that shows the most recent threads on the index.",
		"author"		=> "Mark Janssen",
		"version"		=> "8.0",
		"codename" 			=> "recentthreads",
		"compatibility"	=> "18*"
		);
}

function recentthread_install()
{
    global $db, $mybb;

    if($mybb->version_code < 1801)
    {
        flash_message("Sorry, but this plugin requires you to update to 1.8.1 or higher.", "error");
        admin_redirect("index.php?module=config-plugins");
    }

    // Add some settings
    $new_setting_group = array(
    "name" => "recentthreads",
    "title" => "Recent Threads Settings",
    "description" => "Customize various aspects of recent threads",
    "disporder" => 77,
    "isdefault" => 0
    );

    $gid = $db->insert_query("settinggroups", $new_setting_group);

    $new_setting[] = array(
    "name" => "recentthread_threadcount",
    "title" => "Number of Threads",
    "description" => "How many threads are shown.",
    "optionscode" => "numeric",
    "disporder" => 1,
    "value" => 15,
    "gid" => $gid
    );

    $new_setting[] = array(
    "name" => "recentthread_threadavatar",
    "title" => $db->escape_string("Show thread starter's avatar"),
    "description" => $db->escape_string("If set to yes, the thread starter's avatar will be shown."),
    "optionscode" => "yesno",
    "disporder" => 2,
    "value" => 0,
    "gid" => $gid
    );

    $new_setting[] = array(
    "name" => "recentthread_lastavatar",
    "title" => $db->escape_string("Show last poster's avatar"),
    "description" => $db->escape_string("If set to yes, the last poster's avatar will be shown."),
    "optionscode" => "yesno",
    "disporder" => 3,
    "value" => 0,
    "gid" => $gid
    );

    $new_setting[] = array(
    "name" => "recentthread_forumskip",
    "title" => "Forums To Ignore",
    "description" => "The forums threads should not be pulled from.",
    "optionscode" => "forumselect",
    "disporder" => 4,
    "value" => "",
    "gid" => $gid
    );

    $new_setting[] = array(
    "name" => "recentthread_subject_length",
    "title" => "Max Title Length",
    "description" => "The amount of characters before the rest of the title is truncated. Enter 0 for no limit.",
    "optionscode" => "numeric",
    "disporder" => 5,
    "value" => 0,
    "gid" => $gid
    );

    $new_setting[] = array(
    "name" => "recentthread_subject_breaker",
    "title" => "Word Breaking",
    "description" => "If selected, the title will be kept to full words only in cut off.",
    "optionscode" => "yesno",
    "disporder" => 6,
    "value" => 0,
    "gid" => $gid
    );

    $new_setting[] = array(
    "name" => "recentthread_which_groups",
    "title" => "Permissions",
    "description" => "These groups cannot view the reccent threads on index.",
    "optionscode" => "groupselect",
    "disporder" => 7,
    "value" => 7,
    "gid" => $gid
    );

    $db->insert_query_multiple("settings", $new_setting);
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
    $new_template['recentthread'] = '<div class="scroll">
<div id="recentthreads">
<table border="0" cellspacing="1" cellpadding="6" class="tborder" style="clear: both;max-height:300px">
<thead>
    <tr>
    <td class="thead{$expthead}" colspan="4" style="text-align:left; font-size: 10pt;"><div class="expcolimage"><img src="{$theme[\'imgdir\']}/collapse.png" id="cat_9999_img" class="expander" alt="{$expaltext}" title="{$expaltext}" /></div>
<div>~ Recent Threads ~</div>
</td>
    </tr>
</thead>
<tbody style="{$expdisplay}" id="cat_9999_e">
    <tr>
    <td class="tcat" width="230" style="font-size: 9pt; text-align: center;"><strong>Thread / Author</strong></td>
    <td class="tcat" width="30" style="font-size: 9pt; text-align: center;"><strong>Forum</strong></td>
    <td class="tcat" width="30" style="font-size: 9pt; text-align: center;"><strong>Posts</strong></td>
    <td class="tcat" width="140" style="font-size: 9pt; text-align: center;"><strong>Last Post</strong></td>
    </tr>
    {$recentthreads}
</tbody>
    </table>
</div>
    </div>';

    $new_template['recentthread_thread'] = '<tr>
    <td class="{$trow}"><a href="{$threadlink}">{$thread[\'subject\']}</a><br />{$thread[\'author\']}<br />{$posteravatar}</td>
    <td class="{$trow}">{$thread[\'forum\']}</td>
    <td class="{$trow}"><a href="javascript:MyBB.whoPosted({$thread[\'tid\']});">{$thread[\'replies\']}</a></td>
    <td class="{$trow}">{$lastposttimeago}<br />
    <a href="{$lastpostlink}">Last Post:</a> {$lastposterlink}<br />{$lastavatar}</td>
    </tr>';

    $new_template['recentthread_avatar'] = '<img src="{$avatarurl}" {$dimensions} alt="{$avatarurl}" />';

    $new_template['recentthread_headerinclude'] = '<script type="text/javascript">
  <!--
	var refresher = window.setInterval(function () {refresh_recent_threads()}, 30000);
    var stopper = window.setTimeout(function() { stop_recent_threads()}, 900000);
    $(document).ready(function() { refresh_recent_threads(); });
    function refresh_recent_threads()
    {
      	var xmlhttp;
		if(window.XMLHttpRequest)
  		{// code for IE7+, Firefox, Chrome, Opera, Safari
 			 xmlhttp=new XMLHttpRequest();
  		}
		else
 		 {// code for IE6, IE5
     		 xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
  		}
      xmlhttp.onreadystatechange=function()
      {
        if(xmlhttp.readyState==4 && xmlhttp.status==200)
    		{
   				 document.getElementById("recentthreads").innerHTML=xmlhttp.responseText;
    		}
      }
      	xmlhttp.open("GET","xmlhttp.php?action=recent_threads",true);
		xmlhttp.send();
    }
    function stop_recent_threads()
    {
      		clearInterval(refresher);
    }
  // -->
  </script>';

    foreach($new_template as $title => $template)
	{
		$new_template = array('title' => $db->escape_string($title), 'template' => $db->escape_string($template), 'sid' => '-1', 'version' => '1800', 'dateline' => TIME_NOW);
		$db->insert_query('templates', $new_template);
	}

    require_once MYBB_ROOT . "/inc/adminfunctions_templates.php";

    find_replace_templatesets('index', "#" . preg_quote('{$forums}') . "#i", '{$forums}<div id="recentthreads">{$recentthreadtable}</div>');
    find_replace_templatesets('index', "#" . preg_quote('{$headerinclude}') . "#i", '{$headerinclude}{$recentthread_headerinclude}');
}

function recentthread_deactivate()
{
    global $db;
    $db->delete_query("templates", "title IN('recentthread','recentthread_thread','recentthread_avatar','recentthread_headerinclude')");

    require_once MYBB_ROOT . "/inc/adminfunctions_templates.php";

    find_replace_templatesets('index', "#" . preg_quote('{$recentthread_headerinclude}') . "#i", '');
    find_replace_templatesets('index', "#" . preg_quote('<div id="recentthreads">{$recentthreadtable}</div>') . "#i", '');
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

function recentthread_list_threads($return=false)
{
	global $mybb, $db, $templates, $recentthreadtable, $recentthreads, $settings, $canviewrecentthreads, $cache, $theme;
    // First check permissions
    if(!recentthread_can_view())
    {
        return;
    }
	require_once MYBB_ROOT."inc/functions_search.php";
    $threadlimit = (int) $mybb->settings['recentthread_threadcount'];
    if(!$threadlimit) // Provide a fallback
    {
	    $threadlimit = 15;
    }
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
	$approved = 0;
	
	// Moderators can view unapproved threads
	if($mybb->usergroup['canmodcp']==1) {
		$approved = -1;
	}
	$unsearchableforums = get_unsearchable_forums();
    $unviewableforums = get_unviewable_forums();
    if($unsearchableforums && $unviewableforums)
    {
        $forumarray = explode(",", $unsearchableforums . "," . $unviewableforums);
        $newarray = array_unique($forumarray);
        $unsearchableforumssql = " AND t.fid NOT IN(" . implode(",", $newarray) . ") ";
    }
    // Take into account any ignored forums
    if($mybb->settings['recentthread_forumskip'])
    {
        $ignoreforums = " AND t.fid NOT IN(" . $mybb->settings['recentthread_forumskip'] . ") ";
    }
    $forums = $cache->read("forums");
	$query = $db->query("
			SELECT t.*, u.username AS userusername, u.usergroup, u.displaygroup, u.avatar as threadavatar, u.avatardimensions as threaddimensions, lp.usergroup AS lastusergroup, lp.avatar as lastavatar, lp.avatardimensions as lastdimensions, lp.displaygroup as lastdisplaygroup
			FROM ".TABLE_PREFIX."threads t
			LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=t.uid)
			LEFT JOIN ".TABLE_PREFIX."users lp ON (t.lastposteruid=lp.uid)
			WHERE 1=1 $where AND t.visible > {$approved} {$unsearchableforumssql} {$ignoreforums}
			ORDER BY t.lastpost DESC
			LIMIT $threadlimit
		");
		while($thread = $db->fetch_array($query))
		{
            $trow = alt_trow();
            $thread['forum'] = $forums[$thread['fid']]['name'];
            $threadlink = get_thread_link($thread['tid'], "", "newpost");
            $lastpostlink = get_thread_link($thread['tid'], "", "lastpost");
			$lastpostdate = my_date($mybb->settings['dateformat'], $thread['lastpost']);
			$lastposttime = my_date($mybb->settings['timeformat'], $thread['lastpost']);
            $lastposttimeago = my_date("relative", $thread['lastpost']);
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
            // Now check the length of subjects
            $length = (int) $mybb->settings['recentthread_subject_length'];
            if(strlen($thread['subject']) > $length && $length != 0)
            {
                // Figure out if we need to split it up.
                $title = my_substr($thread['subject'], 0, $length);
                if($mybb->settings['recentthread_subject_breaker'])
                {
                    $words = explode(" ", $title);
                    $count = count($words) -1;
                    $currenttitle = "";
                    for($x = 0; $x < $count; $x++)
                    {
                        $currenttitle .= $words[$x] . " ";
                    }
                    $thread['subject'] = $currenttitle . " ...";
                }
                if(!$mybb->settings['recentthread_subject_breaker'])
                {
                    $thread['subject'] = $title . "...";
                }
            }

            // Moderator stuff baby!
            if(is_moderator($thread['fid']))
            {
                $ismod = TRUE;
                // fetch the inline mod column
            }
            else
            {
                $ismod = FALSE;
            }
            if(is_moderator($thread['fid'], "caneditposts") || $fpermissions['caneditposts'] == 1)
            {
	            $can_edit_titles = 1;
            }
            else
            {
	            $can_edit_titles = 0;
            }
            $inline_edit_class = '';
		    if(($thread['uid'] == $mybb->user['uid'] && $thread['closed'] != 1 && $mybb->user['uid'] != 0 && $can_edit_titles == 1) || $ismod == true)
		    {
			    $inline_edit_class = "subject_editable";
		    }

			eval("\$recentthreads .= \"".$templates->get("recentthread_thread")."\";");
            unset($posteravatar);
            unset($lastavatar);
		}
        eval("\$recentthreadtable = \"".$templates->get("recentthread")."\";");
        if($return)
        {
            return $recentthreadtable;
        }
}

function recentthread_get_templates()
{
    global $templatelist;
    if(THIS_SCRIPT == "index.php")
    {
        $templatelist .= ",recentthread,recentthread_thread,recentthread_avatar,recentthread_headerinclude";
    }
}

function recentthread_global_intermediate()
{
    global $templates, $recentthread_headerinclude;
    if(THIS_SCRIPT == "index.php" && recentthread_can_view())
    {
        eval("\$recentthread_headerinclude = \"".$templates->get("recentthread_headerinclude")."\";");
    }
}

function recentthread_refresh_threads()
{
    global $db, $mybb, $canviewrecentthreads;
    if($mybb->input['action'] == "recent_threads")
    {
        require_once MYBB_ROOT . "/inc/plugins/recentthread.php";
        if(recentthread_can_view())
        {
            echo(recentthread_list_threads(true));
        }
        die;
    }
}

function recentthread_can_view()
{
    global $mybb;
    if($mybb->settings['recentthread_which_groups'])
    {
        $disallowedgroups = explode(",", $mybb->settings['recentthread_which_groups']);
        $mygroups = $mybb->user['usergroup'];
        if($mybb->user['additionalgroups'])
        {
            $mygroups .= "," . $mybb->user['additionalgroups'];
        }
        $groups = explode(",", $mygroups);
        foreach($groups as $group)
        {
            if(in_array($group, $disallowedgroups))
            {
                return FALSE;
            }
        }
        return TRUE;
    }
    else
    {
         return TRUE;
    }
}

?>
