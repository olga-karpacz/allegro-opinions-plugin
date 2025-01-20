<?php

/*
 * Plugin Name:       Studio Afterglow - Allegro opinions plugin
 * Plugin URI:        https://github.com/olga-karpacz/allegro-opinions-plugin
 * Description:       Plugin for Allegro API integration - displaying customers opinions
 * Version:           0.1
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Olga Karpacz
 * Author URI:        https://studioafterglow.pl/
 * Text Domain:       allegro-opinions
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html */

/* ENQUEUING SCRIPTS AND STYLES */

function saallegroapi_public_files() {

    wp_enqueue_style( 'sallapi_style', plugins_url( 'public/style.css', __FILE__ ) );
}
add_action( 'wp_enqueue_scripts', 'saallegroapi_public_files' );

/* ALLEGRO OPINIONS */

//options page

/* OPTIONS */

function saallegroapi_allegro_options_page()
{
    add_menu_page( 
        'Allegro API Settings',
        'Allegro Opinions',
        'manage_options',
        'saallegroapi_allegro_settings',
        '',
        'dashicons-star-filled'
    );
    add_submenu_page(
        'saallegroapi_allegro_settings',
        'API Settings',
        'API Settings',
        'manage_options',
        'saallegroapi_allegro_settings',
        'saallegroapi_allegro_settings_html'
    );
}
add_action('admin_menu', 'saallegroapi_allegro_options_page');

function saallegroapi_allegro_settings_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    settings_errors( 'saallegroapi_allegro_messages' );
?>
<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <?php 
    $redirect_url = get_home_url()."/wp-admin/admin.php?page=saallegroapi_allegro_settings";
    ?>
    <form action="options.php" method="post">
        <?php
    if(isset($_GET["allegro_api_reset"])){
        update_option('saallegroapi_allegro_client_id','');
        update_option('saallegroapi_allegro_client_secret','');
        update_option('saallegroapi_access_token','');
        ?>

        <div class="notice notice-success is-dismissible">
            <p>Data reset. <a href="<?php echo $redirect_url; ?>">Return to edit</a></p>
        </div>
        <?php
    }
    else{
        settings_fields( 'saallegroapi_allegro_settings' );
        do_settings_sections( 'saallegroapi_allegro_settings' );
        if(get_option( 'saallegroapi_allegro_client_id' ) != '' && get_option( 'saallegroapi_allegro_client_secret' ) != ''){
            if(get_option( 'saallegroapi_access_token' ) == '' || !saallegroapi_allegro_check_connection()){
                if (isset($_GET["code"])) {
                    $refreshToken = saallegroapi_get_access_token($_GET["code"],$redirect_url);
                    $nextToken= saallegroapi_allegro_token_refresh($refreshToken,$redirect_url);
                    update_option('saallegroapi_access_token',$nextToken);
        ?>
        <p>Allegro account access code obtained</p>
        <p><strong>If you want to change your data: </strong><a href="<?php echo $redirect_url."&allegro_api_reset=1"; ?>">Reset account data</a></p>
        <?php
                } else {    
                    saallegroapi_get_authorization_code($redirect_url);
                }
            }
            else {
        ?><p>Allegro account access code obtained</p>
        <p><strong>If you want to change your data: </strong><a href="<?php echo $redirect_url."&allegro_api_reset=1"; ?>">Reset account data</a></p>
        <?php
            } 
        }
        else{
            update_option('saallegroapi_access_token','');
            if(get_option( 'saallegroapi_allegro_client_id' ) == ''){
        ?>
        <p><strong>Enter your CLIENT ID to connect the website to your Allegro account</strong></p>
        <?php
            }
            if(get_option( 'saallegroapi_allegro_client_secret' ) == ''){
        ?>
        <p><strong>Enter CLIENT SECRET to connect the page to your Allegro account</strong></p>
        <?php
            }
        }
        submit_button( 'Zapisz zmiany' );
        ?>
    </form>
    <?php
    }
    ?>
</div>
<?php

}


