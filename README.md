#H1 Recent Threads On Index


Adds a section to your index page for recent threads.

**Installation**

1) Upload inc/plugins/recentthread.php to your inc/plugins directory.

2) Install from the Admin CP.

3) Verify that the variable {$recentthread_headerinclude} is in the head section of your index template.  Verify that the variable {$recentthreadtable} is below {$forums}, unless you want it elsewhere on the page.

4) Configure the settings for recent threads.

Compatibility: 1.8 only.  This is due to functions that 1.8 uses in order to format avatars.  If you do not care about avatars, you can comment out the lines and change the compatibility line to 16*.

**Upgrading from 3.0**

1) Deactivate the plugin from the ACP.
2) Activate the plugin from the ACP.
3) Check your index template to make sure only one instance of {$recentthread_headerinclude} and {$recentthreadtable} exist.

**Customization**

The following two variables are not currently used, but will work in case you want them:  
{$lastpostdate} - This uses the default date format on the last post.  
{$lastposttime} - This uses the default time format on the last post.

By default the page will update the recent threads every 30 seconds and stop refreshing ater 15 minutes of no activity.  This can be altered under global templates->recentthread_headerinclude.  On the line that begins with var refresher = Change 30000 to the number of milliseconds you want it to refresh at.  60000 = 1 minute.  The next line controls how many milliseconds before it will stop refreshing at.  5 minutes = 300000; 10 minutes = 600000.  Note that you do not use commas.
