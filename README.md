# ZyplinePHP

**ZyplinePHP**, a PHP library for the *Zypline REST API*.

*Note:* **ZyplinePHP** uses **ZypCreds API** for all verifications.

## Methods
The following methods are organized in to categories.
For more information, visit <http://api.zypline.com/docs>.

### Get Destination
	get_destination( $index, $country, $suffix, $ip )
	add_file( $index, $file )

### Verification
	request_verification( $index, $users_ip )
	attempt_verification( $index, $code, $users_ip )
	check_token( $index, $token, $users_ip )

## Brief Examples

### Get Destination for Index
	# Summary: Using a global index, get back a result which can be a url or text.
	require_once('ZyplineREST.php');
	$x = new ZyplineREST( $your_api_id, $your_api_key );
	$x->get_destination( $index, $country, $suffix, $ip );
	$x; # contains resulting data

### Verification Process
	# Step 1 of 3 - Request Verification
	# Summary: A code will be sent to the index to specify. Either via SMS or email.
	require_once('ZyplineREST.php');
	$x = new ZyplineREST( $your_api_id, $your_api_key );
	$x->request_verification( $index );
	$x->result_bool; # Whether or not message was sent.

	# Step 2 of 3 - Attempt Verification
	# Summary: Checks to see if code provided is valid for this index.
	$x = new ZyplineREST( $your_api_id, $your_api_key );
	$x->verify_code( $index, $code_from_user, $users_ip );
	$x->result_bool; # Whether or not users code is correct.
	$x->token; # The token used to identify this correct verification.

	# Step 3 of 3 - Check token validity
	# Summary: Checks to see if token is valid for this index.
	$x = new ZyplineREST( $your_api_id, $your_api_key );
	$x = new ZyplineREST( $your_api_id, $your_api_key );
	$x->check_token( $index, $token, $users_ip );
	$x->result_bool; # Whether or not token is valid

### Adding
# Step 1 - Follow verification Proces to obtain a token
# Step 2 - Add a new pairing to the system
require_once('ZyplineREST.php');
$x = new ZyplineREST( $your_api_id, $your_api_key);
$x->add_pair($index, $file, $token);
$x->result_bool; # Whether or not it was successful
