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

    $new_template['recentthread'] = '<div id="recentthreads">
<table border="0" cellspacing="1" cellpadding="6" class="tborder" style="clear: both;max-height:300px">
<thead>
    <tr>
    <td class="thead{$expthead}" colspan="{$colspan}" style="text-align:left; font-size: 10pt;">
   
<div><b>~ <a href="{$mybb->settings[\'bburl\']}/misc.php?action=recent_threads">{$lang->recentthreads_recentthreads}</a> ~</b></div>
</td>
    </tr>
</thead>
<tbody style="{$expdisplay}" id="cat_9999_e">
    <tr>
    <td class="tcat" colspan="3"><strong>{$lang->recentthreads_thread} / {$lang->recentthreads_author}</strong></td>
    <td class="tcat"><strong>{$lang->recentthreads_forum}</strong></td>
    <td class="tcat"><strong>{$lang->recentthreads_posts}</strong></td>
    <td class="tcat"><strong>{$lang->recentthreads_last_post}</strong></td>
{$modheader}
    </tr>
    {$recentthreads}
</tbody>
    </table>
</div>';

    $new_template['recentthread_thread'] = '<tr>
<td align="center" class="{$trow}{$thread_type_class}" width="2%"><span class="thread_status {$folder}" title="{$folder_label}">&nbsp;</span></td>
    <td class="{$trow}{$thread_type_class}" width="2%">{$icon}</td>
    <td class="{$trow}{$thread_type_class}">{$arrow}&nbsp;{$recentprefix}<span class="{$new_class}" id="tid_{$thread[\'tid\']}"><a href="{$mybb->settings[\'bburl\']}/showthread.php?tid={$thread[\'tid\']}">{$thread[\'subject\']}</a></span>&nbsp;&nbsp;{$thread[\'multipage\']}<br />{$create_string} {$thread[\'author\']}<br />{$posteravatar}</td>
    <td class="{$trow}{$thread_type_class}">{$recentthread_breadcrumbs}</td>
    <td class="{$trow}{$thread_type_class}"><a href="javascript:MyBB.whoPosted({$thread[\'tid\']});">{$thread[\'replies\']}</a></td>
    <td class="{$trow}{$thread_type_class}">{$lastposttimeago}<br />
    <a href="{$lastpostlink}">Last Post:</a> {$lastposterlink}<br />{$lastavatar}</td>
{$modcol}
    </tr>';

    $new_template['recentthread_avatar'] = '<a href="{$mybb->settings[\'bburl\']}/member.php?action=profile&uid={$thread[\'uid\']}"><img src="{$avatarurl}" {$dimensions} alt="{$avatarurl}" onerror="this.src=\'{$mybb->settings[\'bburl\']}/images/default_avatar.png\'" /></a>';

    $new_template['recentthread_last_avatar'] = '<a href="{$mybb->settings[\'bburl\']}/member.php?action=profile&uid={$thread[\'lastposteruid\']}"><img src="{$avatarurl}" {$dimensions} alt="{$avatarurl}" onerror="this.src=\'{$mybb->settings[\'bburl\']}/images/default_avatar.png\'" /></a>';

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

    $new_template['misc_recentthreads'] = '<html>
        <head>
        <title>{$lang->recentthreads_recentthreads}</title>
        {$headerinclude}
        <script> function update_tidlist(tid)
				{
					myid = document.getElementById(\'tid\' + tid);
					oldlist = document.getElementById(\'tidlist\').value;
					threadcount = parseInt(document.getElementById(\'threadcount\').value);
					if(myid.checked == true)
					{
						if(threadcount > 0)
						{
							newlist = oldlist + \',\' + tid;
						}
						else
						{
							newlist = tid;
						}
						threadcount += 1;
					}
					else
					{
						startlist = oldlist.split(\',\');
						newlist = \'\';
						y = 0;
						for(x in startlist)
						{
							if(startlist[x] != tid)
							{
								if(y > 0)
								{
									newlist = newlist + \',\' + startlist[x];
								}
								else
								{
									newlist = startlist[x];
									y = y + 1;
								}
							}
						}
						threadcount -= 1;
					}
					document.getElementById(\'tidlist\').value = newlist;
					document.getElementById(\'threadcount\').value = threadcount;
					document.getElementById(\'inline_go\').value = \'{$lang->go} (\' + threadcount + \')\'; 
				}
				
				function select_all()
				{
					myid = document.getElementById(\'select_all\');
					if(myid.checked == true)
					{
						masterlist = document.getElementsByClassName(\'thread_checkbox\');
						y = 0;
						newlist = \'\';
						for(x in masterlist)
						{
							if(y >= masterlist.length)
							{
								continue;
							}
							if(y > 0)
							{
								newlist = newlist + \',\' + document.getElementById(masterlist[x].id).value;
							}
							else
							{
								newlist = document.getElementById(masterlist[x].id).value;
							}
							y = y + 1;
							document.getElementById(masterlist[x].id).checked = true;
						}
					}
					else
					{
						y = 0;
						newlist = \'\';
						masterlist = document.getElementsByClassName(\'thread_checkbox\');
						for(x in masterlist)
						{
							if(y >= masterlist.length)
							{
								continue;
							}
							y = y + 1;
							document.getElementById(masterlist[x].id).checked = false;
						}
						y = 0;
					}
					document.getElementById(\'tidlist\').value = newlist;
					document.getElementById(\'threadcount\').value = y;
					document.getElementById(\'inline_go\').value = \'{$lang->go} (\' + y + \')\';
				}
                function clear_select_all()
                {
                    document.getElementById("select_all").checked = false;
                    select_all();
                }
                function new_page(modifier)
                {
                    var old_page = document.getElementById("current_page").value;
                    var current_page = parseInt(old_page) + parseInt(modifier);
                    if(current_page < 1)
                    {
                        current_page = 1;
                    }
                    document.getElementById("current_page").value=current_page;
                    if(current_page > 1)
                    {
                        document.getElementById("previous_page").style.visibility = "visible";
                    }
                    else
                    {
                        document.getElementById("previous_page").style.visibility = "hidden";
                    }
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
      	            xmlhttp.open("GET","xmlhttp.php?action=recent_threads&from=misc.php&page=" + current_page, true);
		            xmlhttp.send();
                }
</script>
<body>
{$header}
{$recentthreadtable}
<div class="pagination">
<a href="#previous" class="pagination_previous" onclick="new_page(-1)" id="previous_page" style="visibility: hidden">&laquo; {$lang->previous}</a><a href="#next" class="pagination_next" onclick="new_page(1)" id="next_page">{$lang->next} &raquo;</a>
</div>
<input name="current_page" id="current_page" type="numeric" style="visibility:hidden" value="1" />
{$moderator_form}
{$footer}
</body>
</html>';

    $new_template['misc_recentthreads_mod_col'] = '<td class="{$trow}"><input type="checkbox" name="tid{$thread[\'tid\']}" value="{$thread[\'tid\']}" id="tid{$thread[\'tid\']}" class="thread_checkbox" onclick="update_tidlist({$thread[\'tid\']})" /></td>';

    $new_template['misc_recentthreads_mod_header'] = '<td class="tcat"><input type="checkbox" class="select_all" name="select_all" id="select_all" onclick="select_all()" /></td>';

    $new_template['mis_recentthreads_moderation'] = '<form action="misc.php?action=recent_threads" method="post">
<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
<input type="hidden" name="modtype" value="inlinethread" />
<span class="smalltext"><strong>{$lang->inline_thread_moderation}</strong></span>
<select name="modaction">
<option value="multiapprovethreads">{$lang->approve_threads}</option>
<option value="multiunapprovethreads">{$lang->unapprove_threads}</option>
<option value="multiclosethreads">{$lang->close_threads}</option>
<option value="multiopenthreads">{$lang->open_threads}</option>
<option value="multistickthreads">{$lang->stick_threads}</option>
<option value="multiunstickthreads">{$lang->unstick_threads}</option>
<option value="multisoftdeletethreads">{$lang->soft_delete_threads}</option>
<option value="multirestorethreads">{$lang->restore_threads}</option>
</select>
<input type="hidden" name="tids" id="tidlist" value="0" />
<input type="hidden" name="threadcount" id="threadcount" value="0" />
<input type="submit" class="button" name="go" value="{$lang->inline_go} (0)" id="inline_go" />&nbsp;
<input type="button" onclick="clear_select_all()" value="{$lang->clear}" class="button" />
</form>
<script type="text/javascript">
<!--
	var go_text = "{$lang->inline_go}";
	var all_text = "{$threadcount}";
	var inlineType = "forum";
	var inlineId = 0;
var inclinecount = 0;
// -->
</script>
<br />';

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
    $template_array = array("recentthread", "recentthread_thread", "recentthread_avatar", "recentthread_last_avatar", "recentthread_headerinclude", "recentthread_usercp",
        "misc_recentthreads", "misc_recentthreads_mod_col", "misc_recentthreads_mod_header", "misc_recentthreads_moderation");
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
