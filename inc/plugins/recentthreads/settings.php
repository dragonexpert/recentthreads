<?php
if(!defined("IN_MYBB"))
{
    die("Direct access to this file is not allowed.");
}

function recentthread_settings_install()
{
    global $db;

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

    $new_setting[] = array(
        "name" => "recentthread_prefix",
        "title" => "Thread Prefix",
        "description" => "If set to yes, thread prefixes will be shown.",
        "optionscode" => "yesno",
        "disporder" => 8,
        "value" => 1,
        "gid" => $gid
    );

    $new_setting[] = array(
        "name" => "recentthread_prefix_only",
        "title" => "Which Prefix",
        "description" => "A thread must have one of these prefix ids to show, separate with a comma.  Leave blank to not restrict.",
        "optionscode" => "text",
        "disporder" => 9,
        "value" => "",
        "gid" => $gid
    );

    $new_setting[] = array(
        "name" => "recentthread_xthreads",
        "title" => "XThreads",
        "description" => "If set to yes, custom thread fields will be loaded.",
        "optionscode" => "yesno",
        "disporder" => 10,
        "value" => 1,
        "gid" => $gid
    );

    $new_setting[] = array(
        "name" => "recentthread_show_create_date",
        "title" => "Show Creation Time",
        "description" => "If set to yes, the thread start date will be shown.",
        "optionscode" => "yesno",
        "disporder" => 11,
        "value" => 1,
        "gid" => $gid
    );

    $new_setting[] = array(
        "name" => "recentthread_format_names",
        "title" => "Format Usernames",
        "description" => "If set to yes, format the username of the thread starter and last poster.",
        "optionscode" => "yesno",
        "disporder" => 12,
        "value" => 1,
        "gid" => $gid
    );

    $new_setting[] = array(
        "name" => "recentthread_pages_shown",
        "title" => "Show On These Pages",
        "description" => "The pages to show the recent threads on.  One entry per line.",
        "optionscode" => "textarea",
        "disporder" => 13,
        "value" => "index.php",
        "gid" => $gid
    );

    $new_setting[] = array(
        "name" => "recentthread_use_breadcrumbs",
        "title" => "Use breadcrumbs for forum name",
        "description" => "If yes, a forum list will be shown.  Otherwise just the forum the thread resides in.",
        "optionscode" => "yesno",
        "disporder" => 14,
        "value" => 0,
        "gid" => $gid
    );

    $new_setting[] = array(
        "name" => "recentthread_breadcrumb_separator",
        "title" => "Breadcrumb Separator",
        "description" => "The separator for the forum list.  No effect if breadcrumbs are disabled.  HTML allowed.",
        "optionscode" => "text",
        "disporder" => 15,
        "value" => " > ",
        "gid" => $gid
    );

    $db->insert_query_multiple("settings", $new_setting);
    rebuild_settings();
}

function recentthread_settings_uninstall()
{
    global $db;
    $query = $db->simple_select("settinggroups", "*", "name='recentthreads'");
    $gid = $db->fetch_field($query, "gid");
    if(!$gid)
    {
        return;
    }
    $db->delete_query("settinggroups", "gid=" . $gid);
    $db->delete_query("settings", "gid=" . $gid);
    rebuild_settings();
}
