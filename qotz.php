<?php
/*
Plugin Name: QOTZ
Version: 0.2
Plugin URI: http://www.beliefmedia.com/wp-plugins/qotz.php
Description: Displays a random quote utilising our QOTZ.net API. Use as &#91;qotz&#93;. Wrap the attribution in html tag with &#91;qotz tags="strong,em"&#93;. Change the separator (between quote and subject) with &#91;qotz sep="::"&#93; (defaults to em dash).
Author: Martin Khoury
Author URI: http://www.beliefmedia.com/
*/


function beliefmedia_qotz($atts) {
  $atts = shortcode_atts(array(
    'sep' => '&mdash;',
    'tags' => false,
    'p' => false,
    'temp' => 360, /* If API offline, number of seconds to wait before trying again */
    'offline' => 'The QOTZ <a href="http://www.qotz.net/" target="_blank">API</a> is temporarily offline. We\'ll try again in a few minutes.',
    'cache' => 3600
  ), $atts);

 $transient = 'bmq_' . md5(serialize($atts));
 $cachedposts = get_transient($transient);

 if ($cachedposts !== false) {
 return $cachedposts;

 } else {

   /* Construct tag(s) for attribution */
   if ($atts['tags']) {
    $tags = explode(',', $atts['tags']);
      foreach($tags as $tag) {
       $htmltag .= '<' . $tag . '>';  
      }
    $att_tags = $htmltag;
    $att_tags_closing = str_replace('<', '</', "$att_tags");
   }

   $json = @file_get_contents('http://api.beliefmedia.com/quotes/random.php');
   if ($json !== false) $data = json_decode($json, true);

   if ($data['status'] == '200') {

     $text = (string) $data['data']['quote'];
     $author = (string) $data['data']['author'];
     $genre = (string) $data['data']['genre'];

     /* Format the quote to your liking */
     $return = ($atts['tags'] !== false) ? '"' . $text . '" ' . $atts['sep'] . ' ' . $att_tags . $author . $att_tags_closing : '"' . $text . '" ' . $atts['sep'] . ' ' . $author;
     if ($atts['p'] !== false) $return = '<p>' . $return . '</p>';

     /* Set transient */
     set_transient($transient, $return, $atts['cache']);

   } else {
     $return = ($atts['p'] !== false) ? '<p>' . $atts['offline'] . '</p>' : $atts['offline'];
     set_transient($transient, $atts['offline'], $atts['temp']);
   }
  }
 return $return;
}
add_shortcode('qotz','beliefmedia_qotz');


/*
	Menu Links
*/


function beliefmedia_qotzs_action_links($links, $file) {
  static $this_plugin;
  if (!$this_plugin) {
   $this_plugin = plugin_basename(__FILE__);
  }

  if ($file == $this_plugin) {
	$links[] = '<a href="http://www.beliefmedia.com/wp-plugins/qotz.php" target="_blank">Support</a>';
	$links[] = '<a href="http://www.qotz.net/" target="_blank">QOTZ</a>';
  }
 return $links;
}
add_filter('plugin_action_links', 'beliefmedia_qotzs_action_links', 10, 2);



/*
	Delete Transient Data on Deactivation
*/

	
function remove_beliefmedia_qotzs_options() {
  global $wpdb;
   $wpdb->query("DELETE FROM $wpdb->options WHERE `option_name` LIKE ('_transient%_beliefmedia_qotz_%')" );
   $wpdb->query("DELETE FROM $wpdb->options WHERE `option_name` LIKE ('_transient_timeout%_beliefmedia_qotz_%')" );
}
register_deactivation_hook( __FILE__, 'remove_beliefmedia_qotzs_options' );


/*
	Uncomment if shortcode isn't working in widgets
*/


// add_filter('widget_text', 'do_shortcode');