## Changelog

3.1.9
 - Support new attribute `data-matomo-mask` which works similar to `data-piwik-mask` but additionally allows to mask content of elements.

3.1.8
 - Support new CSS rendering classes matomoHsr, matomoHeatmap and matomoSessionRecording
 - For input text fields prefer a set value on the element directly
 - Differentiate between scrolling of the window and scrolling within an element (part of the window)
 - Replay in the recorded session when a user is scrolling within an element

3.1.7
 - Make sure validating URL works correctly with HTML entities
 - Prevent possible fatal error when opening manage screen for all websites

3.1.6
 - Renamed Piwik to Matomo

3.1.5
 - Fix requested stylesheet URLs were requested lowercase when using a relative base href in the recorded page
 - Show more accurate time on page and record pageviews for a longer period in case a user is not active right away.

3.1.4
 - Prevent target rules in heatmap or session recording to visually disappear under circumstances when not using the cancel or back button.
 - Respect URL prefix (eg www.) when replaying a session recording, may fix some displaying issues if website does not work without www.
 - Improved look of widgetized session recording 

3.1.3
 - Make Heatmap & Session Recording compatible with canvas and webgl libraries like threejs and earcut
 - Better detected of the embedded heatmap height 
 - Fix scroll heatmap did not paint the last scroll section correctly
 - It is now possible to configure the sample limits in the config via `[HeatmapSessionRecording] session_recording_sample_limits = 50,100,...`

3.1.2
 - Added URL to view heatmap and to replay a session recording to the API response
 - Fix widgetized URL for heatmaps and sessions redirected to another page when authenticated via token_auth
 
3.1.1
 - Better error code when a site does not exist
 - Fix configs.php may fail if plugins directory is a symlink
 - Available sessions are now also displayed in the visitor profile

3.1.0
 - Added autoplay feature for page views within a visit
 - Added possibility to change replay speed
 - Added possibility to skip long pauses in a session recording automatically
 - Better base URL detection in case a relative base URL is used

3.0.15
 - Fix only max 100 heatmaps or session recordings were shown when managing them for a specific site.
 - Mask closing body in embedded page so it won't be replaced by some server logic

3.0.14
 - Make sure to find all matches for a root folder when "equals simple" is used
 
3.0.13
 - Fix a custom set based URL was ignored.
 
3.0.12
 - Fix session recording stops when a user changes a file form field because form value is not allowed to be changed.
 
3.0.11
 - Improve the performance of a DB query of a daily task when cleaning up blob entries.
 
3.0.10
 - Improve the performance of a DB query of a daily task
 - Respect the new config setting `enable_internet_features` in the system check

3.0.9
 - Make sure page rules work fine when using HTML entities

3.0.8
 - Fix possible notice when tracking
 - Avoid some logs in chrome when viewing a heatmaps or session recordings
 - Always prefer same protocol when replaying sessions as currently used

3.0.7
 - When using an "equals exactly" comparison, ignore a trailing slash when there is no path set
 - Let users customize if the tracking code should be included only when active records are configured

3.0.6
 - Fix link to replay session in visitor log may not work under circumstances

3.0.5
 - More detailed "no data message" when nothing has been recorded yet
 - Fix select fields were not recorded

3.0.4
 - Only add tracker code when heatmap or sessions are actually active in any site
 - Added index on site_hsr table
 - Add custom stylesheets for custom styling

3.0.3
 - Add system check for configs.php
 - On install, if .htaccess was not created, create the file manually

3.0.2
 - Enrich system summary widget
 - Show an arrow instead of a dash between entry and exit url
 - Added some German translations
 
3.0.1
 - Updated translations

3.0.0
 - Heatmap & Session Recording for Piwik 3
