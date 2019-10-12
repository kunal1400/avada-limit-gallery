<?php
/*
Plugin Name: Avada Limit Gallery
Description: Simple plugin to extend the functionality of avada gallery
Author: Kunal Malviya
Author URI: https://www.facebook.com/lucky.kunalmalviya
Text Domain: avada-limit-gallery
Domain Path: /languages/
Version: 5.1.1
*/

// add_action( 'admin_enqueue_scripts', 'init_admin_script_new' );
add_action('wp_enqueue_scripts', 'init_wp_enqueue_scripts');
function init_wp_enqueue_scripts() {
    wp_enqueue_style( 'magnificPopupCss', plugin_dir_url( __FILE__ ) . 'dist/magnific-popup.css' );	
	wp_register_script('magnificPopup', plugin_dir_url( __FILE__ ).'dist/jquery.magnific-popup.min.js', array('jquery'), NULL, false );
	wp_enqueue_script('magnificPopup');
}

/**
* On Ninja Form Submission create/login user
**/
add_action( 'ninja_forms_after_submission', 'avada_limit_gallery_ninja_forms_after_submission' );
function avada_limit_gallery_ninja_forms_after_submission( $form_data ) {
    global $wp;
    $currentUrl = home_url( $wp->request );
    $email = $form_data['fields_by_key']['email']['value'];
    if($email) {        
        if ( email_exists($email) == false ) {
            echo "$email not present in db";
            $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
            wp_create_user( $email, $random_password, $email );
        }        

        $user = get_user_by( 'email',  $email );        
        // $user = get_user_by( 'id', $user->ID );
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
        do_action( 'wp_login', $user->data->user_login );        
    }
    else {
        echo "Unable to get email from ninja form";
    }
}

/**
* On page load call this function
**/
add_action("init", "avada_limit_gallery_init_functions");
function avada_limit_gallery_init_functions() {
    if( !empty($_POST['avada_limit_gallery_user_registration_email']) ) {
        $email = $_POST['avada_limit_gallery_user_registration_email'];
        $redirectUrl = $_POST['avada_limit_gallery_redirect_url'];

        // If email is not present then create user
        if ( email_exists($email) == false ) {
            $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
            wp_create_user( $email, $random_password, $email );
        }        

        $user = get_user_by( 'email',  $email );
        // $user = get_user_by( 'id', $user->ID );
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
        do_action( 'wp_login', $user->data->user_login );
        wp_redirect($redirectUrl);
        exit;        
    }    
}

/**
* Adding shortcode for this plugin
**/
add_shortcode( 'avada_limit_gallery', 'avada_limit_gallery_callback' );
function avada_limit_gallery_callback( $atts ) {
    global $wp;
    $currentUrl = home_url( $wp->request );
    
    global $post;
    $numberOfImagesToShow = 2;
	$images = miu_get_images();
    $returnHtml = '';

    // Getting the options from
    $numberOfImagesToShow = get_post_meta($post->ID, 'number_of_images_to_show', true);
    $formShortcode = get_post_meta($post->ID, 'form_shortcode', true);

    if( is_user_logged_in() ) {
        if( count($images) > 0 ) {
            $returnHtml = '[fusion_gallery layout="" picture_size="" columns="4" column_spacing="10" gallery_masonry_grid_ratio="" gallery_masonry_width_double="" hover_type="liftup" lightbox="yes" lightbox_content="" bordersize="" bordercolor="" border_radius="" hide_on_mobile="small-visibility,medium-visibility,large-visibility" class="" id=""]';        
            foreach ($images as $i => $image) {
                $attachmentId = get_attachment_id($image);
                $returnHtml .= '[fusion_gallery_image link="'.$image.'" image_id="'.$attachmentId.'|medium" linktarget="_self" /]';
            }
            $returnHtml .= '[/fusion_gallery]';
        }
    }
    else {
        if( count($images) > 0 ) {
            $returnHtml = '[fusion_gallery layout="" picture_size="" columns="4" column_spacing="10" gallery_masonry_grid_ratio="" gallery_masonry_width_double="" hover_type="liftup" lightbox="yes" lightbox_content="" bordersize="" bordercolor="" border_radius="" hide_on_mobile="small-visibility,medium-visibility,large-visibility" class="" id=""]';        
            foreach ($images as $i => $image) {
                if($i < $numberOfImagesToShow) {
                    $attachmentId = get_attachment_id($image);
                    $returnHtml .= '[fusion_gallery_image link="'.$image.'" image_id="'.$attachmentId.'|medium" linktarget="_self" /]';
                }
            }            
            $returnHtml .= '[/fusion_gallery]';
            
            $returnHtml .= '[fusion_button link="" text_transform="" title="" target="_self" link_attributes="" alignment="" modal="user_not_loged_in" hide_on_mobile="small-visibility,medium-visibility,large-visibility" class="see-more-button" id="" color="default" button_gradient_top_color="" button_gradient_bottom_color="" button_gradient_top_color_hover="" button_gradient_bottom_color_hover="" accent_color="" accent_hover_color="" type="" bevel_color="" border_width="" size="" stretch="default" shape="" icon="" icon_position="left" icon_divider="no" animation_type="" animation_direction="left" animation_speed="0.3" animation_offset=""]See All Images[/fusion_button]';

            // If form shortcode is set then do that hook otherwise do default functionality
            if( $formShortcode == "" ) {
                $returnHtml .= '[fusion_modal name="user_not_loged_in" title="Enter your email to see all images" size="large" background="#1b1b1c" border_color="#1b1b1c" show_footer="no" class="" id=""]<form action="" method="post"><div><label>Email:</label> <input name="avada_limit_gallery_user_registration_email" type="text" /> <input type="hidden" name="avada_limit_gallery_redirect_url" value="'.$currentUrl.'"></div><div><div class="fusion-button-wrapper"><div class="fusion-separator fusion-full-width-sep sep-none" style="margin-left: auto; margin-right: auto; margin-top: 20px;"> </div><p><button class="fusion-button button-flat fusion-button-default-shape fusion-button-default-size button-default button-1 fusion-button-default-span fusion-button-default-type" type="submit"><span class="fusion-button-text">Submit</span></button></p></div></div></form>[/fusion_modal]';
            }
            else {
                $returnHtml .= '[fusion_modal name="user_not_loged_in" title="Enter your email to see all images" size="large" background="#1b1b1c" border_color="#1b1b1c" show_footer="no" class="" id=""]'.$formShortcode.'[/fusion_modal]';
                $returnHtml .= "<script>
                            jQuery(document).ajaxStop(function(){
                                window.location.reload();
                            });
                            </script>";
            }
        }
    }
        
	ob_start();
    echo do_shortcode($returnHtml);
    // include __DIR__ . '/templates/shortcode.php';
    return ob_get_clean();		
}

