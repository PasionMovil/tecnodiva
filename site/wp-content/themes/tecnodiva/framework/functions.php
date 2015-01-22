<?php
//inicia agregado para quitar hotwords
function adios_hotwords() {
    add_meta_box( 'hotwordsno', 'Hotwords', 'sin_hotwords', 'post' );
} 
add_action( 'add_meta_boxes', 'adios_hotwords' );

function sin_hotwords( $post ) { 
    wp_nonce_field( basename( __FILE__ ), 'example_nonce' );
    $example_stored_meta = get_post_meta( $post->ID );
?>
<p>
    <span>Marca la casilla para desactivar hotwords en este post</span>
    <div>
        <label for="quita-hotw">
            <input type="checkbox" name="quita-hotw" id="quita-hotw" value="yes" <?php checked( $example_stored_meta['quita-hotw'][0], 'yes' ); ?> />
            Eliminar Hotwords de este post
        </label>
    </div>
</p>
<?php
    echo 'Dudas <a href="http://twitter.com/davidmirandaz" target="_blank">@DavidMirandaZ</a>'; 
}
function sin_hotwords_save( $post_id ) {
    $is_autosave = wp_is_post_autosave( $post_id );
    $is_revision = wp_is_post_revision( $post_id );
    $is_valid_nonce = ( isset( $_POST[ 'quita-hotw' ] ) && wp_verify_nonce( $_POST[ 'quita-hotw' ], basename( __FILE__ ) ) ) ? 'true' : 'false';
    if ( $is_autosave || $is_revision || !$is_valid_nonce ) {
        return;
    }
if( isset( $_POST[ 'quita-hotw' ] ) ) {
    update_post_meta( $post_id, 'quita-hotw', 'yes' );
	} else {
	    update_post_meta( $post_id, 'quita-hotw', '' );
	} 
}
add_action( 'save_post', 'sin_hotwords_save' );
//termina agregado para quitar hotwords
?>



<?php
function get_related_posts($post_id, $tags = array()) {
	$query = new WP_Query();
	
	$post_types = get_post_types();
	unset($post_types['page'], $post_types['attachment'], $post_types['revision'], $post_types['nav_menu_item']);
	
	if($tags) {
		foreach($tags as $tag) {
			$tagsA[] = $tag->term_id;
		}
	}
	
	$args = wp_parse_args($args, array(
		'showposts' => 4,
		'post_type' => $post_types,
		'post__not_in' => array($post_id),
		'tag__in' => $tagsA,
		'ignore_sticky_posts' => 1,
	));
	
	$query = new WP_Query($args);
	
  	return $query;
}

function kriesi_pagination($pages = '', $range = 2)
{  
     $showitems = ($range * 2)+1;  

     global $paged;
     if(empty($paged)) $paged = 1;

     if($pages == '')
     {
         global $wp_query;
         $pages = $wp_query->max_num_pages;
         if(!$pages)
         {
             $pages = 1;
         }
     }   

     if(1 != $pages)
     {
         echo "<div class='pagination'>";
         if($paged > 2 && $paged > $range+1 && $showitems < $pages) echo "<a href='".get_pagenum_link(1)."'><span class='arrows'>&laquo;</span> First</a>";
         if($paged > 1 && $showitems < $pages) echo "<a href='".get_pagenum_link($paged - 1)."'><span class='arrows'>&lsaquo;</span> Previous</a>";

         for ($i=1; $i <= $pages; $i++)
         {
             if (1 != $pages &&( !($i >= $paged+$range+1 || $i <= $paged-$range-1) || $pages <= $showitems ))
             {
                 echo ($paged == $i)? "<span class='current'>".$i."</span>":"<a href='".get_pagenum_link($i)."' class='inactive' >".$i."</a>";
             }
         }

         if ($paged < $pages && $showitems < $pages) echo "<a href='".get_pagenum_link($paged + 1)."'>Next <span class='arrows'>&rsaquo;</span></a>";  
         if ($paged < $pages-1 &&  $paged+$range-1 < $pages && $showitems < $pages) echo "<a href='".get_pagenum_link($pages)."'>Last <span class='arrows'>&raquo;</span></a>";
         echo "</div>\n";
     }
}

function string_limit_words($string, $word_limit)
{
	$words = explode(' ', $string, ($word_limit + 1));
	
	if(count($words) > $word_limit) {
		array_pop($words);
	}
	
	return implode(' ', $words);
}