<?
error_reporting(E_ALL);

define('IN_PHPBB', true);

/* functions */
function parse($message) {
	global $data , $board_config, $userdata;
	$bbcode_uid = $data['bbcode_uid'];
	if ( !$board_config['allow_html'] && $data['enable_html']){
		$message = preg_replace('#(<)([\/]?.*?)(>)#is', "&lt;\\2&gt;", $message);
	}
	//
	// Parse message and/or sig for BBCode if reqd
	//
if ( $board_config['allow_bbcode'] && $bbcode_uid != ''){
	$message = ( $board_config['allow_bbcode'] ) ? bbencode_second_pass($message, $data['bbcode_uid']) : preg_replace('/\:[0-9a-z\:]+\]/si', ']', $message);
	}
	$message = make_clickable($message);
	if ( $board_config['allow_smilies'] && $data['enable_smilies'])	$message = smilies_pass($message);
	return  str_replace("\n", "\n<br />\n", $message);
}




$phpbb_root_path = './';
include($phpbb_root_path . 'extension.inc');
include($phpbb_root_path . 'common.'.$phpEx);
include($phpbb_root_path . 'includes/constants.'.$phpEx);
include($phpbb_root_path . 'includes/bbcode.'.$phpEx);




$db = new sql_db($dbhost, $dbuser, $dbpasswd, $dbname, false);
if(!$db->db_connect_id)
{
   die("Could not connect to the database");
}


//
// Extracting variables grom GET array if register globals turned to ON
//

if (ini_get('register_globals') != 1)
{   
   if (version_compare(phpversion(), "4.1.0") >= 0 )
      {
      extract($_GET);
      } else {
      extract($HTTP_GET_VARS);
      }
}

$dataSQL = "SELECT * FROM ". CONFIG_TABLE;
   
   $feedData = array();
   $res = $db->sql_query($dataSQL);
   while ($data = $db->sql_fetchrow($res) )
   {
      if ($data['config_name'] == "board_email") $data['config_value'] = str_replace("@", "(+@+)", $data['config_value']);
      $board_config[$data['config_name']]=$data['config_value'];
   }
   
//
// Getting a user data if any exist 
// password can be encrypted or no.
//

if ((isset($id) && is_numeric(trim($id))) && isset($password))
{
   
   $SQL = "SELECT * FROM ". USERS_TABLE ." WHERE user_active = '1' AND (user_password = MD5('".htmlentities($password)."') OR user_password = '".htmlentities($password)."') AND user_id <> " . ANONYMOUS ." AND user_id = '$id'";

   if($db->sql_numrows($db->sql_query($SQL)) == 1) {
      $userdata = $db->sql_fetchrow($db->sql_query($SQL));
   } else {
      $SQL = "SELECT * FROM ". USERS_TABLE ." WHERE  user_id = " . ANONYMOUS;
      if($db->sql_numrows($db->sql_query($SQL)) == 1) $userdata = $db->sql_fetchrow($db->sql_query($SQL)); 
   }
} else {
   $SQL = "SELECT * FROM ". USERS_TABLE ." WHERE  user_id = " . ANONYMOUS;
   if($db->sql_numrows($db->sql_query($SQL)) == 1) $userdata = $db->sql_fetchrow($db->sql_query($SQL)); 
}
$userdata['session_logged_in'] = true;

if (!file_exists(@phpbb_realpath($phpbb_root_path . 'language/lang_' . $board_config['default_lang'] . '/lang_main.'.$phpEx)) )	{
   $board_config['default_lang'] = 'english';
}
include($phpbb_root_path . 'language/lang_' . $board_config['default_lang'] . '/lang_main.' . $phpEx);
//
// Selecting a forums id to view
//
$forums = array();

