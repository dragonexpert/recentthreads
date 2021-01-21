<?php
if(!defined("IN_MYBB"))
{
    die("Direct access to this file is not allowed.");
}

$plugins->add_hook("global_end", "recentthread_list_threads");
$plugins->add_hook("global_start", "recentthread_get_templates");
$plugins->add_hook("global_intermediate", "recentthread_global_intermediate");
$plugins->add_hook("xmlhttp", "recentthread_refresh_threads");
$plugins->add_hook("usercp_options_start", "recentthread_usercp_options_start");
$plugins->add_hook("usercp_do_options_start", "recentthread_usercp_do_options_end");
$plugins->add_hook("misc_start", "recentthread_page");

if(defined("IN_ADMINCP"))
{
    // Due to the massive structural changes, no upgrade script from before version 16.
    require_once "update.php";
    require_once "maintenance.php";
    $plugins->add_hook("admin_config_plugins_begin", "recentthread_update");
    $plugins->add_hook("admin_config_plugins_begin", "recentthread_maintenance");
    $plugins->add_hook("admin_config_settings_begin", "recentthread_admin_config_settings_begin");
    $plugins->add_hook("admin_tools_adminlog_begin", "recentthread_admin_tools_adminlog_begin");
    $plugins->add_hook("admin_tools_get_admin_log_action", "recenttthread_admin_tools_get_admin_log_action");
    $plugins->add_hook("admin_style_templates", "recentthread_admin_style_templates");
}

