<?php
if(!defined("IN_MYBB"))
{
    die("Direct access to this file is not allowed.");
}

function recentthread_theme_install()
{
    global $db;
    $query = $db->simple_select("themestylesheets", "*", "name='thread_status.css'");
    while($properties = $db->fetch_array($query))
    {
        $attached_to = explode("|", $properties['attachedto']);
        if(!in_array("index.php", $attached_to))
        {
            $update_css = array(
                "attachedto" => $properties['attachedto'] . "|index.php",
            );
            $db->update_query("themestylesheets", $update_css, "sid=" . $properties['sid']);
        }
    }
}

function recentthread_theme_uninstall()
{
    global $db;
    $query = $db->simple_select("themestylesheets", "*", "name='thread_status.css'");
    while($properties = $db->fetch_array($query))
    {
        $attached_to = explode("|", $properties['attachedto']);
        $new_attached = "";
        $pipe = "";
        foreach ($attached_to as $file)
        {
            if($file != "index.php")
            {
                $new_attached .= $pipe . $file;
                $pipe = "|";
            }
        }
        $update_stylesheet = array(
            "attachedto" => $db->escape_string($new_attached)
        );
        $db->update_query("themestylesheets", $update_stylesheet, "sid=" . $properties['sid']);
    }
}
