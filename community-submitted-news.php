<?php
//session_start();
/*
    Plugin Name: Community Submitted News
    Plugin URI: http://studioslice.com
    Description: Allows you to let your readers submit stories to post on your site.
    Version: 1.0.6
    Author: Gregary Dean
    Author URI: http://studioslice.com


    Copyright 2009  Gregary M. Dean  (email : greg@studioslice.com)

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


add_action('admin_menu', 'csn_plugin_menu');
add_action('wp_head', 'csn_head');
add_action('admin_head', 'csn_head');
register_activation_hook(__FILE__,'csn_install_news');

switch($_SERVER['REQUEST_METHOD']){
    case isset($_POST['csn_save']):
        csn_update_news($_POST);
        break;
    case isset($_POST['csn_publish']):
        csn_publish_story($_GET['id']);
        break;
    case isset($_POST['csn_captcha_code']):
        csn_add_news($_POST);
        break;
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
        echo '<p>This story has been published. To edit further go to the Edit Posts page or return to the <a href="'.$_SERVER["HTTP_REFERER"].'">View Stories</a> page to publish more stories</p>';
    }
    else if(isset($_POST['save'])){
        echo '<p>This story has been updated</p>';
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
            case 'csn_publish':
                csn_publish_story($_GET['id']);
                break;
            case 'csn_remove':
               csn_delete_story($_GET['id']);
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

        echo '<table class="widefat post fixed" id = "csn">';
        echo '<thead>';
        echo '<tr><th class="name">Name</th><th class="title">Title</th><th class="story">Story</th><th class="action">View Story</th><th class="action">Publish Story</th><th class="action">Remove Story</th></tr><tbody>';
        foreach($news as $story){
            echo '<tr>';
            echo '<td>'.$story->name.'</td>';
            echo '<td>'.$story->title.'</td>';
            echo '<td>'.$story->story.'</td>';
            echo '<td><a href="http://'.$page.'&action=csn_view&id='.$story->id.'">View</a></td>';
            echo '<td><a href="http://'.$page.'&action=csn_publish&id='.$story->id.'">Publish</a></td>';
            echo '<td><a href="http://'.$page.'&action=csn_remove&id='.$story->id.'">Remove</a></td>';
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
    require (ABSPATH . WPINC . '/pluggable.php');
    include_once 'wp-content/plugins/community-submitted-news/securimage/securimage.php';
    $securimage = new Securimage();
    if( !$securimage->check($_POST['csn_captcha_code']) ) {
        die('The code you entered was incorrect.  Go back and try again.');
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
        check_admin_referer('community_sumbitted_news_add_news');
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
              'cat_name' => 'Commuity Submitted News',
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
    $new_uri = 'http://'.$_SERVER['SERVER_NAME'].'/'.$uri[1].'/?form';
    $image_uri = 'http://'.$_SERVER['SERVER_NAME'].'/'.$uri[1].'/';
    ?>
    <style type='text/css'>
        label{float:left}
    </style>
    
    <form name='csn_user_news' id='csn_form' action='<?php echo $new_uri ?>' method="post">
        <?php
        if($_SERVER['argv'][0] == 'form'){
            echo '<p>Thank you for submitting your story</p>';
        }
        ?>
        <?php
        if(function_exists('wp_nonce_field')){
            wp_nonce_field('community_sumbitted_news_add_news');
        }
        ?>
        <ul>
            <li>
                <label for='csn_user_name'>Your Name:</label>
                <input type='text' name='csn_user_name' id='csn_user_name'/>
            </li>
            <li>
                <label for='csn_user_email'>Your Email:</label>
                <input type='text' name='csn_user_email' id='csn_user_email'/>
            </li>
            <li>
                <label for='csn_user_title'>Story Title:</label>
                <input type='text' name='csn_user_title' id='csn_user_title'/>
            </li>
            <li>
                <label for='csn_user_story'>News Story:</label>
                <textarea name='csn_user_story' rows='10' cols='30' id='csn_user_story'></textarea>
            </li>
            <li>
                <img id="captcha" src="<?=get_option('siteurl')?>/wp-content/plugins/community-submitted-news/securimage/securimage_show.php" alt="CAPTCHA Image" />
                <a href="#" onclick="document.getElementById('captcha').src = '<?=get_option('siteurl')?>/wp-content/plugins/community-submitted-news/securimage/securimage_show.php?' + Math.random(); return false">Reload Image</a>

            </li>
            <li>
                <label for='csn_captcha_code'>Captcha text:  </label>
                <input type="text" name="csn_captcha_code" id='csn_captcha_code' size="10" maxlength="6" />
            </li>
            <li>
                <button type='submit'>Submit Story</button>
            </li>
        </ul>
    </form>
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
        csn_email_submitter($id);
        if($_GET['action'] != 'csn_view'){
            echo '<p>The story has been published. You may now edit it from Edit Posts page or return to the <a href="'.$_SERVER["HTTP_REFERER"].'">previous page</a></p>';
        }
    }
    else{
        echo '<p>Something has not gone well. Please return to the <a href="'.$_SERVER["HTTP_REFERER"].'">previous page</a> and try again.</p>';
    }
}

/**
 * Deletes story that was not yet published
 *
 * @param string $id
 */
function csn_delete_story($id){
    global $wpdb;
    
    csn_email_submitter($id, 'delete');
    $wpdb->query($wpdb->prepare('DELETE FROM `'.$wpdb->prefix.'csn_submission` WHERE id = %d', $id));
    
    echo 'The story has been deleted';
}

/**
 * Emails person who submitted story to tell their submission has been published
 *
 * @param string $id
 * @return boolean
 */
function csn_email_submitter($id, $action='approve'){
    $story = csn_get_story($id);
    $name = $story[0]->name;
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
    $from = $admin_email;

    if(mail($to, $subject, $message)){
        return true;
    }
    else{
        return false;
    }
}
?>