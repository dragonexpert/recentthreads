<?php
if(!defined("IN_MYBB"))
{
    die("Direct access to this file is not allowed.");
}

$my_plugins = $cache->read("plugins");
if(array_key_exists("recentthread", $my_plugins['active']))
{
    require_once "recentthreads/hooks.php";
}

function recentthread_info()
{
    global $lang;
    $lang->load("recentthreads");
    $donationlink = "https://www.paypal.me/MarkJanssen";
    $updatelink = "index.php?module=config-plugins&action=update_recentthreads";
    return array(
        "name"	=> $lang->recentthreads,
        "description" => $lang->sprintf($lang->recentthreads_desc, $donationlink, $updatelink),
        "author" => "Mark Janssen",
        "version" => "17.0",
        "codename" 	=> "recentthreads",
        "compatibility"	=> "18*"
    );
}

function recentthread_install()
{
    global $mybb, $lang;
    if($mybb->version_code < 1801)
    {
        flash_message($lang->recentthread_cant_install, "error");
        admin_redirect("index.php?module=config-plugins");
    }
    require_once "recentthreads/db.php";
    recentthread_db_install();
    require_once "recentthreads/settings.php";
    recentthread_settings_install();
}

function recentthread_is_installed()
{
    require_once "recentthreads/db.php";
    return recentthread_db_is_installed();
}

function recentthread_activate()
{
    require_once "recentthreads/templates.php";
    recentthread_templates_install();
    require_once "recentthreads/theme.php";
    recentthread_theme_install();
}

function recentthread_deactivate()
{
    require_once "recentthreads/templates.php";
    recentthread_templates_uninstall();
    require_once "recentthreads/theme.php";
    recentthread_theme_uninstall();
}

function recentthread_uninstall()
{
    require_once "recentthreads/db.php";
    recentthread_db_uninstall();
    require_once "recentthreads/settings.php";
    recentthread_settings_uninstall();
}