/**
 * Calls the class on the post add/edit screens.
 */
function call_Multi_Image_Uploader()
{
    new Multi_Image_Uploader();
}

/**
 * Get images attached to some post
 *
 * @param int $post_id
 * @return array
 */
function miu_get_images($post_id=null) {
    global $post;
    if ($post_id == null) {
        $post_id = $post->ID;
    }

    $value = get_post_meta($post_id, 'miu_images', true);
    $images = unserialize($value);
    $result = array();
    if (!empty($images)) {
		foreach ($images as $image) {
            $result[] = $image;
        }
    }
    return $result;
}

if (is_admin())
{
    add_action('load-post.php', 'call_Multi_Image_Uploader');
    add_action('load-post-new.php', 'call_Multi_Image_Uploader');
}

/**
 * Multi_Image_Uploader
 */
class Multi_Image_Uploader
{

    var $post_types = array();

    /**
     * Initialize Multi_Image_Uploader
     */
    public function __construct()
    {
        $this->post_types = array('post', 'page', 'avada_portfolio');     //limit meta box to certain post types
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Adds the meta box container.
     */
    public function add_meta_box($post_type)
    {

        if (in_array($post_type, $this->post_types))
        {
            add_meta_box(
                    'multi_image_upload_meta_box'
                    , __('Upload Multiple Images', 'miu_textdomain')
                    , array($this, 'render_meta_box_content')
                    , $post_type
                    , 'advanced'
                    , 'high'
            );
        }
    }

    /**
     * Save the images when the post is saved.
     *
     * @param int $post_id The ID of the post being saved.
     */
    public function save($post_id) {
        /*
         * We need to verify this came from the our screen and with proper authorization,
         * because save_post can be triggered at other times.
         */

        // Check if our nonce is set.
        if (!isset($_POST['miu_inner_custom_box_nonce']))
            return $post_id;

        $nonce = $_POST['miu_inner_custom_box_nonce'];

        // Verify that the nonce is valid.
        if (!wp_verify_nonce($nonce, 'miu_inner_custom_box'))
            return $post_id;

        // If this is an autosave, our form has not been submitted,
        //     so we don't want to do anything.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return $post_id;

        // Check the user's permissions.
        if ('page' == $_POST['post_type'])
        {

            if (!current_user_can('edit_page', $post_id))
                return $post_id;
        } else
        {

            if (!current_user_can('edit_post', $post_id))
                return $post_id;
        }

        /* OK, its safe for us to save the data now. */

        // Validate user input.
        $posted_images = $_POST['miu_images'];
        $miu_images = array();
        if (!empty($posted_images))
        {
            foreach ($posted_images as $image_url)
            {
                if (!empty($image_url))
                    $miu_images[] = esc_url_raw($image_url);
            }
        }

        // Update the miu_images meta field.
        update_post_meta($post_id, 'miu_images', serialize($miu_images));

        if( !empty($_POST['number_of_images_to_show']) ) {
            update_post_meta($post_id, 'number_of_images_to_show', $_POST['number_of_images_to_show']);            
        }
        
        if( isset($_POST['form_shortcode']) ) {
            update_post_meta($post_id, 'form_shortcode', $_POST['form_shortcode']);            
        }

    }

    /**
     * Render Meta Box content.
     *
     * @param WP_Post $post The post object.
     */
    public function render_meta_box_content($post) {
        $numberOfImagesToShow = get_post_meta($post->ID, 'number_of_images_to_show', true);
        $formShortcode = get_post_meta($post->ID, 'form_shortcode', true);
        
        // If number of images to show is empty then set it to 2
        if(!$numberOfImagesToShow) {
            $numberOfImagesToShow = 2;
        }

        // If form shortcode is empty then set it to empty string
        if(!$formShortcode) {
            $formShortcode = '';
        }

        // Add an nonce field so we can check for it later.
        wp_nonce_field('miu_inner_custom_box', 'miu_inner_custom_box_nonce');

        // Use get_post_meta to retrieve an existing value from the database.
        $value = get_post_meta($post->ID, 'miu_images', true);

        $metabox_content = '<div>
            <table class="widefat fixed" cellspacing="0">
                <tr class="alternate">
                    <th><label for="number_of_images_to_show">Number of Images to show: </label></th>
                    <td class="column-columnname"><input style="width:100%" type="text" name="number_of_images_to_show" id="number_of_images_to_show" value="'.$numberOfImagesToShow.'" required/></td>
                </tr>
                <tr>
                    <th><label for="form_shortcode">Other shortcode: <small>(Leave empty to use plugin default functionality)</small></label></th>
                    <td class="column-columnname"><input style="width:100%" type="text" name="form_shortcode" id="form_shortcode" value="'.$formShortcode.'"/></td>
                </tr>               
            </table>
        </div><br/>';
        $metabox_content .= '<div id="miu_images"></div><input type="button" onClick="addRow()" value="Add Image" class="button" />';
        echo $metabox_content;

        $images = unserialize($value);

        $script = "<script>
            itemsCount= 0;";
        if (!empty($images))
        {
            foreach ($images as $image)
            {
                $script.="addRow('{$image}');";
            }
        }
        $script .="</script>";
        echo $script;
    }

    function enqueue_scripts($hook)
    {
        if ('post.php' != $hook && 'post-edit.php' != $hook && 'post-new.php' != $hook)
            return;
        wp_enqueue_script('miu_script', plugin_dir_url(__FILE__) . 'dist/miu_script.js', array('jquery'));
    }

}

if ( ! function_exists( 'get_attachment_id' ) ) {
    /**
     * Get the Attachment ID for a given image URL.
     *
     * @link   http://wordpress.stackexchange.com/a/7094
     *
     * @param  string $url
     *
     * @return boolean|integer
     */
    function get_attachment_id( $url ) {

        $dir = wp_upload_dir();

        // baseurl never has a trailing slash
        if ( false === strpos( $url, $dir['baseurl'] . '/' ) ) {
            // URL points to a place outside of upload directory
            return false;
        }

        $file  = basename( $url );
        $query = array(
            'post_type'  => 'attachment',
            'fields'     => 'ids',
            'meta_query' => array(
                array(
                    'key'     => '_wp_attached_file',
                    'value'   => $file,
                    'compare' => 'LIKE',
                ),
            )
        );

        // query attachments
        $ids = get_posts( $query );

        if ( ! empty( $ids ) ) {

            foreach ( $ids as $id ) {

                // first entry of returned array is the URL
                if ( $url === array_shift( wp_get_attachment_image_src( $id, 'full' ) ) )
                    return $id;
            }
        }

        $query['meta_query'][0]['key'] = '_wp_attachment_metadata';

        // query attachments again
        $ids = get_posts( $query );

        if ( empty( $ids) )
            return false;

        foreach ( $ids as $id ) {

            $meta = wp_get_attachment_metadata( $id );

            foreach ( $meta['sizes'] as $size => $values ) {

                if ( $values['file'] === $file && $url === array_shift( wp_get_attachment_image_src( $id, $size ) ) )
                    return $id;
            }
        }

        return false;
    }
}


add_filter( 'show_admin_bar' , 'handle_admin_bar');
function handle_admin_bar($content) {
    if (!current_user_can('manage_options')) {
        return false;
    }
}
