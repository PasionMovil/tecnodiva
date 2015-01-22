<?php
/*
Plugin Name: Popular Posts Bars
Plugin URI: http://codecanyon.net/item/popular-posts-bar-widget/115225
Description: Add your most commented (or viewed) posts in your sidebar with colored bars, Engadget-like.
Version: 1.5.2
Author: Rafael Soto
Author URI: http://codecanyon.net/user/faelsoto/portfolio
License: GPL2
*/

global $wpdb, $mostviewedbars_table;
$mostviewedbars_table = $wpdb->prefix.'plugin_most_viewed_bars';
register_activation_hook( __FILE__, array('MostViewedBars', 'activate'));

class MostViewedBars extends WP_Widget {
  static $defaults = array(
    'title' => 'Most Viewed Posts In The Last 7 Days',
    'lapse' => '-7 days',
    'classes' => 'red,orange,yellow,green,blue',
    'number_posts' => '5',
    'thumbnail' => false,
    'thumbnail_size' => '15x15',
    'css_alternate' => true,
    'cache_data' => true,
    'cache_time' => '+3 hours',
    'codecanyon_link' => true,
    'codecanyon_username' => '',
  );
  
  function MostViewedBars() {
    parent::WP_Widget('most_viewed_bars', 'Most Viewed Bars', array('description' => 'Show your trending posts, calculated by views count.'));  
  }
  
  function activate(){
    global $wpdb, $mostviewedbars_table;
    $wpdb->query("CREATE TABLE IF NOT EXISTS " . $mostviewedbars_table . " (
      id BIGINT(50) NOT NULL AUTO_INCREMENT PRIMARY KEY,
      post_id INT NOT NULL,
      date DATETIME
    );");
  }
  
  function register_view(){
    global $wpdb, $wp_query, $post, $mostviewedbars_table, $mostviewedbars_count_once;

    $options = wp_parse_args(get_option('popularpostsbars'), MostViewedBars::$defaults);
    $post_id = !empty($post->ID) ? $post->ID : $wp_query->post->ID;

    if(is_single() && !is_page() && empty($mostviewedbars_count_once)){
      $current_time = date("c", current_time('timestamp', 0));
      $mostviewedbars_count_once = 1;
      
      $cache_last = get_transient('most_viewed_bars_cache_last');
      if(!empty($options['cache_data'])){
        $to_insert = get_transient('most_viewed_posts_to_insert');
        $to_insert[] = array('post_id' => $post_id, 'date' => $current_time);
        if(empty($cache_last) || current_time('timestamp') > $cache_last){
          $cache_last = strtotime($options['cache_time'], current_time('timestamp'));
          set_transient('most_viewed_bars_cache_last', $cache_last);
          foreach($to_insert as $ins){
            $wpdb->insert($mostviewedbars_table, $ins);
          }
          $to_insert = array();
        }
        set_transient('most_viewed_posts_to_insert', $to_insert);
      } else {
        $wpdb->insert($mostviewedbars_table, array('post_id' => $post_id, 'date' => $current_time));
      }
    }
  }
  
