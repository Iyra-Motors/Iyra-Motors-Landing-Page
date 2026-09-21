<?php
/** Iyra Motors independent WordPress theme. */
defined('ABSPATH') || exit;
add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array('search-form', 'gallery', 'caption', 'style', 'script'));
});
function iyra_vehicle_fields() {
    return array('year'=>'Year','make'=>'Make','model'=>'Model','trim'=>'Trim','price'=>'Listed price ($)','mileage'=>'Mileage','body'=>'Body style (SUV, Sedan, Truck, Coupe)','fuel'=>'Fuel type','drive'=>'Drivetrain','transmission'=>'Transmission','color'=>'Exterior color','vin'=>'VIN','features'=>'Features (one per line)');
}
add_action('init', function () {
    register_post_type('iyra_vehicle', array('labels'=>array('name'=>'Vehicles','singular_name'=>'Vehicle','add_new_item'=>'Add vehicle'), 'public'=>true, 'has_archive'=>true, 'rewrite'=>array('slug'=>'vehicle'), 'menu_icon'=>'dashicons-car', 'show_in_rest'=>true, 'supports'=>array('title','editor','thumbnail','revisions')));
    register_post_type('iyra_inquiry', array('labels'=>array('name'=>'Customer inquiries','singular_name'=>'Inquiry'), 'public'=>false, 'publicly_queryable'=>false, 'show_ui'=>true, 'show_in_rest'=>false, 'exclude_from_search'=>true, 'menu_icon'=>'dashicons-email-alt', 'supports'=>array('title','editor'), 'capability_type'=>'post', 'map_meta_cap'=>true, 'capabilities'=>array('edit_posts'=>'manage_options','edit_others_posts'=>'manage_options','publish_posts'=>'manage_options','read_private_posts'=>'manage_options','delete_posts'=>'manage_options','create_posts'=>'do_not_allow')));
});
add_action('after_switch_theme', function () { flush_rewrite_rules(); });
add_action('add_meta_boxes', function () {
    add_meta_box('iyra-specs','Vehicle details',function ($post) {
        wp_nonce_field('iyra_save_vehicle','iyra_vehicle_nonce');
        echo '<p>Set the vehicle photo using Featured image. Use the editor for the description. Publish only confirmed inventory. Move sold vehicles to Draft to remove them from the website.</p>';
        foreach (iyra_vehicle_fields() as $key=>$label) {
            $value=get_post_meta($post->ID, '_iyra_'.$key, true);
            echo '<p><label for="iyra-'.esc_attr($key).'"><strong>'.esc_html($label).'</strong></label><br>';
            if ($key==='features') echo '<textarea style="width:100%;max-width:650px" rows="6" id="iyra-'.esc_attr($key).'" name="iyra_'.esc_attr($key).'">'.esc_textarea($value).'</textarea>';
            else echo '<input style="width:100%;max-width:650px" id="iyra-'.esc_attr($key).'" name="iyra_'.esc_attr($key).'" type="'.(in_array($key,array('year','price','mileage'),true)?'number':'text').'" min="0" value="'.esc_attr($value).'">';
            echo '</p>';
        }
    },'iyra_vehicle','normal','high');
});
add_action('save_post_iyra_vehicle', function ($id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['iyra_vehicle_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['iyra_vehicle_nonce'])),'iyra_save_vehicle') || !current_user_can('edit_post',$id)) return;
    foreach (iyra_vehicle_fields() as $key=>$label) {
        if (!isset($_POST['iyra_'.$key]) || !is_scalar($_POST['iyra_'.$key])) continue;
        $value=wp_unslash($_POST['iyra_'.$key]);
        $value=in_array($key,array('year','price','mileage'),true)?absint($value):($key==='features'?sanitize_textarea_field($value):sanitize_text_field($value));
        update_post_meta($id,'_iyra_'.$key,$value);
    }
});
function iyra_inventory() {
    $records=get_posts(array('post_type'=>'iyra_vehicle','post_status'=>'publish','numberposts'=>-1,'orderby'=>'date','order'=>'DESC'));
    $items=array();
    foreach ($records as $post) {
        $item=array('id'=>(string)$post->ID,'description'=>wp_strip_all_tags($post->post_content),'sample'=>false);
        foreach (iyra_vehicle_fields() as $key=>$label) $item[$key]=get_post_meta($post->ID,'_iyra_'.$key,true);
        foreach (array('year','price','mileage') as $key) $item[$key]=(int)$item[$key];
        $item['features']=array_values(array_filter(array_map('trim',explode("\n",$item['features']))));
        $item['image']=get_the_post_thumbnail_url($post->ID,'large');
        // Incomplete vehicles remain in the editor but are not advertised.
        if (!$item['image'] || !$item['make'] || !$item['model'] || !$item['year'] || $item['price']<=0) continue;
        $items[]=$item;
    }
    return $items;
}
add_action('admin_menu',function () {
    add_theme_page('Iyra Motors settings','Iyra Motors','manage_options','iyra-settings','iyra_settings_page');
});
add_action('admin_init',function () {
    foreach (array('phone','email','address','hours','inquiry_email') as $field) register_setting('iyra_settings','iyra_'.$field,array('type'=>'string','sanitize_callback'=>strpos($field,'email')!==false?'sanitize_email':($field==='hours'?'sanitize_textarea_field':'sanitize_text_field')));
    register_setting('iyra_settings','iyra_enable_inquiries',array('type'=>'boolean','sanitize_callback'=>'rest_sanitize_boolean','default'=>false));
});
function iyra_settings_page() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="wrap"><h1>Iyra Motors</h1><p>Add your dealership contact details and a monitored email address. Customer inquiries are stored under Customer inquiries; notifications also go to the address below. Configure and test email delivery on your host before enabling requests.</p><form method="post" action="options.php">';
    settings_fields('iyra_settings');
    echo '<table class="form-table">';
    foreach (array('phone'=>'Public phone number','email'=>'Public email address','address'=>'Dealership address','hours'=>'Opening hours','inquiry_email'=>'Inquiry notification email') as $field=>$label) {
        echo '<tr><th><label for="iyra_'.esc_attr($field).'">'.esc_html($label).'</label></th><td>';
        if ($field==='hours') echo '<textarea rows="5" class="large-text" id="iyra_hours" name="iyra_hours">'.esc_textarea(get_option('iyra_hours','')).'</textarea>';
        else echo '<input class="regular-text" id="iyra_'.esc_attr($field).'" name="iyra_'.esc_attr($field).'" type="'.(strpos($field,'email')!==false?'email':'text').'" value="'.esc_attr(get_option('iyra_'.$field,'')).'">';
        echo '</td></tr>';
    }
    echo '<tr><th>Online inquiries</th><td><input type="hidden" name="iyra_enable_inquiries" value="0"><label><input type="checkbox" name="iyra_enable_inquiries" value="1" '.checked(get_option('iyra_enable_inquiries',false),true,false).'> Enable after contact details, privacy policy, and email delivery have been checked</label></td></tr></table>';
    submit_button(); echo '</form></div>';
}
function iyra_settings() {
    return array('demo'=>false,'assetBase'=>get_template_directory_uri().'/images/','vehicles'=>iyra_inventory(),'phone'=>get_option('iyra_phone',''),'email'=>get_option('iyra_email',''),'address'=>get_option('iyra_address',''),'hours'=>get_option('iyra_hours',''),'privacyUrl'=>get_privacy_policy_url(),'leadEndpoint'=>get_option('iyra_enable_inquiries',false)?rest_url('iyra/v1/inquiries'):'','nonce'=>wp_create_nonce('wp_rest'),'initialRoute'=>is_singular('iyra_vehicle')?'/vehicle/'.get_queried_object_id():'/');
}
add_action('wp_enqueue_scripts',function () {
    $url=get_template_directory_uri();
    wp_enqueue_style('iyra-app',$url.'/assets/iyra.css',array(),'1.0.0');
    wp_enqueue_script('iyra-app',$url.'/assets/iyra.js',array(),'1.0.0',true);
    wp_add_inline_script('iyra-app','window.IYRA_SETTINGS = '.wp_json_encode(iyra_settings(),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).';','before');
});
add_action('wp_head',function () {
    echo '<link rel="icon" type="image/svg+xml" href="'.esc_url(get_template_directory_uri().'/images/favicon.svg').'">';
    echo '<meta name="description" content="Explore pre-owned vehicles at Iyra Motors. Find your next drive, estimate a payment, and ask about selling or trading your car.">';
});
function iyra_inquiry_permission($request) {
    if (!get_option('iyra_enable_inquiries',false)) return new WP_Error('iyra_unavailable','Online requests are unavailable. Please contact the dealership directly.',array('status'=>503));
    $origin=$request->get_header('origin');
    if ($origin && strtolower((string)wp_parse_url($origin,PHP_URL_HOST))!==strtolower((string)wp_parse_url(home_url(),PHP_URL_HOST))) return new WP_Error('iyra_origin','Request origin is not allowed.',array('status'=>403));
    if (!wp_verify_nonce($request->get_header('x-wp-nonce'),'wp_rest')) return new WP_Error('iyra_expired','This form has expired. Refresh the page and try again.',array('status'=>403));
    return true;
}
function iyra_create_inquiry($request) {
    if (strlen($request->get_body())>16384) return new WP_Error('iyra_size','Request is too long.',array('status'=>413));
    $data=$request->get_json_params();
    if (!is_array($data)) return new WP_Error('iyra_fields','Invalid request.',array('status'=>400));
    if (!empty($data['website'])) return new WP_Error('iyra_spam','Unable to submit this request.',array('status'=>400));
    if (($data['consent']??false)!==true) return new WP_Error('iyra_consent','Please agree to be contacted about your request.',array('status'=>400));
    foreach (array('firstName','lastName','email') as $field) if (empty($data[$field]) || !is_string($data[$field]) || strlen($data[$field])>250) return new WP_Error('iyra_fields','Please enter your name and a valid email address.',array('status'=>400));
    $email=sanitize_email($data['email']);
    if (!is_email($email)) return new WP_Error('iyra_email','Please enter a valid email address.',array('status'=>400));
    $kind=$data['kind']??'contact';
    if (!in_array($kind,array('contact','trade','availability','test-drive'),true)) return new WP_Error('iyra_kind','Invalid request type.',array('status'=>400));
    if ($kind==='trade' && (empty($data['year'])||empty($data['tradeVehicle'])||!isset($data['mileage'])||!is_numeric($data['year'])||!is_numeric($data['mileage'])||(float)$data['mileage']<0)) return new WP_Error('iyra_trade','Please enter the vehicle year, make, model, and mileage.',array('status'=>400));
    if ($kind==='test-drive' && (empty($data['date'])||!is_string($data['date'])||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$data['date']))) return new WP_Error('iyra_date','Please choose a preferred date.',array('status'=>400));
    if (!empty($data['vehicleId'])) {
        $vehicle=get_post(absint($data['vehicleId']));
        if (!$vehicle || $vehicle->post_type!=='iyra_vehicle' || $vehicle->post_status!=='publish') return new WP_Error('iyra_vehicle','This vehicle is no longer available. Please browse our current inventory.',array('status'=>400));
    }
    $ip=isset($_SERVER['REMOTE_ADDR'])?sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])):'unknown';
    $key='iyra_rate_'.hash_hmac('sha256',$ip,wp_salt('nonce'));
    $count=(int)get_transient($key);
    if ($count>=5) return new WP_Error('iyra_rate','Too many requests. Please try again in an hour.',array('status'=>429));
    $lines=array('Request: '.$kind,'Received: '.current_time('mysql'),'Contact consent: Yes');
    foreach (array('firstName','lastName','email','phone','vehicleId','year','tradeVehicle','mileage','vin','date','message') as $field) {
        if (!isset($data[$field])) continue;
        if (!is_scalar($data[$field]) || strlen((string)$data[$field])>3000) return new WP_Error('iyra_fields','One of the fields is invalid or too long.',array('status'=>400));
        $lines[]=$field.': '.sanitize_textarea_field((string)$data[$field]);
    }
    $body=implode("\n",$lines);
    $id=wp_insert_post(array('post_type'=>'iyra_inquiry','post_status'=>'private','post_title'=>sanitize_text_field($kind.' — '.$data['firstName'].' '.$data['lastName']),'post_content'=>$body),true);
    if (is_wp_error($id)) return new WP_Error('iyra_save','Your request could not be saved. Please try again.',array('status'=>500));
    set_transient($key,$count+1,HOUR_IN_SECONDS);
    $to=get_option('iyra_inquiry_email','');
    $sent=$to && is_email($to)?wp_mail($to,'Iyra Motors: new '.$kind.' inquiry',$body):false;
    update_post_meta($id,'_iyra_notification_sent',(bool)$sent);
    return new WP_REST_Response(array('success'=>true),201);
}
add_action('rest_api_init',function () {
    register_rest_route('iyra/v1','/inquiries',array('methods'=>'POST','permission_callback'=>'iyra_inquiry_permission','callback'=>'iyra_create_inquiry'));
});
