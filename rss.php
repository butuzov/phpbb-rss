<?
error_reporting(E_ALL);

define('IN_PHPBB', true);

/* functions */
function parse($message) {
	global $data , $board_config, $userdata;
	$bbcode_uid = $data['bbcode_uid'];
	
	$message = preg_replace('#(<)([\/]?.*?)(>)#is', "&lt;\\2&gt;", $message);
	
	
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
if(!$db->db_connect_id){
   die("Could not connect to the database");
}


//
// Extracting variables grom GET array if register globals turned to ON

if (ini_get('register_globals') != 1){   
	if (version_compare(phpversion(), "4.1.0") >= 0 ){
      extract($_GET);
    } else {
      extract($HTTP_GET_VARS);
    }
}

// BoardConfig configuraton
$board_config['pub'] = date("D, d M Y G:i:s T", time());
$board_config['ext'] = $phpEx;
$board_config['board_email'] = str_replace("@", "-at-", $board_config['board_email']);
$board_config['board_email'] = str_replace(".", "-dot-", $board_config['board_email']);


//Language
if (!file_exists(@phpbb_realpath($phpbb_root_path . 'language/lang_' . $board_config['default_lang'] . '/lang_main.'.$phpEx)) )	{
   $board_config['default_lang'] = 'english';
}
include($phpbb_root_path . 'language/lang_' . $board_config['default_lang'] . '/lang_main.' . $phpEx);

$board_config['encoding'] = $lang['ENCODING'];
//
// Getting a user data if any exist 
// password can be encrypted or no.
//

if ((isset($id) && is_numeric(trim($id))) && isset($password)){
	$SQL = "SELECT * FROM ". USERS_TABLE ." WHERE user_active = '1' AND (user_password = MD5('".htmlentities($password)."') OR user_password = '".htmlentities($password)."') AND user_id <> " . ANONYMOUS ." AND user_id = '$id'";
	if($db->sql_numrows($db->sql_query($SQL)) == 1) {
      $userdata = $db->sql_fetchrow($db->sql_query($SQL));
	} else {
      $SQL = "SELECT * FROM ". USERS_TABLE ." WHERE  user_id = " . ANONYMOUS;
      if($db->sql_numrows($db->sql_query($SQL)) == 1) $userdata = $db->sql_fetchrow($db->sql_query($SQL)); 
	}
} else {
	$SQL = "SELECT * FROM ". USERS_TABLE ." WHERE  user_id = " . ANONYMOUS;
	if($db->sql_numrows($db->sql_query($SQL)) == 1) {
		$userdata = $db->sql_fetchrow($db->sql_query($SQL));
	}
}
$userdata['session_logged_in'] = true;


//
// Selecting a forums id to view
//
$forums = array();

if (!empty($c) && is_numeric($c))
{
    array_push($forums,$data['forum_id']);
   
} elseif(!empty($f) && is_numeric($f)){
      array_push($forums,$f);
} else {
	$select2view = "SELECT f.forum_id FROM ". FORUMS_TABLE ." f ";
	$res = $db->sql_query($select2view);
	while ($data = $db->sql_fetchrow($res)){
		array_push($forums,$data['forum_id']);
	}
}

$allowedForums = array();
foreach ($forums as $forum_id) {
	$forumauth = auth(AUTH_READ, $forum_id, $userdata);
    if ($forumauth["auth_read"] == true){
         array_push($allowedForums,$forum_id);
    }
    unset($forumauth);
}

// geting a topics numbers that need to be show in rss feed

$topicsSql = "SELECT t.topic_id FROM  ". TOPICS_TABLE ." t  WHERE t.forum_id IN (".implode(",", $allowedForums).") ORDER BY t.topic_id DESC LIMIT 0, 20";
$topicsRes =  $db->sql_query($topicsSql);
$topicsArray= array();
while ($topicsData = $db->sql_fetchrow($topicsRes)) $topicsArray[] = $topicsData['topic_id'];

$postidsSql = "SELECT DISTINCT pt.post_id FROM  ". POSTS_TABLE ." pt, ". TOPICS_TABLE ." t  WHERE pt.topic_id = t.topic_id AND t.topic_id IN (".implode(',', $topicsArray).") GROUP BY pt.topic_id DESC LIMIT 0, 20";

$postidsRes =  $db->sql_query($postidsSql);
$postidsArray= array();
while ($postidsData = $db->sql_fetchrow($postidsRes)) $postidsArray[] = $postidsData['post_id'];


$postsSql = "SELECT ptt.post_text, pt.enable_bbcode, pt.enable_html, pt.enable_sig, pt.enable_smilies,   ptt.bbcode_uid, t.topic_title, t.topic_time, t.topic_id  FROM ". POSTS_TABLE ." pt, ". POSTS_TEXT_TABLE ." ptt , ". TOPICS_TABLE ." t  WHERE t.topic_id = pt.topic_id AND ptt.post_id = pt.post_id AND pt.post_id IN (".implode(',', $postidsArray).") ORDER BY pt.post_id DESC ";


$templateSQL = "SELECT th.template_name, c.* FROM ". THEMES_TABLE ." th, ". CONFIG_TABLE ." c WHERE th.themes_id = c.config_value AND  c.config_name = 'default_style'";
$rssConf = $db->sql_fetchrow($db->sql_query($templateSQL));
$template = new Template($phpbb_root_path . 'templates/' . $rssConf['template_name'] );  
$template->set_filenames(array('rss' => 'rss20.tpl'));
$board_config['template_name']=$rssConf['template_name'];

if($db->sql_numrows($db->sql_query($postsSql)) != 0){
	
    $postsRes = $db->sql_query($postsSql);
	
    while ($data = $db->sql_fetchrow($postsRes)){
           
	    if (!isset($dates['lastbuid'])) {
			
			$dates['lastbuid'] = $data['post_time'];
			
	    }
		   
        $template->assign_block_vars('feeditems', array("TOPIC_TITLE" => $data['topic_title'],
            "TOPIC_TEXT" => parse($data['post_text']),"TOPIC_ID" => $data['topic_id'],
            "TOPIC_TIME" => date("D, d M Y G:i:s T", $data['topic_time']))
        );
    }  
   $board_config['lastbuid'] = date("D, d M Y G:i:s T", $dates['lastbuid']);
   
} else {
	
   $board_config['lastbuid'] = $board_config['pub'];
   $template->assign_block_vars('nofeeditems', true);
   
}

$template->assign_vars($board_config);

header("Content-type: text/xml");
$template->pparse('rss');

?>