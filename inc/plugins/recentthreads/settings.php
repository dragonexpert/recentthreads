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

    $settings_json = json_decode(file_get_contents("settings.json", true), true);
    // escape string is used in the off chance that your settings.json file gets edited maliciously.
    foreach($settings_json as $key)
    {
        $new_setting[] = array(
            "name" => $db->escape_string($key['name']),
            "title" => $db->escape_string($key['title']),
            "description" => $db->escape_string($key['description']),
            "optionscode" => $db->escape_string($key['optionscode']),
            "disporder" => (int) $key['disporder'],
            "value" => $db->escape_string($key['value']),
            "gid" => $gid
        );
    }
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
    $db->free_result($query);
    $db->delete_query("settinggroups", "gid=" . $gid);
    $db->delete_query("settings", "gid=" . $gid);
    rebuild_settings();
}