if (!empty($c) && is_numeric($c))
{
   $select2view = "SELECT f.forum_id FROM  ". FORUMS_TABLE ." f WHERE f.cat_id = ". $c ;
   
   $res = $db->sql_query($select2view);
   while ($data = $db->sql_fetchrow($res) )
   {
      array_push($forums,$data['forum_id']);
   }
}
elseif(!empty($f) && is_numeric($f))
{
   $forumauth = auth(AUTH_READ, $f, $userdata);
   if ($forumauth["auth_read"] == true){
      array_push($forums,$f);
   }
   unset($forumauth);
}
else
{
   
   $select2view = "SELECT f.forum_id FROM ". FORUMS_TABLE ." f ";
   $res = $db->sql_query($select2view);
   while ($data = $db->sql_fetchrow($res) )
   {
      $forumauth = auth(AUTH_READ, $data['forum_id'], $userdata);
      if ($forumauth["auth_read"] == true){
         array_push($forums,$data['forum_id']);
      }
      unset($forumauth);
   }
}



$templateSQL = "SELECT th.template_name, c.* FROM ". THEMES_TABLE ." th, ". CONFIG_TABLE ." c WHERE th.themes_id = c.config_value AND  c.config_name = 'default_style'";
$rssConf = $db->sql_fetchrow($db->sql_query($templateSQL));

$template_path = 'templates/' ;
$template_name = $rssConf['template_name'];
$template = new Template($phpbb_root_path . $template_path . $template_name );  

   

$forumInList = "";
for ($i=0; $i < count($forums); $i++)
{
   $forumInList .= $forums[$i];
   if ($i != (count($forums) - 1)) $forumInList .=", ";
}
$topics_sql = "SELECT t.topic_id  FROM  ". TOPICS_TABLE ." t  WHERE t.forum_id IN (". $forumInList .") ORDER BY t.topic_id DESC LIMIT 0, 20";
$tres =  $db->sql_query($topics_sql);
$tarr = array();
while ($tdata = $db->sql_fetchrow($tres)) $tarr[] = $tdata['topic_id'];

if (count($tarr) == 0)
{
   $template->assign_block_vars('nofeeditems', true);
} else {
   $topicInList = "";
   for ($i=0; $i < count($tarr); $i++){
   $topicInList .= $tarr[$i];
   if ($i != (count($tarr) - 1)) $topicInList .=", ";
   }
   
}
 $topicInList;

$posts_sql = "SELECT ptt.post_text, pt.enable_bbcode, pt.enable_html, pt.enable_sig, pt.enable_smilies,   ptt.bbcode_uid, t.topic_title, t.topic_time, t.topic_id  FROM  ". POSTS_TEXT_TABLE ." ptt ,". POSTS_TABLE ." pt , ". TOPICS_TABLE ." t  WHERE ptt.post_id  = pt.post_id AND pt.post_id  = t.topic_id AND pt.topic_id IN (". $topicInList .") ORDER BY pt.topic_id DESC LIMIT 0, 20";

header("Content-type: text/xml");
$template->set_filenames(array('rss' => 'rss20.tpl'));
$board_config['ext'] = $phpEx;
$template->assign_vars($board_config);

$dates = array();
if($db->sql_numrows($db->sql_query($posts_sql)) != 0)
{
       $res = $db->sql_query($posts_sql);
      while ($data = $db->sql_fetchrow($res) )
         {
           if (!isset($dates['lastbuid'])) $dates['lastbuid'] = $data['post_time'];
           $template->assign_block_vars('feeditems',
            array(
               "TOPIC_TITLE" => $data['topic_title'],
               "TOPIC_TEXT" => parse($data['post_text']),
               "TOPIC_ID" => $data['topic_id'],
               "FExt" => $phpEx,
               "TOPIC_TIME" => date("D, d M Y G:i:s T", $data['post_time'])
               )
            );
         }  
} else {
   $template->assign_block_vars('nofeeditems', true);
            
}
$dates['lastbuid'] = date("D, d M Y G:i:s T", $dates['lastbuid']);
$dates['pub'] = date("D, d M Y G:i:s T", time());
$template->assign_vars($dates);


$template->pparse('rss');

?>