function recentthread_list_threads($return=false, $threadcount=0, $page=1)
{
    global $mybb, $db, $templates, $recentthreadtable, $recentthreads, $settings, $canviewrecentthreads, $cache, $theme, $lang, $threadfields, $xthreadfields;
    global $expcolimage, $expthead, $expaltext, $expdisplay, $collapsed_name, $collapsed, $moderator_form;
    global $plugins; /* This enables us to have plugin hooks for users. */
    // First check permissions
    if(!recentthread_can_view())
    {
        return false;
    }
    if($mybb->settings['recentthread_pages_shown'])
    {
        $allowed_pages = explode("\n", $mybb->settings['recentthread_pages_shown']);
    }
    else
    {
        $allowed_pages = array();
    }
    $allowed_pages = str_replace(array(" ", "\n", "\r"), "", $allowed_pages);
    $allowed_pages[] = "xmlhttp.php";
    $allowed_pages[] = "misc.php";
    if(!in_array(THIS_SCRIPT, $allowed_pages))
    {
        return false;
    }
    $lang->load("recentthreads");
    $lang->load("forumdisplay");
    $icon_cache = $cache->read("posticons");
    require_once MYBB_ROOT."inc/functions_search.php";
    if($threadcount == 0) {
        $threadlimit = (int)$mybb->settings['recentthread_threadcount'];
        if (!$threadlimit) // Provide a fallback
        {
            $threadlimit = 15;
        }
    }
    else
    {
        $threadlimit = (int) $threadcount;
    }
    if($threadlimit <= 0)
    {
        // Fallback for people who call the function wrong.
        $threadlimit = 5;
    }
    $page = (int) $page;
    $comma = $start = "";
    if($page > 1)
    {
        $start = $page * $threadlimit - $threadlimit;
        $comma = ",";
    }
    $onlyusfids = array();
    $onlycanview = array();
    // Check group permissions if we can't view threads not started by us
    $group_permissions = forum_permissions();
    foreach($group_permissions as $fid => $forum_permissions)
    {
        if($forum_permissions['canonlyviewownthreads'] == 1)
        {
            $onlyusfids[] = $fid;
        }
        if ($forum_permissions['canview'] == 0)
        {
            $onlycanview[] = $fid;
        }
    }
    $where = "";
    if(!empty($onlyusfids))
    {
        $where .= "AND ((t.fid IN(".implode(',', $onlyusfids).") AND t.uid='{$mybb->user['uid']}') OR t.fid NOT IN(".implode(',', $onlyusfids)."))";
    }
    if (!empty($onlycanview))
    {
        $where .= "AND (t.fid NOT IN(".implode(',', $onlycanview)."))";
    }
    $approved = 0;

    // Moderators can view unapproved threads
    if($mybb->usergroup['issupermod']==1)
    {
        $approved = -2;
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
        $ignoreforums = " AND t.fid NOT IN(" . htmlspecialchars($mybb->settings['recentthread_forumskip']) . ") ";
    }
    $forums = $cache->read("forums");
    $prefixes = $cache->read("threadprefixes");

    // Are we only using certain prefixes?
    if($mybb->settings['recentthread_prefix_only'])
    {
        if(is_numeric($mybb->settings['recentthread_prefix_only']))
        {
            $prefixonly = " AND t.prefix = " . (int) $mybb->settings['recentthread_prefix_only'] . " ";
        }
        else
        {
            $prefixlist = explode(",", $mybb->settings['recentthread_prefix_only']);
            $newlist = array_map("intval", $prefixlist); /* Use this to stop any funny business. */
            $prefixonly = " AND t.prefix IN(" . implode(',', $newlist) . ") ";
        }
    }


    // Take XThreads into account
    if(function_exists("xthreads_get_threadfields") && $mybb->settings['recentthread_xthreads'] == 1)
    {
        $quickquery = $db->query("SELECT t.tid as threadid, t.username, t.fid, tf.*
                                    FROM " . TABLE_PREFIX . "threads t
                                    LEFT JOIN " . TABLE_PREFIX . "threadfields_data tf ON(t.tid=tf.tid)
                                    WHERE 1=1 $where $prefixonly AND t.visible > $approved $unsearchableforumssql $ignoreforums
                                    ORDER BY t.lastpost DESC
                                    LIMIT $start $comma $threadlimit");

        while ($threadfields = $db->fetch_array($quickquery))
        {
            $threadfields_raw[$xthreadsfields['threadid']] = $xthreadsfields;
            if ($threadfields['fid'] == $GLOBALS['fid'])
            {
                // use global cache if we're referring to current forum
                $threadfield_cache =& $GLOBALS['threadfield_cache'];
            }
            if (!isset($threadfield_cache))
            {
                $threadfield_cache = xthreads_gettfcache((int)$threadfields['fid']);
            }
            if (!empty($threadfield_cache))
            {
                if (!isset($threadfields))
                {
                    $threadfields = array();
                }
                foreach ($threadfield_cache as $k => &$v)
                {
                    xthreads_get_xta_cache($v, $threadfields['threadid']);
                    xthreads_sanitize_disp($threadfields[$k], $v, $threadfields['username'], true);
                }
                $threadfields_formatted[$threadfields['threadid']] = $threadfields;
            }
        }
        $db->free_result($quickquery);
    }

    // Get a thread read cache
    $threadsread = array();
    if($mybb->user['uid'] && $mybb->settings['threadreadcut'] > 0)
    {
        $query = $db->query("SELECT tr.*, t.closed
                            FROM " . TABLE_PREFIX . "threadsread tr
                            LEFT JOIN " . TABLE_PREFIX . "threads t ON(tr.tid=t.tid)
                            WHERE tr.uid=" . $mybb->user['uid'] . " " . $where . $prefixonly . " AND t.visible > " . $approved . $unsearchableforumssql . $ignoreforums .
            " ORDER BY t.lastpost DESC
                            LIMIT $start $comma $threadlimit");
        while($threadread = $db->fetch_array($query))
        {
            $threadsread[$threadread['tid']] = $threadread['dateline'];
        }
        $db->free_result($query);
    }
    $plugins->run_hooks("recentthread_get_threads");
    $query = $db->query("
			SELECT t.*, u.username AS userusername, u.usergroup, u.displaygroup, u.avatar as threadavatar, u.avatardimensions as threaddimensions, lp.usergroup AS lastusergroup, lp.avatar as lastavatar, lp.avatardimensions as lastdimensions, lp.displaygroup as lastdisplaygroup, fr.dateline as forumlastread
			FROM " . TABLE_PREFIX . "threads t
			LEFT JOIN " . TABLE_PREFIX . "users u ON (u.uid=t.uid)
			LEFT JOIN " . TABLE_PREFIX . "users lp ON (t.lastposteruid=lp.uid)
			LEFT JOIN " . TABLE_PREFIX . "forumsread fr ON (fr.fid = t.fid AND fr.uid = {$mybb->user['uid']})
			WHERE 1=1 $where $prefixonly AND t.visible > {$approved} {$unsearchableforumssql} {$ignoreforums}
			ORDER BY t.lastpost DESC
			LIMIT $start $comma $threadlimit");

    $listed_tids = array();
    $forum_list = $cache->read("forums");
    if($mybb->usergroup['issupermod'] && THIS_SCRIPT == "misc.php")
    {
        $colspan = 7;
        eval("\$modheader =\"".$templates->get("recentthread_misc_mod_header")."\";");
        eval("\$moderator_form =\"".$templates->get("recentthread_misc_moderation")."\";");
    }
    else
    {
        $colspan = 6;
    }
    while($thread = $db->fetch_array($query))
    {
        $plugins->run_hooks("recentthread_thread");
        $parent = $forum_list[$thread['fid']]['parentlist'];
        $recentthread_breadcrumbs = "";
        $multitid = $thread['tid'];
        if($mybb->settings['recentthread_use_breadcrumbs'])
        {
            if (strpos($parent, ","))
            {
                if($mybb->settings['recentthread_full_breadcrumb'])
                {
                    $parentlist = explode(",", $parent);
                    $separator = "";
                    foreach ($parentlist as $subforum)
                    {
                        $recentthread_breadcrumbs .= $separator . "<a href='" . $mybb->settings['bburl'] . "/forumdisplay.php?fid=" . $subforum . "'>" . $forum_list[$subforum]['name'] . "</a>";
                        $separator = $mybb->settings['recentthread_breadcrumb_separator'];
                    }
                }
                else
                {
                    $separator = "";
                    $parentlist = array($forum_list[$thread['fid']]['pid'], $thread['fid']);
                    foreach ($parentlist as $subforum)
                    {
                        $recentthread_breadcrumbs .= $separator . "<a href='" . $mybb->settings['bburl'] . "/forumdisplay.php?fid=" . $subforum . "'>" . $forum_list[$subforum]['name'] . "</a>";
                        $separator = $mybb->settings['recentthread_breadcrumb_separator'];
                    }
                }
            }
            else
            {
                $thread['forum'] = $forum_list[$thread['fid']]['name'];
                $recentthread_breadcrumbs = "<a href=\"{$mybb->settings['bburl']}/forumdisplay.php?fid={$thread['fid']}\">{$thread['forum']}</a>";
            }
        }
        else
        {
            $thread['forum'] = $forum_list[$thread['fid']]['name'];
            $recentthread_breadcrumbs = "<a href=\"{$mybb->settings['bburl']}/forumdisplay.php?fid={$thread['fid']}\">{$thread['forum']}</a>";
        }
        $folder = $folder_label = "";
        $isnew = 0;
        if(strpos($thread['closed'], "moved|") === 0)
        {
            $thread['tid'] = substr($thread['closed'], 6);
            $folder = "move";
        }
        $tid = $thread['tid'];

        // Figure out the read status and lock status
        if($thread['sticky'] == 1)
        {
            $thread_type_class = " forumdisplay_sticky";
        }
        else
        {
            $thread_type_class = " forumdisplay_regular";
        }

        $lastread = 0;
        if (array_key_exists($thread['tid'], $threadsread)) {
            $lastread = $threadsread[$thread['tid']];
        }
        if (!is_null($thread['forumlastread']) && $thread['forumlastread'] > $lastread) {
            $lastread = $thread['forumlastread'];
        }
        if($thread['lastpost'] > $lastread)
        {
            $folder .= "new";
            $folder_label .= $lang->icon_new;
            $new_class = "subject_new";
        }
        else /*if(array_key_exists($thread['tid'], $threadsread) && $thread['lastpost'] <= $threadsread[$thread['tid']])*/
        {
            $folder_label = $lang->icon_no_new;
            $new_class = "subject_old";
        }
        if($thread['replies'] >= $mybb->settings['hottopic'] || $thread['views'] >= $mybb->settings['hottopicviews'])
        {
            $folder .= "hot";
            $folder_label .= $lang->icon_hot;
        }
        if($thread['closed'] == 1)
        {
            $folder .= "close";
            $folder_label .= $lang->icon_lock;
        }
        $folder .= "folder";

        $trow = alt_trow();
        if($thread['visible'] == 0)
        {
            $trow = "trow_shaded";
        }
        if($thread['visible'] == -1)
        {
            $trow = "trow_shaded trow_deleted";
        }
        $thread['forum'] = $forums[$thread['fid']]['name'];
        if($mybb->settings['recentthread_prefix'])
        {
            $recentprefix = $prefixes[$thread['prefix']]['displaystyle'];
        }
        if($thread['icon'] > 0 && $icon_cache[$thread['icon']])
        {
            $icon = $icon_cache[$thread['icon']];
            $icon['path'] = str_replace("{theme}", $theme['imgdir'], $icon['path']);
            $icon['path'] = htmlspecialchars_uni($icon['path']);
            $icon['name'] = htmlspecialchars_uni($icon['name']);
            eval("\$icon = \"".$templates->get("forumdisplay_thread_icon")."\";");
        }
        else
        {
            $icon = "&nbsp;";
        }
        $threadlink = $thread['newpostlink'] = get_thread_link($tid, "", "newpost"); // Maintain for template compatibility
        eval("\$arrow =\"".$templates->get("forumdisplay_thread_gotounread")."\";");
        $lastpostlink = get_thread_link($tid, "", "lastpost");
        $lastpostdate = my_date($mybb->settings['dateformat'], $thread['lastpost']);
        $lastposttime = my_date($mybb->settings['timeformat'], $thread['lastpost']);
        $lastposttimeago = my_date("relative", $thread['lastpost']);
        $lastposter = $thread['lastposter'];
        $lastposteruid = $thread['lastposteruid'];
        if($mybb->settings['recentthread_format_names'])
        {
            $thread['author'] = build_profile_link(format_name($thread['userusername'], $thread['usergroup'], $thread['displaygroup']), $thread['uid']);
            // Don't link to guest's profiles (they have no profile).
            if ($lastposteruid == 0)
            {
                $lastposterlink = $lastposter;
            }
            else
            {
                $lastposterlink = build_profile_link(format_name($lastposter, $thread['lastusergroup'], $thread['lastdisplaygroup']), $lastposteruid);
            }
        }
        else
        {
            $thread['author'] = build_profile_link($thread['userusername'], $thread['uid']);
            if($lastposteruid == 0)
            {
                $lastposterlink = $lastposter;
            }
            else
            {
                $lastposterlink = build_profile_link($lastposter, $lastposteruid);
            }
        }
        if($mybb->settings['recentthread_show_create_date'])
        {
            $create_time = my_date($mybb->settings['timeformat'], $thread['dateline']);
            $create_date = my_date($mybb->settings['dateformat'], $thread['dateline']);
            $create_string = $lang->sprintf($lang->recentthread_create_date, $create_date, $create_time);
        }
        else
        {
            $lang->recentthread_create_date = "";
            $create_string = "";
        }
        if($mybb->settings['recentthread_avatar'] && $mybb->user['showavatars'])
        {
            $threadavatar = format_avatar($thread['threadavatar'], $thread['threaddimensions']);
            $avatarurl = $threadavatar['image'];
            $dimensions = $threadavatar['width_height'];
            eval("\$posteravatar = \"".$templates->get("recentthread_avatar")."\";");
        }
        if($mybb->settings['recentthread_lastavatar'] && $mybb->user['showavatars'])
        {
            $lastposteravatar = format_avatar($thread['lastavatar'], $thread['lastdimensions']);
            $avatarurl = $lastposteravatar['image'];
            $dimensions = $lastposteravatar['width_height'];
            eval("\$lastavatar = \"".$templates->get("recentthread_last_avatar")."\";");
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
        $thread['subject'] = htmlspecialchars_uni($thread['subject']);
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
        if($mybb->usergroup['issupermod'] && THIS_SCRIPT == "misc.php")
        {
            eval("\$modcol = \"".$templates->get("recentthread_misc_mod_column")."\";");
        }

        // Multipage.  Code from forumdisplay.php
        $thread['posts'] = $thread['replies'] +1;
        if($thread['unapprovedposts'] > 0 && $ismod)
        {
            $thread['posts'] += $thread['unapprovedposts'] + $thread['deletedposts'];
        }
        if($thread['posts'] > $mybb->settings['postsperpage'])
        {
            $thread['pages'] = ceil($thread['posts'] / $mybb->settings['postsperpage']);
            if($thread['pages'] > $mybb->settings['maxmultipagelinks'])
            {
                $pagesstop = $mybb->settings['maxmultipagelinks'] - 1;
                $page_link = get_thread_link($thread['tid'], $thread['pages']);
                eval("\$morelink = \"".$templates->get("forumdisplay_thread_multipage_more")."\";");
            }
            else
            {
                $pagesstop = $thread['pages'];
            }
            for($i = 1; $i <= $pagesstop; ++$i)
            {
                $page_link = get_thread_link($thread['tid'], $i);
                eval("\$threadpages .= \"".$templates->get("forumdisplay_thread_multipage_page")."\";");
            }
            eval("\$thread['multipage'] = \"".$templates->get("forumdisplay_thread_multipage")."\";");
        }
        else
        {
            $threadpages = '';
            $morelink = '';
            $thread['multipage'] = '';
        }
        if(!in_array($thread['tid'], $listed_tids))
        {
            eval("\$recentthreads .= \"" . $templates->get("recentthread_thread") . "\";");
            $posteravatar = $lastavatar = $icon = $thread['multipage'] = $threadpages = $morelink = "";
            array_push($listed_tids, $thread['tid']);
        }
    } // End the while loop
    $db->free_result($query);
    $expdisplay = '';
    $collapsed_name = "cat_9999_c";
    if(isset($collapsed[$collapsed_name]) && $collapsed[$collapsed_name] == "display: show;")
    {
        $expcolimage = "collapse_collapsed.png";
        $expdisplay = "display: none;";
        $expthead = " thead_collapsed";
        $expaltext = "[+]";
    }
    else
    {
        $expcolimage = "collapse.png";
        $expthead = "";
        $expaltext = "[-]";
    }
    $plugins->run_hooks("recentthread_threadlist");
    eval("\$recentthreadtable = \"".$templates->get("recentthread")."\";");
    if($return)
    {
        return $recentthreadtable;
    }
}

function recentthread_get_templates()
{
    global $templatelist, $mybb;
    if($mybb->settings['recentthread_pages_shown'])
    {
        $allowed_pages = explode("\n", $mybb->settings['recentthread_pages_shown']);
    }
    else
    {
        $allowed_pages = array();
    }
    $allowed_pages = str_replace(array(" ", "\n", "\r"), "", $allowed_pages);
    $allowed_pages[] = "xmlhttp.php";
    $allowed_pages[] = "misc.php";
    if(in_array(THIS_SCRIPT, $allowed_pages))
    {
        $templatelist .= ",recentthread,recentthread_thread,recentthread_avatar,recentthread_last_avatar,recentthread_headerinclude,forumdisplay_thread_gotounread";
        $templatelist .= ",forumdisplay_thread_multipage,forumdisplay_thread_multipage_page,forumdisplay_thread_multipage_more,forumdisplay_thread_icon";
    }
    if(THIS_SCRIPT == "usercp.php")
    {
        $templatelist .= ",recentthread_usercp";
    }
    if(THIS_SCRIPT == "misc.php")
    {
        $templatelist .= ",recentthreads_misc,recentthreads_misc_moderation,recentthreads_misc_mod_header, recentthreads_misc_mod_column";
    }
}

function recentthread_global_intermediate()
{
    global $templates, $recentthread_headerinclude, $mybb, $refresher;
    if($mybb->settings['recentthread_pages_shown'])
    {
        $allowed_pages = explode("\n", $mybb->settings['recentthread_pages_shown']);
    }
    else
    {
        $allowed_pages = array();
    }
    $allowed_pages = str_replace(array(" ", "\n", "\r"), "", $allowed_pages);
    $allowed_pages[] = "xmlhttp.php";
    if(in_array(THIS_SCRIPT, $allowed_pages) && recentthread_can_view())
    {
        if($mybb->settings['recentthread_refresh_interval'] > 0)
        {
            $refresh_interval = $mybb->settings['recentthread_refresh_interval'] * 1000;
            $refresher = "var refresher = window.setInterval(function () {refresh_recent_threads()}, " . $refresh_interval . ");";
        }
        else
        {
            $refresher = "var refresher = window.setInterval(function () {refresh_recent_threads()}, 900000);";
        }
        eval("\$recentthread_headerinclude = \"".$templates->get("recentthread_headerinclude")."\";");
    }
}

function recentthread_refresh_threads()
{
    global $db, $mybb, $canviewrecentthreads;
    if($mybb->input['action'] == "recent_threads")
    {
        require_once MYBB_ROOT . "/inc/plugins/recentthreads/hooks.php";
        if(recentthread_can_view())
        {
            if($mybb->input['from'] == "misc.php")
            {
                echo(recentthread_list_threads(TRUE, $mybb->user['tpp'], $mybb->input['page']));
            }
            else {
                echo(recentthread_list_threads(TRUE, 0, 1));
            }
        }
        die;
    }
}

function recentthread_can_view()
{
    global $mybb;
    if($mybb->user['uid'] && $mybb->user['recentthread_show'] == 0)
    {
        return false;
    }
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
                return false;
            }
        }
        return true;
    }
    else
    {
        return true;
    }
}

function recentthread_admin_style_templates()
{
    global $lang;
    $lang->load("recentthreads");
}

function recentthread_admin_config_settings_begin()
{
    global $lang;
    $lang->load("recentthreads");
}

function recentthread_admin_tools_adminlog_begin()
{
    global $lang;
    $lang->load("recentthreads");
}

function recenttthread_admin_tools_get_admin_log_action(&$plugin_array)
{
    if($plugin_array['logitem']['module'] == "config-plugins"  && $plugin_array['logitem']['action'] == "update_recentthreads")
    {
        $plugin_array['lang_string'] = "admin_log_config_plugins_update_recentthreads";
    }
}

function recentthread_usercp_options_start()
{
    global $mybb, $lang, $templates, $recentthreadcheck, $recentthread_option;
    $lang->load("recentthreads");
    if($mybb->user['recentthread_show'] != 0)
    {
        $recentthreadcheck = "checked=\"checked\"";
    }
    eval("\$recentthread_option =\"".$templates->get("recentthread_usercp")."\";");
}

function recentthread_usercp_do_options_end()
{
    global $mybb, $db;
    $update_user['recentthread_show'] = (int) $mybb->input['recentthread_show'];
    $db->update_query("users", $update_user, "uid=" . $mybb->user['uid']);
}

function recentthread_page()
{
    global $mybb, $templates, $recentthreadtable, $recentthreads, $settings, $canviewrecentthreads, $theme, $lang, $threadfields, $xthreadfields, $header;
    global $headerinclude, $footer, $thread, $lang, $moderator_form;
    if($mybb->input['action'] == "recent_threads")
    {
        $lang->load("recentthreads");
        $lang->load("forumdisplay");
        if($mybb->input['modaction'])
        {
            recentthread_moderation();
        }
        add_breadcrumb($lang->recentthreads_recentthreads, "misc.php?action=recent_threads");
        recentthread_list_threads(false, $mybb->user['tpp'], $mybb->input['page']);
        eval("\$recentthread_page =\"".$templates->get("recentthread_misc")."\";");
        output_page($recentthread_page);
    }
    return;
}

function recentthread_moderation()
{
    global $mybb, $db, $plugins, $lang;
    if(!$mybb->usergroup['issupermod'])
    {
        error_no_permission();
    }
    if($mybb->request_method == "post"  && verify_post_check($mybb->get_input("my_post_key")))
    {
        $lang->load("recentthreads");
        $action = $mybb->get_input("modaction");
        require_once MYBB_ROOT . "/inc/class_moderation.php";
        $moderation = new Moderation();
        $tids = $db->escape_string($mybb->get_input("tids"));
        $tidarray = explode(",", $tids);
        $modlog = array();
        $modlog['tids'] = $tids;
        switch ($action) {
            case "multiclosethreads":
                $moderation->close_threads($tidarray);
                log_moderator_action($modlog, "Locked threads");
                break;
            case "multiopenthreads":
                $moderation->open_threads($tidarray);
                log_moderator_action($modlog, "Unlocked threads");
                break;
            case "multistickthreads":
                $moderation->stick_threads($tidarray);
                log_moderator_action($modlog, "Stuck threads");
                break;
            case "multiunstickthreads":
                $moderation->unstick_threads($tidarray);
                log_moderator_action($modlog, "Unstuck threads");
                break;
            case "multisoftdeletethreads":
                // Only soft delete in case of a mistake.
                $moderation->soft_delete_threads($tidarray);
                log_moderator_action($modlog, "Soft deleted threads");
                break;
            case "multiapprovethreads":
                $moderation->approve_threads($tidarray);
                log_moderator_action($modlog, "Approved threads");
                break;
            case "multiunapprovethreads":
                $moderation->unapprove_threads($tidarray);
                log_moderator_action($modlog, "Unapproved threads");
                break;
            case "multirestorethreads":
                $moderation->restore_threads($tidarray);
                log_moderator_action($modlog, "Restored threads");
                break;
            default:
                // This might be cool for custom moderator tools.
                $plugins->run_hooks("recentthread_moderation");
                break;
        }
        if ($mybb->settings['redirects'])
        {
            $url = $mybb->settings['bburl'] . "/misc.php?action=recent_threads";
            $message = $lang->recentthread_redirect;
            redirect($url, $message);
        }
    }
}
