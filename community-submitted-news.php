<?php
//session_start();
/*
    Plugin Name: Community Submitted News
    Plugin URI: http://studioslice.com
    Description: Allows you to let your readers submit stories to post on your site.
    Version: 1.1
    Author: Gregary Dean
    Author URI: http://studioslice.com


    Copyright 2010  Gregary M. Dean  (email : greg@studioslice.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
if (!function_exists('add_action')){
    require_once("../../../wp-config.php");
}

add_shortcode('csn_news_form','csn_show_form');
add_action('admin_menu', 'csn_plugin_menu');
add_action('wp_head', 'csn_head');
add_action('admin_head', 'csn_head');
add_action('init', 'init_method');
register_activation_hook(__FILE__,'csn_install_news');

$func = 'csn_'.$_POST['action'].'_story';

if(function_exists($func)){
    call_user_func($func, $_POST['id']);
}
else{
    
    switch($_SERVER['REQUEST_METHOD']){
        case isset($_POST['csn_save']):
            csn_update_news($_POST);
            break;
        case isset($_POST['csn_publish']):
            csn_publish_story($_POST['csn_story_id']);
            break;
        case isset($_POST['csn_captcha_code']):
            csn_add_news($_POST);
            break;
    }
}

function init_method(){
    wp_enqueue_script('jquery');
}

function csn_plugin_menu(){
    add_menu_page('Community Submitted News', 'Community Submitted News', 8, __FILE__, 'csn_read_news');
}

/**
 * Gets story that was submitted by using the id of the story
 *
 * @global wpdb object $wpdb
 * @param string $id
 * @return array
 */
function csn_get_story($id){
    global $wpdb;
    $tablename = $wpdb->prefix.'csn_submission';
    $query = 'SELECT * FROM '.$tablename.' WHERE id = '.$id;

    return $wpdb->get_results($query);
}
/**
 * Displays story in editor for it to be edited
 *
 * @param string $id - ID of story
 * 
 */
function csn_view_story($id){
    $story = csn_get_story($id);
    $uri = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'].'?'.$_GET['page'];
    if(isset($_POST['csn_publish'])){
        echo '<div id="message" class="updated fade"><p>This story has been published. To edit further go to the Edit Posts page or return to the <a href="'.$_SERVER["HTTP_REFERER"].'">View Stories</a> page to publish more stories</p></div>';
    }
    else if(isset($_POST['csn_save'])){
        echo '<div id="message" class="updated fade"><p>This story has been updated</p></div>';
    }

    ?>
    <div id='poststuff'>
        <div id="csn_submission_content" class='wrap'>
            <h2>Edit Submission</h2>
            <form id='csn_story' name='csn' action='' method='post'>
                <input type='hidden' name='csn_story_id' value='<?php echo $story[0]->id ?>' />
                <div id='titlediv'>
                    <input type='text' id='title' name='csn_user_title' value='<?php echo $story[0]->title?>'>
                </div>
                <?
                add_filter('user_can_richedit', create_function ('$a', 'return false;') , 50);	// Disable visual editor
                the_editor(stripslashes($story[0]->story), 'content', '', false, 5);
                add_filter('user_can_richedit', create_function ('$a', 'return true;') , 50);	// Enable visual editor
                ?>
                <div id='csn_submission_details' class='postbox stuffbox'>
                    <h3>Submission details</h3>
                    <fieldset>
                        <label for='csn_submission_author'>Submitter Name:</label>
                        <input type='text' name='csn_user_name' value='<?php echo $story[0]->name?>'/>
                    </fieldset>
                    <fieldset>
                        <label for='csn_submission_author_email'>Submitter Email:</label>
                        <input type='text' name='csn_user_email' value='<?php echo $story[0]->email?>'/>
                    </fieldset>
                    <fieldset id='major-publishing-actions'>
                        <input name='csn_save' id='save_post' class='button button-highlighted' type='submit' value='Save'/>
                        <input name='csn_publish' id='publish' class='button-primary'type='submit' value='Publish' />
                    </fieldset>
                </div>
            </form>
        </div>
    </div>
    <?
}

/**
 * Includes css file
 */
