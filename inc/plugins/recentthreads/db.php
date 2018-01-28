<?php
if(!defined("IN_MYBB"))
{
    die("Direct access to this file is not allowed.");
}

function recentthread_db_install()
{
    global $db;
    if(!$db->field_exists("recentthread_show", "users"))
    {
        $db->add_column("users", "recentthread_show", "INT NOT NULL DEFAULT 1");
    }
}

function recentthread_db_is_installed()
{
    global $db;
    return $db->field_exists("recentthread_show", "users");
}

function recentthread_db_uninstall()
{
    global $db;
    if($db->field_exists("recentthread_show", "users"))
    {
        $db->drop_column("users", "recentthread_show");
    }
}