  function widget($args, $instance){
    global $wpdb, $mostviewedbars_table;

    $options = wp_parse_args(get_option('popularpostsbars'), MostViewedBars::$defaults);

    $time_start = date('c', strtotime($instance['lapse'], current_time('timestamp', 0)));
    $classes = preg_replace('/\s\s+/', '', preg_replace('~[,.]~', ' ', $instance['classes']));
    
    echo $args['before_widget'];
    if(!empty($instance['title'])) echo $args['before_title'] . $instance['title'] . $args['after_title'];
    
    $cache_last = get_transient('most_viewed_bars_cache_last');
    if(!empty($options['cache_data'])){
      $most_viewed_posts = get_transient('most_viewed_posts');
      if(empty($most_viewed_posts) || empty($cache_last) || current_time('timestamp') > $cache_last){
        $cache_last = strtotime($options['cache_time'], current_time('timestamp'));
        set_transient('most_viewed_bars_cache_last', $cache_last);
        $most_viewed_posts = $wpdb->get_results("SELECT " . $wpdb->prefix . "posts.id, " . $wpdb->prefix . "posts.post_title, " . $wpdb->prefix . "posts.post_status, " . $wpdb->prefix . "posts.guid, COUNT(" . $mostviewedbars_table . ".post_id) AS view_count FROM " . $wpdb->prefix . "posts JOIN " . $mostviewedbars_table . " ON " . $mostviewedbars_table . ".post_id = " . $wpdb->prefix . "posts.id WHERE " . $wpdb->prefix . "posts.post_status = 'publish' AND " . $mostviewedbars_table . ".date > '" . $time_start . "' GROUP BY " . $mostviewedbars_table . ".post_id ORDER BY view_count DESC, post_date DESC LIMIT " . $instance['number_posts']);
        set_transient('most_viewed_posts', $most_viewed_posts);
      }
    } else {
      $most_viewed_posts = $wpdb->get_results("SELECT " . $wpdb->prefix . "posts.id, " . $wpdb->prefix . "posts.post_title, " . $wpdb->prefix . "posts.post_status, " . $wpdb->prefix . "posts.guid, COUNT(" . $mostviewedbars_table . ".post_id) AS view_count FROM " . $wpdb->prefix . "posts JOIN " . $mostviewedbars_table . " ON " . $mostviewedbars_table . ".post_id = " . $wpdb->prefix . "posts.id WHERE " . $wpdb->prefix . "posts.post_status = 'publish' AND " . $mostviewedbars_table . ".date > '" . $time_start . "' GROUP BY " . $mostviewedbars_table . ".post_id ORDER BY view_count DESC, post_date DESC LIMIT " . $instance['number_posts']);
    }
    
    $classes = explode(" ", $classes);
    if(!empty($most_viewed_posts)){ ?>
    <ul class="popular_posts_bars most_viewed_bars">
    <?php
      foreach($most_viewed_posts as $k => $post){
        $class = isset($classes[$k]) && !empty($classes[$k]) ? $classes[$k] : 'no-color';
        $thumbnail = '';
        if($instance['thumbnail'] && function_exists('current_theme_supports') && current_theme_supports('post-thumbnails') && has_post_thumbnail($post->id)){
          $thumbnail = get_the_post_thumbnail($post->id, explode('x', $instance['thumbnail_size']));
        }
        ?>
        <li class="popular_posts_bars_li <?php echo $class; ?>"><a href="<?php echo get_permalink($post->id); ?>" class="popular_posts_bars_link"><?php echo $thumbnail  . get_the_title($post->id); ?></a></li>
        <?php
      }
      ?>
      </ul>
    <?php if(!empty($instance['codecanyon_link'])): ?>
    <p><small><a href="http://codecanyon.net/item/popular-posts-bar-widget/115225<?php if(!empty($instance['codecanyon_username'])): echo '?ref=' . esc_attr($instance['codecanyon_username']); endif; ?>" target="_blank">Purchase this widget on CodeCanyon</a></small>
    <?php endif; ?>
      <?php
    }
    echo $args['after_widget'];
  }
  