function saallegroapi_allegro_settings_init() {
    register_setting( 'saallegroapi_allegro_settings', 'saallegroapi_allegro_client_id' );
    register_setting( 'saallegroapi_allegro_settings', 'saallegroapi_allegro_client_secret' );

    add_settings_section(
        'saallegroapi_allegro_settings_section',
        'App settings', 
        'saallegroapi_allegro_settings_section_callback',
        'saallegroapi_allegro_settings'
    );

    add_settings_field(
        'saallegroapi_allegro_client_id',
        'CLIENT ID',
        'saallegroapi_allegro_client_id_callback',
        'saallegroapi_allegro_settings',
        'saallegroapi_allegro_settings_section'
    );
    add_settings_field(
        'saallegroapi_allegro_client_secret',
        'CLIENT SECRET',
        'saallegroapi_allegro_client_secret_callback',
        'saallegroapi_allegro_settings',
        'saallegroapi_allegro_settings_section'
    );

}
add_action( 'admin_init', 'saallegroapi_allegro_settings_init' );


function saallegroapi_allegro_settings_section_callback( $args ) {
?>
<p id="<?php echo esc_attr( $args['id'] ); ?>">Ustawienia aplikacji</p>
<?php
                                                              }

function saallegroapi_allegro_client_id_callback( $args ) {
    $setting = get_option( 'saallegroapi_allegro_client_id' );
?>
<input type="text"
       name="saallegroapi_allegro_client_id"
       value="<?php echo isset( $setting ) ? esc_attr( $setting ) : ''; ?>"
       <?php if(isset( $setting ) && $setting!=''){
        echo "disabled";
    }
       ?>
       >
<?php
}

function saallegroapi_allegro_client_secret_callback( $args ) {
    $setting = get_option( 'saallegroapi_allegro_client_secret' );
?>
<input type="text"
       name="saallegroapi_allegro_client_secret"
       value="<?php echo isset( $setting ) ? esc_attr( $setting ) : ''; ?>"
       <?php if(isset( $setting ) && $setting!=''){
        echo "disabled";
    }
       ?>
       >
<?php
}

function saallegroapi_get_authorization_code($redirect_url) {
    $client_id = get_option("saallegroapi_allegro_client_id");
    $auth_url = 'https://allegro.pl.allegrosandbox.pl/auth/oauth/authorize';
    //$auth_url = 'https://allegro.pl/auth/oauth/authorize';
    $authorization_redirect_url = $auth_url . "?response_type=code&client_id=" 
        . $client_id . "&redirect_uri=" . $redirect_url;
?>
<html>
    <body>
        <p><strong>If you want to change your data: </strong><a href="<?php echo $redirect_url."&allegro_api_reset=1"; ?>">Reset account data</a></p>
        <p><strong>Log in to your Allegro account to get the access code</strong></p>
        <a href="<?php echo $authorization_redirect_url; ?>">Log in to Allegro</a>
    </body>
</html>
<?php
}

function saallegroapi_allegro_token_refresh($token,$redirect_url){

    $client_id = get_option("saallegroapi_allegro_client_id");
    $client_secret = get_option("saallegroapi_allegro_client_secret");

    $authorization = base64_encode($client_id.':'.$client_secret);
    $headers = array("Authorization: Basic {$authorization}","Content-Type: application/x-www-form-urlencoded");
    $content = "grant_type=refresh_token&refresh_token={$token}&redirect_uri=" . $redirect_url;
    $ch = saallegroapi_get_curl($headers, $content);
    $tokenResult = curl_exec($ch);
    $resultCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($tokenResult === false || $resultCode !== 200) {
        exit ("Something went wrong token refresh:  $resultCode $tokenResult");
    }

    return json_decode($tokenResult)->access_token;
}

function saallegroapi_get_access_token($authorization_code, $redirect_url) {
    $client_id = get_option("saallegroapi_allegro_client_id");
    $client_secret = get_option("saallegroapi_allegro_client_secret");

    $authorization = base64_encode($client_id.':'.$client_secret);
    $authorization_code = urlencode($authorization_code);
    $headers = array("Authorization: Basic {$authorization}","Content-Type: application/x-www-form-urlencoded");
    $content = "grant_type=authorization_code&code=".$authorization_code."&redirect_uri=" . $redirect_url;
    $ch = saallegroapi_get_curl($headers, $content);
    $tokenResult = curl_exec($ch);
    $resultCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($tokenResult === false || $resultCode !== 200) {
        exit ("Something went wrong access token $resultCode $tokenResult");
    }
    return json_decode($tokenResult)->refresh_token;
}

