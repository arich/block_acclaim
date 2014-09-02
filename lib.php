<?php
//GLOBAL $DB;
require_once(dirname(__FILE__).'/../../config.php');

function query_acclaim_api()
{
    $config = get_config('block_acclaim');
    $url="{$config->url}/api/v1/organizations/{$config->org}/badge_templates";
    $username=$config->token;
    $password = "";
    return return_json_badges($url,$username);
}


function truncate($input)
{
    $max_length = 18;

    if(strlen($input) > $max_length){
        $len = strlen($input);
        $trim_amount = ($max_length - $len);
        return substr($input,0, $trim_amount)."...";
        }

    return $input;
}

function test_the_test()
{
    return "test";
}

function get_badge_info($course_id,$field)
{
    global $DB;
    $return_val = "";

    $course = $DB->get_record('block_acclaim', array('courseid' => $course_id));
    
    if(!empty($course)){
        $return_val = $course->$field;
    }

    return $return_val;
}

function get_block_course($course_id)
{
    global $DB;
    $course = $DB->get_record('block_acclaim', array('courseid' => $course_id), '*', MUST_EXIST);
    return $course;
}

function write_badge_to_issue($fromform)
{
    global $DB;
    $table = 'block_acclaim';

    $fromform = update_form_with_badge_name($fromform);

    $exists = $DB->record_exists_select($table, "courseid = '{$fromform->courseid}'");
        if($exists){
        $DB->delete_records_select($table, "courseid = '{$fromform->courseid}'");
    }
    
    return $DB->insert_record('block_acclaim', $fromform);
}

function get_issue_badge_url()
{
    //https://jefferson-staging.herokuapp.com/api/v1/organizations/6bb2e1c7-c66b-4d47-9301-4a6b9e792e2c/badges
    $block_acclaim_config = get_config('block_acclaim');
    
    $base_url = $block_acclaim_config->url;
    $org_id = $block_acclaim_config->org;
    $request_url = "{$base_url}/api/v1/organizations/{$org_id}/badges";
    return $request_url;
}

function get_request_token(){
    $block_acclaim_config = get_config('block_acclaim');
    return $block_acclaim_config->token;
}

function return_user($user_id){
    global $DB;
    return $DB->get_record('user', array('id'=>$user_id), '*', MUST_EXIST);
}

function convert_time_stamp($timestamp)
{
    if($timestamp){
        return gmdate("Y-m-d  h:i:s a", $timestamp);
    }

    return $timestamp;
}

function update_form_with_badge_name($fromform)
{
    $all_badges_names = json_decode($fromform->badgename);
    $badge_id = $fromform->badgeid;
    
    if(isset($all_badges_names->$badge_id)){
        $badge_name =  $all_badges_names->$badge_id;
        $fromform->badgename = $badge_name;
    }

    return $fromform;
}

function create_data_array($event,$badge_id,$timestamp){
    $user_id = $event->relateduserid;
    $course_id = $event->courseid;
    $user = return_user($user_id);
    $firstname = $user->firstname;
    $lastname = $user->lastname;
    $email = $user->email;
    $expires_at = convert_time_stamp($timestamp); 
    $date_time = convert_time_stamp(time());

    $data = array(
        'badge_template_id' => $badge_id,
        'issued_to_first_name' => $firstname,
        'issued_to_last_name' => $lastname,
        'expires_at' => $expires_at,
        'recipient_email' => $email,
        'issued_at' => $date_time
    );

    return $data;
}

function make_curl_request($header_type,$url,$username,$data)
{
    $ch = curl_init();
    $password = "";

    $curlConfig = array(
        CURLOPT_HTTPHEADER     => array('Accept: application/json'),
        CURLOPT_CUSTOMREQUEST  => $header_type,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD        => $username . ":" . $password,
        CURLOPT_POSTFIELDS     => $data,
    );

    //        error_log('curl_array: '. print_r($curlConfig,true));
    curl_setopt_array($ch, $curlConfig);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $httpCode;
}

function issue_badge_request($data,$url,$token)
{
    $header = "POST";
    return make_curl_request($header,$url,$token,$data);
}


function return_json_badges($url,$username){
    $password = "";
    $ch = curl_init();

    $curlConfig = array(
    CURLOPT_HTTPHEADER     => array('Accept: application/json'),
    CURLOPT_CUSTOMREQUEST  => "GET",
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD        => $username . ":" . $password,
                                                                                                            );
    curl_setopt_array($ch, $curlConfig);

    $result = curl_exec($ch);
    $json = json_decode($result,true);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $json;
}

function build_radio_buttons($json){
     $badge_items = "";
     
     if(isset($json['data'])){
         $badge_items = array();
         foreach($json['data'] as $item){
             $friendly_name = truncate($item["name"]);
             $badge_id = $item["id"];
             $badge_items[$badge_id] = $friendly_name;
             }
        }else{
            error_log("invalid api, token or unable to connect");
            }
    return $badge_items;
}


function block_acclaim_images() {
    $json = query_acclaim_api();
    return build_radio_buttons($json);
}

?>