  function update($new_instance, $old_instance) {
    $options['css_alternate'] = $new_instance['css_alternate'];
    $options['cache_data'] = !empty($new_instance['cache_data']) ? true : false;
    $options['cache_time'] = $new_instance['cache_time'];
    if(strtotime($options['cache_time']) < 1 || current_time('timestamp') > strtotime($options['cache_time'], current_time('timestamp'))){
      $options['cache_time'] = '+3 hours';
    }
    // Clear cache
    set_transient('most_viewed_bars_cache_last', 0);
    
    if (!get_option('popularpostsbars')){
      add_option('popularpostsbars', $options);
    } else {
      update_option('popularpostsbars', $options);
    }
    
    $instance = $old_instance;
    $instance['title'] = $new_instance['title'];
    $instance['lapse'] = $new_instance['lapse'];
    if(strtotime($instance['lapse']) < 1 || current_time('timestamp') < strtotime($instance['lapse'], current_time('timestamp'))){
      $instance['lapse'] = '-7 days';
    }
    $instance['classes'] = preg_replace('#[^0-9a-z,_-]#', '', $new_instance['classes']);
    $instance['number_posts'] = (int) $new_instance['number_posts'];
    $instance['thumbnail'] = (int) $new_instance['thumbnail'];
    $instance['thumbnail_size'] = preg_replace('/\s\s+/', '', preg_replace('~[^0-9]~', ' ', $new_instance['thumbnail_size']));
    if(preg_match('~(([0-9]+)[^0-9]+([0-9]+))~', $instance['thumbnail_size'], $size)){
      $instance['thumbnail_size'] = $size[2] . 'x' . $size[3];
    } else {
      $instance['thumbnail_size'] = '15x15';
    }
    $instance['css_alternate'] = !empty($new_instance['css_alternate']) ? true : false;
    $instance['codecanyon_link'] = !empty($new_instance['codecanyon_link']) ? true : false;
    $instance['codecanyon_username'] = $new_instance['codecanyon_username'];
    return $instance;
  }
  