function csn_head(){
    ?>
        <script type="text/javascript" src="<?php echo get_option('siteurl').'/wp-content/plugins/community-submitted-news/csn_js.js' ?>"> </script>
        <style type='text/css'>
            @import "<?php echo get_option('siteurl').'/wp-content/plugins/community-submitted-news/csn_style.css' ?>";
        </style>
    <?
    
}

/**
 * Gets and displays stories from database and allows admin to publish or edit stories
 *
 * @global object $wpdb
 */
function csn_read_news(){
    if( isset($_GET['action']) ){
        switch($_GET['action']){
            case 'csn_view':
                csn_view_story($_GET['id']);
                break;
        }
    }
    else{
        global $wpdb;
        $category = get_category_by_slug(get_option('csn_category_slug'));        
        $tablename = $wpdb->prefix.'csn_submission';
        $query = 'SELECT * FROM '.$tablename.' WHERE approve = 0';
        $news = $wpdb->get_results($query);
        $page = $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];

        echo '<div id="csn_msg" class="updated fade"></div>';
        echo '<table class="widefat post fixed" id = "csn">';
        echo '<thead>';
        echo '<tr><th class="name">Name</th><th class="title">Title</th><th class="story">Story</th><th class="action">View Story</th><th class="action">Publish Story</th><th class="action">Remove Story</th></tr><tbody>';
        foreach($news as $story){
            echo '<tr class="row">';
            echo '<td>'.$story->name.'</td>';
            echo '<td>'.$story->title.'</td>';
            echo '<td>'.$story->story.'</td>';
            echo '<td><a href="http://'.$page.'&action=csn_view&id='.$story->id.'">View</a></td>';
            echo '<td><a class="publish" id="'.$story->id.'" href="http://'.$page.'&action=csn_publish&id='.$story->id.'">Publish</a></td>';
            echo '<td><a class="delete" id="'.$story->id.'" href="http://'.$page.'&action=csn_remove&id='.$story->id.'">Remove</a></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
}

/**
 * Saves user submitted story to database
 *
 * @global object $wpdb
 * @param array $csn
 */
function csn_add_news($csn){
    global $wpdb;
    include_once ABSPATH.'wp-content/plugins/community-submitted-news/securimage/securimage.php';
    if (!function_exists('check_admin_referer')){
        require_once(ABSPATH."wp-includes/pluggable.php");
    }
    $securimage = new Securimage();
    if( !$securimage->check($_POST['csn_captcha_code']) ) {
        die('The captcha text you entered was incorrect.  Please correct it.');
    }
    else{
        $tablename = $wpdb->prefix.'csn_submission';
        $params = array(
            'name' => $csn['csn_user_name'],
            'story' => $csn['csn_user_story'],
            'approve' => '0',
            'title' => $csn['csn_user_title'],
            'email' => $csn['csn_user_email']
        );
        check_admin_referer('community_submitted_news_add_news');
        $wpdb->insert($tablename, $params);
    }
}

/**
 * Updates a user submitted story
 *
 * @global object $wpdb
 * @param array $csn
 */
function csn_update_news($csn){
    global $wpdb;

    $tablename = $wpdb->prefix.'csn_submission';
    $params = array(
        'name' => $csn['csn_user_name'],
        'story' => $csn['content'],
        'title' => $csn['csn_user_title']
    );
    $where = array('id' => $csn['csn_story_id']);
    $wpdb->update($tablename, $params, $where);
}

/**
 * Installs plugin
 *
 * @global object $wpdb
 */
function csn_install_news(){
    global $wpdb;
    $tablename = $wpdb->prefix.'csn_submission';
    $category_table = $wpdb->prefix.'terms';
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $sql = 'CREATE TABLE '.$tablename.'(
                id int unsigned auto_increment not null,
                name varchar(100),
                story text,
                email varchar(200),
                title varchar(255),
                approve int(1) unsigned,
                PRIMARY KEY  (id)
                )';

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        $cat_array = array('cat_ID' => 0,
              'cat_name' => 'Community Submitted News',
              'category_description' => '',
              'category_nicename' => 'community_submitted_news' ,
              'category_parent' => ''
        );
        
        wp_insert_category($cat_array);
        add_option('csn_plugin_version', '1.0');
        add_option('csn_category_slug', 'community_submitted_news');
    }
}

