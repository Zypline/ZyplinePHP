<?php
/**
 * Class ZyplineREST
 * Summary: A libary which handles requests for Zypline REST API.
 * Version: 0.2
 * Authors: Greg Kasbarian
 * Release Date Version 1.0: n/a
 *
 * Methods:
 *
 *  check_token( $index, $token )
 *  request_verification( $index )
 *  attempt_verification( $index, $code )
 *
 *  get_destination( $index )
 *  browse_destinations( $industry, $zipcode, $radius ) ---- ????
 *
 *  ---
 *
 *  call_uri()
 *  parse_data()
 *  parse_xml()
 *  parse_json()
 *  create_hash()
 *
 **/
class ZyplineREST{

  # Your API Credentials (set in constructor)
  private $api_id  = '';
  private $api_key = '';

  # ---

  # URIs
  private $zyp_uri     = 'http://api.zypline.com/zyp/';
  private $verify_uri      = 'http://api.zypline.com/verify/';

  # Values
  public $raw_index;
  public $raw_country;
  public $raw_ip;

  public $prepped_index;
  public $friendly_index;
  public $secret_code;

  public $token;
  public $token_expiration;
  public $token_bool;

  public $result_desc;
  public $result_bool;
  public $error_no;
  public $error_desc;

  # Settings
  private $timeout = 30;
  private $accept_type = 'json';  # Default return type


  # ----------------------------------------------
  # ----------------------------------------------


  /**
   * __construct()
   * Summary: Requires $api_id and $api_key to begin.
   * @param   str   $api_id   Your API ID
   * @param   str   $api_key  Your API KEY
   * @return  bool  True    Constructor successful.
   * @return  bool  False     Constructor failed.
   **/
  function __construct( $api_id, $api_key ){

    if( !$api_id || !$api_key ){
      return false;
    }else{
      $this->api_id = $api_id;
      $this->api_key = $api_key;
      return true;
    }
  }

  # -------------------------
  ## ZYP API METHODS
  # -------------------------

  function get_destination( $index ){
    # Check for params

    # Prep params
    $params = array( 'index' => $index );

    # Check if
    $data = $this->call_uri( $params, $this->zyp_uri, 'get' );

    # Parse the result
    $this->parse_data( $data );

    # Return raw response
    return $data;
  }

  function add_pair( $index, $file, $token ){

    # Check for required params
    $tmpfile = $file['tmp_name'];
    $filename = basename($file['name']);

    # Prep params array
    $file_str = '@'.$tmpfile.';filename='.$filename.';type='.$file['type'];
    $cfile = new CURLFile($tmpfile, $file['type'], $filename);
    $params = array( 'index' => $index, 'url' => $url, 'uploadedfile' => $cfile, 'token' => $token);

    # Make API call
    $data = $this->call_uri( $params, $this->zyp_uri, 'post');

    # Parse through response
    $this->parse_data($data);

    # Return raw response.
    return $data;
  }

  function delete_pair( $index, $token ){

    $params = array( 'index' => $index, 'token' => $token );

    $data = $this->call_uri( $params, $this->zyp_uri, 'delete');

    $this->parse_data($data);

    return $data;

  }

  # -------------------------
  ## VERIFICATION API METHODS
  # -------------------------

  /**
   * check_token()
   * @param  str $index Index who's session we're checking.
   * @param  str $token Session token that we're checking.
   * @return bool / response data
   **/
  function check_token( $index, $token, $ip ){

    # Check for required params
    if( !$index || !$token ){
     return false;
    }

    # Prep params
    $params = array( 'index' => $index, 'token' => $token, 'ip' => $ip );

    # Check if index/token (along with API ID and timestamp) is valid.
    $data = $this->call_uri( $params, $this->verify_uri, 'get');

    # Parse the result
    $this->parse_data( $data );

    # Return raw response
    return $data;
  }

  /**
   * request_verification()
   * Summary: Requests a verification code be sent to the index provided.
   *  Preps parameters, makes curl call, and then parses data into class variables.
   * @param   str   $index  Index requesting verification for.
   * @return  bool  False   Error occurred - error number and description set.
   * @return  obj   $data   Raw-content returned from ->call_uri() call.
   **/
  function request_verification( $index, $ip ){

    # Check for required values
    if( !$index ){
     return false;
    }

    # Prep params
    $params = array( 'index' => $index, 'ip' => $ip );

    # Initiate request and gather response.
    $data = $this->call_uri( $params, $this->verify_uri, 'post' );

    # Parse the returned content and place values within class.
    $this->parse_data( $data );

    return $data;
  }

  /**
   * attempt_verification()
   * Summary: Check if $code provided is a valid verification code for the $index provided.
   * @param   str   $index    The index you wish to verify.
   * @param   str   $code     Code entered by user.
   * @return  str   $response   Returned content from uri call.
   **/
  function attempt_verification( $index, $code, $ip ){

    # Check for required parameters.
    if( !$index || !$code ){
      return false;
    }

    # Prep params
    $params = array( 'index' => $index, 'code' => $code, 'ip' => $ip );

    # Attempt to verify the code entered by the user
    $data = $this->call_uri( $params, $this->verify_uri, 'put' );

    # Parse the result
    $this->parse_data( $data );

    # Return raw response
    return $data;
  }

  # --- --- --- --- --- --- --- --- --- ---
  # --- --- --- --- --- --- --- --- --- ---

  # ---------------------------------
  ## PRIVATE METHODS / HELPER METHODS
  # ---------------------------------