  function form($instance) {
    $instance = wp_parse_args($instance, MostViewedBars::$defaults);
    
    $options = wp_parse_args(get_option('popularpostsbars'), MostViewedBars::$defaults);
    $instance['classes'] = implode(', ', explode(',', $instance['classes']));
    $time_start = date('c', strtotime($instance['lapse'], current_time('timestamp', 0))); ?>
<p>
<label for="<?php echo $this->get_field_id('title'); ?>">Title
<input name="<?php echo $this->get_field_name('title'); ?>" id="<?php echo $this->get_field_id('title'); ?>" type="text" class="widefat" value="<?php echo esc_attr($instance['title']); ?>" /></label>
</p>
<p>
<label for="<?php echo $this->get_field_id('lapse'); ?>">Lapse
<input name="<?php echo $this->get_field_name('lapse'); ?>" id="<?php echo $this->get_field_id('lapse'); ?>" type="text" class="widefat" value="<?php echo esc_attr($instance['lapse']); ?>" /></label><br />
<small>Must be <a href="http://php.net/manual/en/function.strtotime.php">strtotime</a> compatible, else, defaults to <b>-7 days</b> <br /> Currently: <?php echo $time_start; ?></small>
</p>
<p>
<label for="<?php echo $this->get_field_id('classes'); ?>">HTML Classes
<input name="<?php echo $this->get_field_name('classes'); ?>" id="<?php echo $this->get_field_id('classes'); ?>" type="text" class="widefat" value="<?php echo esc_attr($instance['classes']); ?>" /></label><br />
<small>Comma separated, in order from most popular to less popular.</small>
</p>
<p>
<label for="<?php echo $this->get_field_id('number_posts'); ?>">Number of posts
<input name="<?php echo $this->get_field_name('number_posts'); ?>" id="<?php echo $this->get_field_id('number_posts'); ?>" type="text" size="3" value="<?php echo esc_attr($instance['number_posts']); ?>" /></label>
</p>
<p>
<input name="<?php echo $this->get_field_name('thumbnail'); ?>" id="<?php echo $this->get_field_id('thumbnail'); ?>"type="checkbox" value="1"<?php if(!empty($instance['thumbnail'])): ?> checked="checked"<?php endif; ?>/>
<label for="<?php echo $this->get_field_id('thumbnail'); ?>">Add thumbnail</label>
</p>
<p>
<label for="<?php echo $this->get_field_id('thumbnail_size'); ?>">Thumbnail size
<input name="<?php echo $this->get_field_name('thumbnail_size'); ?>" id="<?php echo $this->get_field_id('thumbnail_size'); ?>" type="text" size="8" value="<?php echo esc_attr($instance['thumbnail_size']); ?>" /></label><br />
<small>Your theme must support thumbnails and WP version must be 2.9.0 or above.</small>
</p>
<p>
<input name="<?php echo $this->get_field_name('css_alternate'); ?>" id="<?php echo $this->get_field_id('css_alternate'); ?>" type="checkbox" value="1"<?php if(!empty($options['css_alternate'])): ?> checked="checked"<?php endif; ?>/>
<label for="<?php echo $this->get_field_id('css_alternate'); ?>">Having trouble with CSS?</label><br />
<small>Sometimes the theme messes up this plugin's CSS and looks bad. Mark this checkbox if that's your case.</small>
</p>
<p>
<input name="<?php echo $this->get_field_name('cache_data'); ?>" id="<?php echo $this->get_field_id('cache_data'); ?>" type="checkbox" value="1"<?php if(!empty($options['cache_data'])): ?> checked="checked"<?php endif; ?>/>
<label for="<?php echo $this->get_field_id('cache_data'); ?>">Cache data?</label><br />
<small>Data has to be cached in order to improve performance, but your bars will remain unupdated for the cached time.</small>
</p>
<p>
<label for="<?php echo $this->get_field_id('cache_time'); ?>">Cache time
<input name="<?php echo $this->get_field_name('cache_time'); ?>" id="<?php echo $this->get_field_id('cache_time'); ?>" type="text" size="9" value="<?php echo esc_attr($options['cache_time']); ?>" /></label>
</p>
<hr style="border:0; border-top: 1px solid #dadada;" />
<strong>Referral Settings</strong>
<p>
<small>You can <strong>earn money</strong> on Code Canyon <a href="http://codecanyon.net/wiki/referral/referral-program/" target="_blank">by referring users</a>. Just mark the next checkbox and add your username!</small>
</p>
<p>
<input type="checkbox" id="<?php echo $this->get_field_id('codecanyon_link'); ?>" name="<?php echo $this->get_field_name('codecanyon_link'); ?>" value="1"<?php if(!empty($instance['codecanyon_link'])): ?> checked="checked"<?php endif; ?> />
<label for="<?php echo $this->get_field_id('codecanyon_link'); ?>">Include link to item at CodeCanyon</label>
</p>
<p>
<label for="<?php echo $this->get_field_id('codecanyon_username'); ?>">CodeCanyon Username</label> <br />
<input type="text" id="<?php echo $this->get_field_id('codecanyon_username'); ?>" name="<?php echo $this->get_field_name('codecanyon_username'); ?>" value="<?php echo esc_attr($instance['codecanyon_username']); ?>" />
</p><?php
  }
}

class PopularPostsBars extends WP_Widget {
  static $defaults = array(
    'title' => 'Popular Posts By Comments In The Last 7 Days',
    'lapse' => '-7 days',
    'classes' => 'red,orange,yellow,green,blue',
    'number_posts' => '5',
    'thumbnail' => false,
    'thumbnail_size' => '15x15',
    'css_alternate' => true,
    'cache_data' => true,
    'cache_time' => '+3 hours',
    'codecanyon_link' => true,
    'codecanyon_username' => '',
  );

  function PopularPostsBars() {
    parent::WP_Widget('popular_posts_bars', 'Popular Posts Bars', array('description' => 'Show the hottest posts by comments.'));  
  }
  
