<?xml version="1.0" encoding="{encoding}" ?>

<rss version="2.0">
 <channel>
  <title>{sitename}</title>
   <description>{site_desc}</description>
   <link>http://{server_name}/</link>
   <language>en-us</language>

   <lastBuildDate>{lastbuid}</lastBuildDate>
   <pubDate>{pub}</pubDate>

   <docs>http://i.am.samuray.com.ua/projects/phpbb_rss/</docs>
   <generator>phpBB/RSS Exporter</generator>
   <ttl>1440</ttl>
   <webMaster> {board_email}</webMaster>
   <managingEditor>{board_email}</managingEditor>
   <copyright>Copyrights  2001-2005 by phpBB, phpBB/RSS copyrights Oleg Butuzov</copyright>
      <image>
       <url>http://{server_name}{script_path}phpbbrss.gif</url>
       <title>{site_desc}!</title>
       <link>http://{sitename}/</link>
       <width>80</width>
       <height>15</height>
      </image>
    <!-- BEGIN feeditems --> 
	<item>
		<title>{feeditems.TOPIC_TITLE}</title>
		<guid isPermaLink="true">http://{server_name}{script_path}viewtopic.{ext}?t={feeditems.TOPIC_ID}</guid>
		<link>http://{server_name}{script_path}viewtopic.{ext}?t={feeditems.TOPIC_ID}</link>
		<comments>http://{server_name}{script_path}viewtopic.{ext}?t={feeditems.TOPIC_ID}</comments>
		<description><![CDATA[
		{feeditems.TOPIC_TEXT}
		]]></description>
		<pubDate>{feeditems.TOPIC_TIME}</pubDate>
		<author></author>
	</item> 
	<!-- END feeditems -->  
    <!-- BEGIN nofeeditems -->  
	<item>
     <title>Error: forum access not allowed</title>
     <description>
	 	Forums that do not allow public access cannot (yet) be monitored. In case you do not understand this message, please contact the WebMaster.
	 </description>
     <link>http://{server_name}/{script_path}/</link>
    </item>
	<!-- END nofeeditems -->	  
	</channel>
</rss>