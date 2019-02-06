<?php
if(!defined("IN_MYBB"))
{
    die("Direct access to this file is not allowed.");
}

function recentthread_templates_install()
{
    global $db, $config;
    $new_template_group = array(
        "prefix" => "recentthread",
        "title" => "<lang:recentthreads_template>",
        "isdefault" => 0
    );

    $db->insert_query("templategroups", $new_template_group);

    $new_template['recentthread'] = '<div class="scroll">
<div id="recentthreads">
<table border="0" cellspacing="1" cellpadding="6" class="tborder" style="clear: both;max-height:300px">
<thead>
    <tr>
    <td class="thead{$expthead}" colspan="6" style="text-align:left; font-size: 10pt;">
    <div class="expcolimage">
    <img src="{$theme[\'imgdir\']}/collapse.png" id="cat_9999_img" class="expander" alt="{$expaltext}" title="{$expaltext}" />
    </div>
<div><b>~ {$lang->recentthreads_recentthreads} ~</b></div>
</td>
    </tr>
</thead>
<tbody style="{$expdisplay}" id="cat_9999_e">
    <tr>
    <td class="tcat" colspan="3"><strong>{$lang->recentthreads_thread} / {$lang->recentthreads_author}</strong></td>
    <td class="tcat"><strong>{$lang->recentthreads_forum}</strong></td>
    <td class="tcat"><strong>{$lang->recentthreads_posts}</strong></td>
    <td class="tcat"><strong>{$lang->recentthreads_last_post}</strong></td>
    </tr>
    {$recentthreads}
</tbody>
    </table>
</div>
    </div>';

    $new_template['recentthread_thread'] = '<tr>
<td align="center" class="{$trow}{$thread_type_class}" width="2%"><span class="thread_status {$folder}" title="{$folder_label}">&nbsp;</span></td>
    <td class="{$trow}{$thread_type_class}" width="2%">{$icon}</td>
    <td class="{$trow}{$thread_type_class}">{$arrow}&nbsp;{$recentprefix}<span class="{$new_class}" id="tid_{$thread[\'tid\']}"><a href="{$mybb->settings[\'bburl\']}/showthread.php?tid={$thread[\'tid\']}">{$thread[\'subject\']}</a></span>&nbsp;&nbsp;{$thread[\'multipage\']}<br />{$create_string} {$thread[\'author\']}<br />{$posteravatar}</td>
    <td class="{$trow}{$thread_type_class}">{$recentthread_breadcrumbs}</td>
    <td class="{$trow}{$thread_type_class}"><a href="javascript:MyBB.whoPosted({$thread[\'tid\']});">{$thread[\'replies\']}</a></td>
    <td class="{$trow}{$thread_type_class}">{$lastposttimeago}<br />
    <a href="{$lastpostlink}">Last Post:</a> {$lastposterlink}<br />{$lastavatar}</td>
    </tr>';

    $new_template['recentthread_avatar'] = '<a href="{$mybb->settings[\'bburl\']}/member.php?action=profile&uid={$thread[\'uid\']}"><img src="{$avatarurl}" {$dimensions} alt="{$avatarurl}" onerror="this.src=\'/images/default_avatar.png\';"/></a>';

    $new_template['recentthread_last_avatar'] = '<a href="{$mybb->settings[\'bburl\']}/member.php?action=profile&uid={$thread[\'lastposteruid\']}"><img src="{$avatarurl}" {$dimensions} alt="{$avatarurl}" onerror="this.src=\'/images/default_avatar.png\';" /></a>';

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

    $new_template['recentthread_usercp'] = '<tr>
        <td valign="top" width="1"><input type="checkbox" class="checkbox" name="recentthread_show" id="recentthread_show" value="1" {$recentthreadcheck} /></td>
        <td><span class="smalltext"><label for="recentthread_show">{$lang->recentthread_show}</label></span></td>
        </tr>';

    // Now go through each of the themes
    $themequery = $db->simple_select("themes", "*");
    $sids = array();
    $first = true;
    while($theme = $db->fetch_array($themequery))
    {
        $properties = unserialize($theme['properties']);
        $sid = $properties['templateset'];
        if(!in_array($sid, $sids))
        {
            array_push($sids, $sid);

            foreach ($new_template as $title => $template)
            {
                $my_template = array(
                    'title' => $db->escape_string($title),
                    'template' => $db->escape_string($template),
                    'sid' => $sid,
                    'version' => '1800',
                    'dateline' => TIME_NOW);
                $db->insert_query('templates', $my_template);
                if($first)
                {
                    $my_new_template = array(
                        "title" => $db->escape_string($title),
                        "template" => $db->escape_string($template),
                        "sid" => -2,
                        "version" => "1814",
                        "dateline" => TIME_NOW
                    );
                    $db->insert_query("templates", $my_new_template);
                }
            }
            $first = false;
        }
    }

    // Stylesheet updates are required
    require_once MYBB_ROOT . $config['admin_dir'] . "/inc/functions_themes.php";
    $stylesheetquery = $db->simple_select("themestylesheets", "*", "name='thread_status.css'");
    while($stylesheet = $db->fetch_array($stylesheetquery))
    {
        $attachedto = explode("|", $stylesheet['attachedto']);
        if(!in_array("index.php", $attachedto))
        {
            $update_stylesheet['attachedto'] = $stylesheet['attachedto'] . "|index.php";
            $db->update_query("themestylesheets", $update_stylesheet, "sid=" . $stylesheet['sid']);
            update_theme_stylesheet_list($stylesheet['tid'], false, false);
        }
    }

    require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
    find_replace_templatesets('index', "#" . preg_quote('{$forums}') . "#i", "{\$forums}\n<div id=\"recentthreads\">{\$recentthreadtable}</div>");
    find_replace_templatesets('index', "#" . preg_quote('{$headerinclude}') . "#i", "{\$headerinclude}\n{\$recentthread_headerinclude}");
    find_replace_templatesets('usercp_options', "#" . preg_quote('{$board_style}') . "#i", "{\$recentthread_option}\n{\$board_style}");
}

function recentthread_templates_uninstall()
{
    global $db;
    $template_array = array("recentthread", "recentthread_thread", "recentthread_avatar", "recentthread_last_avatar", "recentthread_headerinclude", "recentthread_usercp");
    $string = "";
    $comma = "";
    foreach($template_array as $name)
    {
        $string .= $comma . "'" . $name . "'";
        $comma = ",";
    }
    $db->delete_query("templates", "title IN(" . $string . ")");
    $db->delete_query("templategroups", "prefix='recentthread'");
    require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
    find_replace_templatesets('index', "#" . preg_quote("\n{\$recentthread_headerinclude}") . "#i", '');
    find_replace_templatesets('index', "#" . preg_quote("\n<div id=\"recentthreads\">{\$recentthreadtable}</div>") . "#i", '');
    find_replace_templatesets('usercp_options', "#" . preg_quote("{\$recentthread_option}\n") . "#i", '');
}