function saallegroapi_get_curl($headers, $content) {
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => 'https://allegro.pl.allegrosandbox.pl/auth/oauth/token',
        //CURLOPT_URL => 'https://allegro.pl/auth/oauth/token',
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $content
    ));
    return $ch;
}

function saallegroapi_get_allegro_user($token){
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, 'https://api.allegro.pl.allegrosandbox.pl/sale/user-ratings');
    //curl_setopt($ch, CURLOPT_URL, 'https://api.allegro.pl/sale/user-ratings');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);


    $headers = array();
    $headers[] = 'Authorization: Bearer '.$token;
    $headers[] = 'Accept: application/vnd.allegro.public.v1+json';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result=json_decode(curl_exec($ch),true);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close($ch);
    return $result;

}
function saallegroapi_get_allegro_opinions($token, $offer){
    $ch = curl_init();

    $curl_url = 'https://api.allegro.pl.allegrosandbox.pl/sale/offers/'.$offer.'/rating';
    //$curl_url = 'https://api.allegro.pl/sale/offers/'.$offer.'/rating';

    curl_setopt($ch, CURLOPT_URL, $curl_url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);


    $headers = array();
    $headers[] = 'Authorization: Bearer '.$token;
    $headers[] = 'Accept: application/vnd.allegro.public.v1+json';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result=json_decode(curl_exec($ch),true);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close($ch);
    return $result;

}

function saallegroapi_allegro_check_connection(){
    $token = get_option('saallegroapi_access_token');
    $result = saallegroapi_get_allegro_user($token);
    if(isset($result["error"])){
        return false;
    }
    else return true;
}

/* OPINIONS SHORTCODE */

add_shortcode('opinie_allegro', 'saallegroapi_allegro_shortcode');
if ( !function_exists( 'saallegroapi_allegro_shortcode' ) ) {
    function saallegroapi_allegro_shortcode( $atts = [], $content = null) {
        ob_start();
        $all_reviews = [];
        if(saallegroapi_allegro_check_connection()){
            $token = get_option('saallegroapi_access_token');
            $api_response = saallegroapi_get_allegro_user($token);
            if(isset($api_response['ratings'])){
                $limit = count($api_response['ratings']);
                if($limit>=6){
                    $limit = 6;
                }
                for($i=0;$i<$limit;$i++){
                    $review = $api_response['ratings'][$i];
                    if($review['comment'] != ''){
                        array_push($all_reviews, array(
                            'date' => $review['createdAt'],
                            'rating' => $review['rates']['service'],
                            'text' => $review['comment']
                        ));
                    }
                }
                $string = serialize( $all_reviews );  
                update_option('saallegroapi_last_reviews', $string);
            }
        }
        else{
            if(get_option("saallegroapi_last_reviews") && get_option("saallegroapi_last_reviews")!=''){
                $last_reviews_string = get_option("saallegroapi_last_reviews");
                $all_reviews = unserialize($last_reviews_string);
            }
        }

?>
<div class="saallegroapi-allegro__widget-bg">
    <div class="saallegroapi-allegro__opinions-div">
        <?php
        $reviews_count = count($all_reviews);
        for($i=0;$i<$reviews_count;$i++){
            $review = $all_reviews[$i];
        ?>
        <div class="saallegroapi-allegro__opinion">
            <span class="saallegroapi-allegro__date">
                <?php
            $pl_months = array(1 => 'stycznia', 'lutego', 'marca', 'kwietnia', 'maja', 'czerwca', 'lipca', 'sierpnia', 'września', 'października', 'listopada', 'grudnia');
            echo date("d", strtotime($review['date']))." ".$pl_months[date("n", strtotime($review['date']))]." ".date("Y", strtotime($review['date']));                ?>
            </span>
            <div class="saallegroapi-allegro-star-rating">
                <?php
            for($l=0;$l<$review['rating'];$l++){
                ?><i class="elementor-star-full">★</i><?php
            }
                ?>
            </div>
            <p class="saallegroapi-allegro__content">
                <?php echo $review['text']; ?>
            </p>
        </div>
        <?php
        }
        ?>
    </div>
</div>
<?php
        return ob_get_clean();
    }
}
