<?php
/*
   Plugin Name: CCIChat Live Chat
   Plugin URI: https://www.ccichat.com/wordpress_integration/
   Version: 1.2
   Author: <a href="https://www.ccichat.com/">CCIChat.com</a>
   Description: CCIChat is a Free live chat software and chatbot for wordpress websites.
   Text Domain: ccichat
   License: GPLv3
*/

function ccichat_options_page() {
?>
    
    <div id="ccichat_ui_container">
        <div id="ccichat_ui_content">
            <img src="<?php echo plugin_dir_url( __FILE__ ) . 'images/__image_chatters_sm_wlogo__.png'; ?>" />
            <h2>CCIChat Live Chat plugin for Wordpress</h2>
            <p>Install CCIChat to this Wordpress by entering your CCIChat Widget ID.<br />If you do not have a CCIChat Widget ID you can create your Free account at <a href="https://widget.ccichat.com/register" target="_blank">CCIChat.com</a></p>
            <form method="post"  action="options.php">
                <?php settings_fields( 'ccichat_settings' ); ?>
                <?php ccichat_do_options(); ?>
                <div id="ccichat_buttons_section">
                    <p>
                        <input type="submit" class="button-primary" id="ccichat_save" value="<?php _e('Save Changes', 'ccichat') ?>"/>
                        <a class="button-primary" href="https://widget.ccichat.com/dashboard" target="_blank">Go to Dashbaord</a>
                        <input type="button" class="button-primary" id="ccichat_call_registration" value="<?php _e('Create Account', 'ccichat_get_account') ?>"/>
                    </p>
                </div>
            </form>
        </div>
    </div>
<?php
}

function ccichat_menu() {
    add_menu_page(__('CCIChat Live Chat', 'ccichat'), __('CCIChat Live Chat', 'ccichat'), 'manage_options', basename(__FILE__), 'ccichat_options_page',plugin_dir_url( __FILE__ ) . 'images/dashboard_icon.svg');
}
add_action( 'admin_menu', 'ccichat_menu' );

function ccichat_init() {
	register_setting( 'ccichat_settings', 'ccichat', 'ccichat_validate' );
}
add_action( 'admin_init', 'ccichat_init' );

function ccichat_add_stylesheet() 
{
    wp_enqueue_style( 'ccichat', plugins_url( '/css/ccichat_styles.css', __FILE__ ) );
}
add_action('admin_print_styles', 'ccichat_add_stylesheet');

function ccichat_do_options() {
	$options = get_option( 'ccichat' );
    ob_start();
    
    
	?>
        <div class="ccichat_ui_row"><?php _e( '<strong>Enter your CCIChat Widget ID</strong>', 'ccichat' ); ?><input type="text" class="regular-text" id="ccichat_live_id" name="ccichat[ccichat_live_id]" value="<?php echo $options['ccichat_live_id']; ?>" /></div>
	<?php
}

function ccichat_widget_enqueue_script() {
        $options = get_option( 'ccichat' );
        wp_register_script('ccichat',"https://widget.ccichat.com/ccichat_client?id=".$options['ccichat_live_id']);
        wp_enqueue_script('ccichat');
}
add_action('wp_enqueue_scripts', 'ccichat_widget_enqueue_script');

function ccichat_add_id_to_script( $tag, $handle, $src ) {
   if ( 'ccichat' === $handle ) {
        $tag = '<script type="text/javascript" src="' . esc_url( $src ) . '" id="CCILive" async></script>';
   }
   return $tag;
}
add_filter( 'script_loader_tag', 'ccichat_add_id_to_script', 10, 3 );

add_action( 'admin_footer', 'ccichat_get_account_javascript' ); // Write our JS below here

function ccichat_get_account_javascript() { 

    function randomPassword($length = 8, $add_dashes = false, $available_sets = 'luds') {
        $sets = array();
        if(strpos($available_sets, 'l') !== false)
            $sets[] = 'abcdefghjkmnpqrstuvwxyz';
        if(strpos($available_sets, 'u') !== false)
            $sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
        if(strpos($available_sets, 'd') !== false)
            $sets[] = '23456789';
        if(strpos($available_sets, 's') !== false)
            $sets[] = '!@#$%&*?';
        $all = '';
        $password = '';
        foreach($sets as $set)
        {
            $password .= $set[array_rand(str_split($set))];
            $all .= $set;
        }
        $all = str_split($all);
        for($i = 0; $i < $length - count($sets); $i++)
            $password .= $all[array_rand($all)];
        $password = str_shuffle($password);
        if(!$add_dashes)
            return $password;
        $dash_len = floor(sqrt($length));
        $dash_str = '';
        while(strlen($password) > $dash_len)
        {
            $dash_str .= substr($password, 0, $dash_len) . '-';
            $password = substr($password, $dash_len);
        }
        $dash_str .= $password;
        return $dash_str;
    }

    ?>
	<script type="text/javascript" >
	jQuery(document).ready(function($) {

        $('body').on("click", "#ccichat_call_registration", ajax_ccichat_create);
 
        function ajax_ccichat_create() {

            $("#ccichat_call_registration").text('<img src="images/wpspin_light.gif"> Processing');
            $("#ccichat_call_registration").attr('disabled',true);

            var data = {
                'reg_username':  <?php echo '"'.get_option('admin_email').'"'; ?>, 
                'reg_name': <?php echo '"'.get_option('blogname').'"'; ?>, 
                'reg_url': <?php echo '"'.$_SERVER['SERVER_NAME'].'"'; ?>, 
                'reg_password': <?php echo '"'.randomPassword().'"'; ?>,
                'reg_src': "Wordpress"
            };
            jQuery.post("https://widget.ccichat.com/register", data, function(response) {

                    if(response.code == 400){
                        $("#ccichat_call_registration").attr('disabled',false);
                        alert("Some data is missing. Cannot create account. Visit https://widget.ccichat.com/register for account creation.");
                    }
                    else if(response.code == 401){
                        $("#ccichat_call_registration").text('Create Account');
                        $("#ccichat_call_registration").attr('disabled',false);
                        alert("User already exist. Login at https://widget.ccichat.com/ to get your widget_id");
                    }
                    else if (response.code== 402){
                        $("#ccichat_call_registration").text('Create Account');
                        $("#ccichat_call_registration").attr('disabled',false);
                        alert("Invalid domain name.")
                    }
                    else if (response.code == 403){
                        $("#ccichat_call_registration").text('Create Account');
                        $("#ccichat_call_registration").attr('disabled',false);
                        alert("Password doest not meet requirements.")
                    }
                    else if (response.code == 404){
                        $("#ccichat_call_registration").text('Create Account');
                        $("#ccichat_call_registration").attr('disabled',false);
                        alert("Invalid Username/E-mail");
                    }
                    else if (response.code == 200){
                        $("#ccichat_call_registration").text('Create Account');
                        $("#ccichat_call_registration").attr('disabled',false);
                        $("#ccichat_live_id").val(response.widget_id);
                        $("#ccichat_save").click();
      
                    }
                    else{
                        $("#ccichat_call_registration").text('Create Account');
                        $("#ccichat_call_registration").attr('disabled',false);
                        alert("Invalid Response contact CCIChat.com");
                    }

            });
        }
	});
    </script> 
<?php
}

function ccichat_validate($input) {

    $input['ccichat_live_id'] = wp_filter_nohtml_kses( $input['ccichat_live_id'] );

	return $input;
}