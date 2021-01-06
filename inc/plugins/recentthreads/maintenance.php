<?php
/*
 * This file is part of the Recent Threads On Index Plugin for MyBB.
 * This file is used for repairing inconsistencies in settings, database integrity, and templates.
 * Copyright Mark Janssen 2021
 */
if (!defined("IN_MYBB"))
{
    die("Direct access to this file is not allowed.");
}

function recentthread_maintenance()
{
    global $db, $lang, $mybb;
    if ($mybb->input['action'] != "maintenance_recentthreads")
    {
        return;
    }
    $lang->load("recentthreads");
    $settinggroupquery = $db->simple_select("settinggroups", "*", "name='recentthreads'");
    $settinggroupinfo = $db->fetch_array($settinggroupquery);
    $flash_message = "";
    $db->free_result($settinggroupquery);
    if(array_key_exists("gid", $settinggroupinfo))
    {
        $gid = $settinggroupinfo['gid'];
    }
    else
    {
        $new_setting_group = array(
            "name" => "recentthreads",
            "title" => "Recent Threads Settings",
            "description" => "Customize various aspects of recent threads",
            "disporder" => 77,
            "isdefault" => 0
        );
        $gid = $db->insert_query("settinggroups", $new_setting_group);
    }
    $have_already = array();
    // All settings start with recentthread_
    $query = $db->simple_select("settings", "*", "name LIKE 'recentthread_%')");

    while ($old_setting = $db->fetch_array($query))
    {
        $have_already[] = $old_setting['name'];
    }
    $db->free_result($query);
    // Create a variable to see if we inserted any settings.
    $settingschanged = 0;
    if(!in_array("recentthread_threadcount", $have_already))
    {
        $new_setting[] = array(
            "name" => "recentthread_threadcount",
            "title" => "Number of Threads",
            "description" => "How many threads are shown.",
            "optionscode" => "numeric",
            "disporder" => 1,
            "value" => 15,
            "gid" => $gid
        );
        ++$settingschanged;
    }
    if(!in_array("recentthread_threadavatar", $have_already))
    {
        $new_setting[] = array(
            "name" => "recentthread_threadavatar",
            "title" => $db->escape_string("Show thread starter's avatar"),
            "description" => $db->escape_string("If set to yes, the thread starter's avatar will be shown."),
            "optionscode" => "yesno",
            "disporder" => 2,
            "value" => 0,
            "gid" => $gid
        );
        ++$settingschanged;
    }
    if(!in_array("recentthread_lastavatar", $have_already))
    {
        $new_setting[] = array(
            "name" => "recentthread_lastavatar",
            "title" => $db->escape_string("Show last poster's avatar"),
            "description" => $db->escape_string("If set to yes, the last poster's avatar will be shown."),
            "optionscode" => "yesno",
            "disporder" => 3,
            "value" => 0,
            "gid" => $gid
        );
        ++$settingschanged;
    }
    if(!in_array("recentthread_forumskip", $have_already))
    {
        $new_setting[] = array(
            "name" => "recentthread_forumskip",
            "title" => "Forums To Ignore",
            "description" => "The forums threads should not be pulled from.",
            "optionscode" => "forumselect",
            "disporder" => 4,
            "value" => "",
            "gid" => $gid
        );
        ++$settingschanged;
    }
    if(!in_array("recentthread_subject_length", $have_already))
    {
        $new_setting[] = array(
            "name" => "recentthread_subject_length",
            "title" => "Max Title Length",
            "description" => "The amount of characters before the rest of the title is truncated. Enter 0 for no limit.",
            "optionscode" => "numeric",
            "disporder" => 5,
            "value" => 0,
            "gid" => $gid
        );
        ++$settingschanged;
    }
    if(!in_array("recentthread_subject_breaker", $have_already))
    {
        $new_setting[] = array(
            "name" => "recentthread_subject_breaker",
            "title" => "Word Breaking",
            "description" => "If selected, the title will be kept to full words only in cut off.",
            "optionscode" => "yesno",
            "disporder" => 6,
            "value" => 0,
            "gid" => $gid
        );
        ++$settingschanged;
    }
    if(!in_array("recentthread_which_groups", $have_already))
    {
        $new_setting[] = array(
            "name" => "recentthread_which_groups",
            "title" => "Permissions",
            "description" => "These groups cannot view the recent threads on index.",
            "optionscode" => "groupselect",
            "disporder" => 7,
            "value" => 7,
            "gid" => $gid
        );
        ++$settingschanged;
    }
    if(!in_array("recentthread_prefix", $have_already))
    {
        $new_setting[] = array(
            "name" => "recentthread_prefix",
            "title" => "Thread Prefix",
            "description" => "If set to yes, thread prefixes will be shown.",
            "optionscode" => "yesno",
            "disporder" => 8,
            "value" => 1,
            "gid" => $gid
        );
        ++$settingschanged;
    }
    if(!in_array("recentthread_prefix_only", $have_already))
    {
        $new_setting[] = array(
            "name" => "recentthread_prefix_only",
            "title" => "Which Prefix",
            "description" => "A thread must have one of these prefix ids to show, separate with a comma.  Leave blank to not restrict.",
            "optionscode" => "text",
            "disporder" => 9,
            "value" => "",
            "gid" => $gid
        );
        ++$settingschanged;
    }
    if(!in_array("recentthread_xthreads", $have_already))
    {
        $new_setting[] = array(
            "name" => "recentthread_xthreads",
            "title" => "XThreads",
            "description" => "If set to yes, custom thread fields will be loaded.",
            "optionscode" => "yesno",
            "disporder" => 10,
            "value" => 1,
            "gid" => $gid
        );
        ++$settingschanged;
    }
    if(!in_array("recentthread_show_create_date", $have_already))
    {
        $new_setting[] = array(
            "name" => "recentthread_show_create_date",
            "title" => "Show Creation Time",
            "description" => "If set to yes, the thread start date will be shown.",
            "optionscode" => "yesno",
            "disporder" => 11,
            "value" => 1,
            "gid" => $gid
        );
        ++$settingschanged;
    }
    if(!in_array("recentthread_format_names", $have_already))
    {
        $new_setting[] = array(
            "name" => "recentthread_format_names",
            "title" => "Format Usernames",
            "description" => "If set to yes, format the username of the thread starter and last poster.",
            "optionscode" => "yesno",
            "disporder" => 12,
            "value" => 1,
            "gid" => $gid
        );
        ++$settingschanged;
    }
    if(!in_array("recentthread_pages_shown", $have_already))
    {
        $new_setting[] = array(
            "name" => "recentthread_pages_shown",
            "title" => "Show On These Pages",
            "description" => "The pages to show the recent threads on.  One entry per line.",
            "optionscode" => "textarea",
            "disporder" => 13,
            "value" => "index.php",
            "gid" => $gid
        );
        ++$settingschanged;
    }
    if(!in_array("recentthread_use_breadcrumbs", $have_already))
    {
        $new_setting[] = array(
            "name" => "recentthread_use_breadcrumbs",
            "title" => "Use breadcrumbs for forum name",
            "description" => "If yes, a forum list will be shown.  Otherwise just the forum the thread resides in.",
            "optionscode" => "yesno",
            "disporder" => 14,
            "value" => 0,
            "gid" => $gid
        );
        ++$settingschanged;
    }
    if(!in_array("recentthread_breadcrumb_separator", $have_already))
    {
        $new_setting[] = array(
            "name" => "recentthread_breadcrumb_separator",
            "title" => "Breadcrumb Separator",
            "description" => "The separator for the forum list.  No effect if breadcrumbs are disabled.  HTML allowed.",
            "optionscode" => "text",
            "disporder" => 15,
            "value" => " > ",
            "gid" => $gid
        );
        ++$settingschanged;
    }
    if($settingschanged > 0)
    {
        $db->insert_query_multiple("settings", $new_setting);
        rebuild_settings();
        $flash_message .= "<br />Inserted " . $settingschanged . " settings.";
    }
    // Now database checks
    if(!$db->field_exists("recentthread_show", "users"))
    {
        $db->add_column("users", "recentthread_show", "INT NOT NULL DEFAULT 1");
        $flash_message .= "<br />Fixed database structure.";
    }
    // Check if they have the template group.  If not run the full script.
    $templategroupquery = $db->simple_select("templategroups", "*", "prefix='recentthread'");
    $templategroupinfo = $db->fetch_array($templategroupquery);
    $db->free_result($templategroupquery);
    if($db->num_rows($templategroupquery) < 1)
    {
        require_once "templates.php";
        recentthread_templates_install();
        $flash_message .= "<br />Added templates.  Please check you don't have duplicates.";
    }
    flash_message($flash_message, "success");
    admin_redirect("index.php?module=config-plugins");
}