/**
 * Shows form used to submit story
 */
function csn_show_form(){
    $uri = explode('/', $_SERVER['REQUEST_URI']);
    $new_uri = 'http://'.$_SERVER['SERVER_NAME'].'/'.$uri[1].'/wp-content/plugins/community-submitted-news/community-submitted-news.php';
    $image_uri = 'http://'.$_SERVER['SERVER_NAME'].'/'.$uri[1].'/';
    ?>
    <style type='text/css'>
        label{float:left}
    </style>
        <div id="csn_alert"></div>
        <div id="csn_user_submission">
    <form name='csn_user_news' id='csn_form' action='<?php echo $new_uri ?>' method="post">
        <?php
        if(function_exists('wp_nonce_field')){
            wp_nonce_field('community_submitted_news_add_news');
        }
        ?>
            <p>Your Name: <input type='text' name='csn_user_name' id='csn_user_name'/></p>
            <p>Your Email: <input type='text' name='csn_user_email' id='csn_user_email'/></p>
            <p>Story Title: <input length="30" type='text' name='csn_user_title' id='csn_user_title'/></p>
            <span>News Story:  </span><textarea name='csn_user_story' rows='10' cols='45' id='csn_user_story'></textarea>
            <p><img id="captcha" src="<?=get_option('siteurl')?>/wp-content/plugins/community-submitted-news/securimage/securimage_show.php" alt="CAPTCHA Image" /></p>
            <p><a href="#" onclick="document.getElementById('captcha').src = '<?=get_option('siteurl')?>/wp-content/plugins/community-submitted-news/securimage/securimage_show.php?' + Math.random(); return false">Reload Image</a></p>
            <p>Captcha text: <input type="text" name="csn_captcha_code" id='csn_captcha_code' size="10" maxlength="6" /></p>
            <p><button type='submit'>Submit Story</button></p>
    </form>
        </div>
    <?php
}

/**
 * Publishes user submitted story to wordpress posts table
 *
 * @global object $wpdb
 * @global object $wp_rewrite
 * @param string $id
 */
function csn_publish_story($id){
    require (ABSPATH . WPINC . '/pluggable.php');
    global $wpdb, $wp_rewrite;
    $category = get_category_by_slug(get_option('csn_category_slug'));
    $tablename = $wpdb->prefix.'posts';
    $story = csn_get_story($id);
    $wp_rewrite->feeds = array('no');
    $params = array(
        "post_author" => $current_user->ID,
        "post_content" => $story[0]->story,
        "post_title" => $story[0]->title,
        "post_category" => array($category->term_id),
        "post_status" => 'publish',
        "post_type" => 'post'
    );

    if(wp_insert_post($params)){
        $wpdb->update($wpdb->prefix.'csn_submission', array('approve' => 1), array('id' => $id));
        //csn_email_submitter($id);
        if($_GET['action'] != 'csn_view'){
            echo 'The story has been published. You may now edit it from Edit Posts page';
        }
    }
    else{
        echo 'Something has not gone well. Please try again';
    }
}

/**
 * Deletes story that was not yet published
 *
 * @param string $id
 */
function csn_delete_story($id){
    global $wpdb;
    $wpdb->query($wpdb->prepare('DELETE FROM `'.$wpdb->prefix.'csn_submission` WHERE id = %d', $id));
    
    echo 'The submission was deleted';
}

/**
 * Emails person who submitted story to tell their submission has been published
 *
 * @param string $id
 * @return boolean
 */
function csn_email_submitter($id, $action='approve'){
    $story = csn_get_story($id);
    $blogname = get_option('blogname');
    $message = 'Hello '.$name;
    if($action == 'approve'){
        $message = <<<MSG
The story you submitted to $blogname has been approved and published.

$blogname
MSG;
    }
    else{
        $message = <<<MSG
The story you submitted to $blogname has been denied. Sorry.

$blogname
MSG;
    }

    $to = $story[0]->email;
    $subject = 'News story published at ';
    
    if(wp_mail($to, $subject, $message)){
        echo 'stuff';
        return true;
    }
    else{
        echo 'stiff';
        return false;
    }
}
?>