  function head(){
    $options = wp_parse_args(get_option('popularpostsbars'), PopularPostsBars::$defaults);
    $css = !empty($options['css_alternate']) ? 'style_alternate.css' : 'style.css';
    $dir = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)); 
    echo '<link type="text/css" rel="stylesheet" href="' . $dir . $css . '" />' . "\n";
  }
  
  function widget($args, $instance){
    global $wpdb;

    $options = wp_parse_args(get_option('popularpostsbars'), PopularPostsBars::$defaults);

    $time_start = date('c', strtotime($instance['lapse'], current_time('timestamp', 0)));
    $classes = preg_replace('/\s\s+/', '', preg_replace('~[,.]~', ' ', $instance['classes']));
    
    echo $args['before_widget'];
    if(!empty($instance['title'])) echo $args['before_title'] . $instance['title'] . $args['after_title'];
    
    $cache_last = get_transient('popular_posts_bars_cache_last');
    if(!empty($options['cache_data'])){
      $popular_posts_bars = get_transient('popular_posts_bars');
      if(empty($popular_posts_bars) || empty($cache_last) || current_time('timestamp') > $cache_last){
        $cache_last = strtotime($options['cache_time'], current_time('timestamp'));
        set_transient('popular_posts_bars_cache_last', $cache_last);
        $popular_posts_bars =  $wpdb->get_results("SELECT id, post_title, post_status, guid, COUNT(comment_post_ID) AS post_comment_count FROM " . $wpdb->prefix . "posts JOIN " . $wpdb->prefix . "comments ON id = comment_post_ID WHERE post_status = 'publish' AND comment_approved = 1 AND comment_date > '" . $time_start . "' AND comment_type = '' GROUP BY id ORDER BY post_comment_count DESC, post_date DESC LIMIT " . $instance['number_posts']);
        set_transient('popular_posts_bars', $popular_posts_bars);
      }
    } else {
      $popular_posts_bars = $wpdb->get_results("SELECT id, post_title, post_status, guid, COUNT(comment_post_ID) AS post_comment_count FROM " . $wpdb->prefix . "posts JOIN " . $wpdb->prefix . "comments ON id = comment_post_ID WHERE post_status = 'publish' AND comment_approved = 1 AND comment_date > '" . $time_start . "' AND comment_type = '' GROUP BY id ORDER BY post_comment_count DESC, post_date DESC LIMIT " . $instance['number_posts']);
    }
    
    $classes = explode(" ", $classes);
    if(!empty($popular_posts_bars)){ ?>
    <ul class="popular_posts_bars">
    <?php
      foreach($popular_posts_bars as $k => $post){
        $class = isset($classes[$k]) && !empty($classes[$k]) ? $classes[$k] : 'no-color';
        $thumbnail = '';
        if($instance['thumbnail'] && function_exists('current_theme_supports') && current_theme_supports('post-thumbnails') && has_post_thumbnail($post->id)){
          $thumbnail = get_the_post_thumbnail($post->id, explode('x', $instance['thumbnail_size']));
        }
        ?>
        <li class="popular_posts_bars_li <?php echo $class; ?>"><a href="<?php echo get_permalink($post->id); ?>" class="popular_posts_bars_link"><?php echo $thumbnail  . get_the_title($post->id); ?></a><span class="popular_posts_bars_comment_count_hold"><a href="<?php echo get_permalink($post->id); ?>#comments" class="popular_posts_bars_comment_count"><?php echo number_format($post->post_comment_count); ?></a><span class="popular_posts_bars_comment_count_triangle"></span></span></li>
        <?php
      }
      ?>
      </ul>
    <?php if(!empty($instance['codecanyon_link'])): ?>
    <small><a href="http://codecanyon.net/item/popular-posts-bar-widget/115225<?php if(!empty($instance['codecanyon_username'])): echo '?ref=' . esc_attr($instance['codecanyon_username']); endif; ?>">Popular Posts Bars Widget</a></small>
    <?php endif; ?>
      <?php
    }
    echo $args['after_widget'];
  }
  
  function update($new_instance, $old_instance) {
    $options['css_alternate'] = $new_instance['css_alternate'];
    $options['cache_data'] = !empty($new_instance['cache_data']) ? true : false;
    $options['cache_time'] = $new_instance['cache_time'];
    if(strtotime($options['cache_time']) < 1 || current_time('timestamp') > strtotime($options['cache_time'], current_time('timestamp'))){
      $options['cache_time'] = '+3 hours';
    }
    // Clear cache
    set_transient('popular_posts_bars_cache_last', 0);
    
    if (!get_option('popularpostsbars')){
      add_option('popularpostsbars', $options);
    } else {
      update_option('popularpostsbars', $options);
    }
    
    $instance = $old_instance;
    $instance['title'] = $new_instance['title'];
    $instance['lapse'] = $new_instance['lapse'];
    if(strtotime($instance['lapse']) < 1 || current_time('timestamp') < strtotime($instance['lapse'], current_time('timestamp'))){
      $instance['lapse'] = '-7 days';
    }
    $instance['classes'] = preg_replace('#[^0-9a-z,_-]#', '', $new_instance['classes']);
    $instance['number_posts'] = (int) $new_instance['number_posts'];
    $instance['thumbnail'] = (int) $new_instance['thumbnail'];
    $instance['thumbnail_size'] = preg_replace('/\s\s+/', '', preg_replace('~[^0-9]~', ' ', $new_instance['thumbnail_size']));
    if(preg_match('~(([0-9]+)[^0-9]+([0-9]+))~', $instance['thumbnail_size'], $size)){
      $instance['thumbnail_size'] = $size[2] . 'x' . $size[3];
    } else {
      $instance['thumbnail_size'] = '15x15';
    }
    $instance['css_alternate'] = !empty($new_instance['css_alternate']) ? true : false;
    $instance['codecanyon_link'] = !empty($new_instance['codecanyon_link']) ? true : false;
    $instance['codecanyon_username'] = $new_instance['codecanyon_username'];
    return $instance;
  }
  
  function form($instance) {
    $instance = wp_parse_args($instance, PopularPostsBars::$defaults);
    
    $options = wp_parse_args(get_option('popularpostsbars'), PopularPostsBars::$defaults);
    $instance['classes'] = implode(', ', explode(',', $instance['classes']));
    $time_start = date('c', strtotime($instance['lapse'], current_time('timestamp', 0))); ?>
<p>
<label for="<?php echo $this->get_field_id('title'); ?>">Title
<input name="<?php echo $this->get_field_name('title'); ?>" id="<?php echo $this->get_field_id('title'); ?>" type="text" class="widefat" value="<?php echo esc_attr($instance['title']); ?>" /></label>
</p>
<p>
<label for="<?php echo $this->get_field_id('lapse'); ?>">Lapse
<input name="<?php echo $this->get_field_name('lapse'); ?>" id="<?php echo $this->get_field_id('lapse'); ?>" type="text" class="widefat" value="<?php echo esc_attr($instance['lapse']); ?>" /></label><br />
<small>Must be <a href="http://php.net/manual/en/function.strtotime.php">strtotime</a> compatible, else, defaults to <b>-7 days</b> <br /> Currently: <?php echo $time_start; ?></small>
</p>
<p>
<label for="<?php echo $this->get_field_id('classes'); ?>">HTML Classes
<input name="<?php echo $this->get_field_name('classes'); ?>" id="<?php echo $this->get_field_id('classes'); ?>" type="text" class="widefat" value="<?php echo esc_attr($instance['classes']); ?>" /></label><br />
<small>Comma separated, in order from most popular to less popular.</small>
</p>
<p>
<label for="<?php echo $this->get_field_id('number_posts'); ?>">Number of posts
<input name="<?php echo $this->get_field_name('number_posts'); ?>" id="<?php echo $this->get_field_id('number_posts'); ?>" type="text" size="3" value="<?php echo esc_attr($instance['number_posts']); ?>" /></label>
</p>
<p>
<input name="<?php echo $this->get_field_name('thumbnail'); ?>" id="<?php echo $this->get_field_id('thumbnail'); ?>"type="checkbox" value="1"<?php if(!empty($instance['thumbnail'])): ?> checked="checked"<?php endif; ?>/>
<label for="<?php echo $this->get_field_id('thumbnail'); ?>">Add thumbnail</label>
</p>
<p>
<label for="<?php echo $this->get_field_id('thumbnail_size'); ?>">Thumbnail size
<input name="<?php echo $this->get_field_name('thumbnail_size'); ?>" id="<?php echo $this->get_field_id('thumbnail_size'); ?>" type="text" size="8" value="<?php echo esc_attr($instance['thumbnail_size']); ?>" /></label><br />
<small>Your theme must support thumbnails and WP version must be 2.9.0 or above.</small>
</p>
<p>
<input name="<?php echo $this->get_field_name('css_alternate'); ?>" id="<?php echo $this->get_field_id('css_alternate'); ?>" type="checkbox" value="1"<?php if(!empty($options['css_alternate'])): ?> checked="checked"<?php endif; ?>/>
<label for="<?php echo $this->get_field_id('css_alternate'); ?>">Having trouble with CSS?</label><br />
<small>Sometimes the theme messes up this plugin's CSS and looks bad. Mark this checkbox if that's your case.</small>
</p>
<p>
<input name="<?php echo $this->get_field_name('cache_data'); ?>" id="<?php echo $this->get_field_id('cache_data'); ?>" type="checkbox" value="1"<?php if(!empty($options['cache_data'])): ?> checked="checked"<?php endif; ?>/>
<label for="<?php echo $this->get_field_id('cache_data'); ?>">Cache data?</label><br />
<small>Data has to be cached in order to improve performance, but your bars will remain unupdated for the cached time.</small>
</p>
<p>
<label for="<?php echo $this->get_field_id('cache_time'); ?>">Cache time
<input name="<?php echo $this->get_field_name('cache_time'); ?>" id="<?php echo $this->get_field_id('cache_time'); ?>" type="text" size="9" value="<?php echo esc_attr($options['cache_time']); ?>" /></label>
</p>
<hr style="border:0; border-top: 1px solid #dadada;" />
<strong>Referral Settings</strong>
<p>
<small>You can <strong>earn money</strong> on Code Canyon <a href="http://codecanyon.net/wiki/referral/referral-program/" target="_blank">by referring users</a>. Just mark the next checkbox and add your username!</small>
</p>
<p>
<input type="checkbox" id="<?php echo $this->get_field_id('codecanyon_link'); ?>" name="<?php echo $this->get_field_name('codecanyon_link'); ?>" value="1"<?php if(!empty($instance['codecanyon_link'])): ?> checked="checked"<?php endif; ?> />
<label for="<?php echo $this->get_field_id('codecanyon_link'); ?>">Include link to item at CodeCanyon</label>
</p>
<p>
<label for="<?php echo $this->get_field_id('codecanyon_username'); ?>">CodeCanyon Username</label> <br />
<input type="text" id="<?php echo $this->get_field_id('codecanyon_username'); ?>" name="<?php echo $this->get_field_name('codecanyon_username'); ?>" value="<?php echo esc_attr($instance['codecanyon_username']); ?>" />
</p><?php
  }
}

add_action('widgets_init', 'popular_posts_bars_register_widgets');

function popular_posts_bars_register_widgets(){
  add_action('wp_head', array('PopularPostsBars', 'head'));
  add_action('wp_head', array('MostViewedBars', 'register_view'));
  register_widget('PopularPostsBars');
  register_widget('MostViewedBars');
}

function popular_posts_bars_widget($instance){
  if(!is_array($instance)) $instance = wp_parse_args($instance);
  return PopularPostsBars::widget(NULL, $instance);
}

function most_viewed_bars_widget($instance){
  if(!is_array($instance)) $instance = wp_parse_args($instance);
  return MostViewedBars::widget(NULL, $instance);
}