  /**
   * call_uri( $data, $uri, $verb )
   * Summary: Makes a CURL call to the $uri provided and returns the resulting content.
   * @param   array   $data   Parameters passed by user.
   * @param   str   $uri  URI to call.
   * @param   str   $verb   Form submission method - GET POST PUT DELETE
   * @return  str   $data   Resulting content (XML or JSON)
   **/
  private function call_uri( $data, $uri, $verb ){

    # Affix api id to params list
    $data = array_merge($data, array('api_id' => $this->api_id));

    # Create hash
    $hash = $this->create_hash( $data, $this->api_key );

    # Affix hash to params list
    $data = array_merge($data, array('hash' => $hash));

    # Start curl
    $ch = curl_init();

    # Decide verb
    switch( $verb ){

      case 'get' :
        # Affix params to the uri for GET
        $uri = $uri . '?' . http_build_query($data);
        break;

      case 'post' :
        # Place params in post and enable post.
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
          break;

      case 'put':
        # CUSTOM REQUEST FOR PUT - USE SAME POSTFIELDS
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        break;

      case 'delete':
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        break;
    }

    # SET CURL OPTIONS
    curl_setopt( $ch, CURLOPT_URL, $uri );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
    curl_setopt( $ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");

    # Set accept header
    if( $this->accept_type == 'json' ){
      $header_str = array("Accept: application/json");
    }else{
      $header_str = array("Accept: text/xml");
    }
    curl_setopt( $ch, CURLOPT_HTTPHEADER, $header_str);

    # Execute curl
    $data = curl_exec( $ch );
    curl_close ( $ch );
    return $data;
  }

  /**
   * parse_data()
   * Summary: Calls either JSON or XML parse depending on what the accepted content type is.
   * @param   str   $data   Returned content from the ->call_uri() call.
   * @return  void
   **/
  private function parse_data( $data ){

    if( $this->accept_type == 'json' ){
      $this->parse_json( $data );
    }else{
      $this->parse_xml( $data );
    }
  }

  /**
   * parse_xml( $data )
   * Summary: Parses through the XML result and places all the values in the class variables.
   * @param   str   $data    The resulting XML from the CURL call.
   * @return  void
   **/
  private function parse_xml( $xml ){

    if(!$xml){ return false; }

    $obj = new SimpleXMLElement( $xml );
    # Use (string) or (int) or SimpleXMLElement passes an object and causes SESSION fail.
    $this->raw_index        = (string)$obj->params['index'];
    $this->raw_country      = (string)$obj->params['country'];
    $this->raw_ip           = (string)$obj->params['ip'];

    $this->prepped_index    = (string)$obj->index['prepped'];
    $this->friendly_index   = (string)$obj->index['friendly'];

    $this->token            = (string)$obj->token['token'];
    $this->token_expiration = (string)$obj->token['expiration'];
    $this->token_bool       = (string)$obj->token['bool'];

    $this->result_bool      = (string)$obj->result['bool'];
    $this->result_desc      = (string)$obj->result['description'];
    $this->error_no         = (string)$obj->error['number'];
    $this->error_desc       = (string)$obj->error['description'];
    return true;
  }

  /**
   * parse_json
   * Summary: Parses JSON and places values in class variables.
   * @param   str   $json   The resulting JSON content.
   * @return  void
   **/
  private function parse_json( $json ){

    if( !$json ){ return false; }

    $x = json_decode($json, true);
    $x = $x['zypline'];

    $this->raw_index        = (string)$x['params']['index'];
    $this->raw_country      = (string)$x['params']['country'];
    $this->raw_suffix       = (string)$x['params']['suffix'];
    $this->raw_ip           = (string)$x['params']['ip'];

    $this->prepped_index    = (string)$x['index']['prepped'];
    $this->friendly_index   = (string)$x['index']['friendly'];
    $this->suffix           = (string)$x['index']['suffix'];
    $this->local_index      = (string)$x['index']['local'];
    $this->contact_index    = (string)$x['index']['contact'];
    $this->navi             = (string)$x['index']['navi'];
    $this->short_navi       = (string)$x['index']['shortnavi'];

    $this->url              = (string)$x['destination']['url'];
    $this->filename         = (string)$x['destination']['filename'];
    $this->type             = (string)$x['destination']['type'];
    $this->updated          = (string)$x['destination']['updated'];
    $this->expiration       = (string)$x['destination']['expiration'];

    $this->thumbs_full      = (string)$x['destination']['thumbs']['full'];
    $this->thumbs_large     = (string)$x['destination']['thumbs']['large'];
    $this->thumbs_medium    = (string)$x['destination']['thumbs']['medium'];
    $this->thumbs_small     = (string)$x['destination']['thumbs']['small'];

    $this->token            = (string)$x['token']['token'];
    $this->token_expiration = (string)$x['token']['expiration'];
    $this->token_bool       = (string)$x['token']['bool'];

    $this->country_code     = (string)$x['meta']['country']['code'];
    $this->country_iso      = (string)$x['meta']['country']['iso'];
    $this->country_name     = (string)$x['meta']['country']['name'];

    $this->result_bool      = (string)$x['meta']['result']['bool'];
    $this->result_desc      = (string)$x['meta']['result']['description'];
    $this->error_no         = (string)$x['meta']['error']['number'];
    $this->error_desc       = (string)$x['meta']['error']['description'];

    return true;
  }

  /**
   * create_hash()
   * Summary: Creates a hash value from the params passed to it.
   * @param   arr   $params Parameters being passed to API.
   * @return  str   $hash   The properly hashed value from using params and the api_key
   **/
  private function create_hash( $data, $api_key ){
    $str = '';
    foreach($data as $k => $v){
      if( $k != 'hash' && $k != 'siteurl' && $k != 'uploadedfile' && $v != ''){ $str .= $k.$v; }
    }
    return hash_hmac('sha1', $str, $api_key);
  }
}# End Class
?>
