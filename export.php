<?php


//This PHP program exports a set of user contact information from REDCap, a web application for managing 
//user data information and imports it into CiviCRM, a wordpress CMS website


//importing user contact data from RedCap contant management system


function check999($data)
{
	if ($data == 999 || $data == 9999 )
		return "I don't know";
	else
		return $data;
}

function multi_select($data)
{
	if (count($data) == 1)
	{
		return $data;
	};
	$result= array();
	$values= explode(',', $data);
	foreach ($values as $x)
		array_push($result, $x);
	return $result;
}

//Format for retrieving the contact data from the Redcap database through their API playground
$data = array(
    'token' => '5ECB257DB11D82F150D16390BFE31279',
    'content' => 'record',
    'format' => 'json',
    'type' => 'flat',
    'forms' => array('current'),
    'rawOrLabel' => 'raw',
    'rawOrLabelHeaders' => 'raw',
    'exportCheckboxLabel' => 'false',
    'exportSurveyFields' => 'false',
    'exportDataAccessGroups' => 'false',
    'returnFormat' => 'json'
);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://term2.bio.uci.edu/redcapdev/api/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_VERBOSE, 0);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_AUTOREFERER, true);
curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
$output = curl_exec($ch);

$array= json_decode($output, true);
curl_close($ch);

$keys= array_keys($array[2]);


//getting the list of states from civicrm

$data= array(
	'api_key' => 'nLbAvy7i8CzPpQQDLPBR4ew8',
	'key' => '6af3aaa08ed7efaa1a8dcb543286043d',
	'entity' => 'address',
	'action' => 'getoptions',
	'field' => 'state_province_id',
	'country_id' => 1228
);

$ch= curl_init();
curl_setopt($ch, CURLOPT_URL, "http://192.168.137.14/wp-content/plugins/civicrm/civicrm/extern/rest.php?");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
$result= curl_exec($ch);
$state_data= simplexml_load_string($result);
curl_close($ch);


//Looping through each of the users
for ($i = 1; $i < count($array); $i++)
{

	//Sending a curl GET request to the CiviCRM server to check if the user already exists by checking first name, last name, and email
	$data= array(
		'api_key' => 'nLbAvy7i8CzPpQQDLPBR4ew8',
		'key' => '6af3aaa08ed7efaa1a8dcb543286043d',
		'entity' => 'contact',
		'action' => 'get',
		'first_name' => $array[$i]['cur_firstname'],
		'last_name' => $array[$i]['cur_lastname'],
		'email' => $array[$i]['cur_email'],
		'gender_id' => $array[$i]['cur_sex'],
		'birth_date' => $array[$i]['cur_dob'],
		'contact_type' => 'individual'
	);
	$ch= curl_init();
	curl_setopt($ch, CURLOPT_URL, "http://192.168.137.14/wp-content/plugins/civicrm/civicrm/extern/rest.php?");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
	$result= curl_exec($ch);
	$xml_data= simplexml_load_string($result);
	
	
	curl_close($ch);

	//If the user already exists, continue on to the next user
	if (($xml_data -> {'Result'} -> {'contact_id'}))
	{
		//echo 'Contact already exists!'  ."\r\n";
		echo '!';
		continue;
	}

	//Changing the action entity to create and sending another API request, this time a POST to actually create a user in the CiviCRM database
	//custom fields are inputted in here as well

	
	//Automating the input for the rest of the custom fields
	$count= 9;
	for ($x= 20; $x < count($keys); $x++)
	{
		$base= 'custom_' . $count;
		$data[$base]= $array[$i][$keys[$x]];
		$count++;
		//accounting for errors when entering fields (once you delete a custom field, it skips over the number FOREVER when creating new fields)
		//These are corrections for values skipped
		if ($count == 67)
		{
			$count = 99;
		}
		if ($count == 140)
		{
			$count= 154;
		}
	}

	//discrepencies and NEW CUSTOM FIELDS GO HERE
	$data['action']= 'create';
	$data['custom_1']= $array[$i]['cur_rcv_calls']; //Would you like to recieve calls about studies?
	$data['custom_2']= $array[$i]['cur_rcv_emails']; //recieve email about studies?
	$data['custom_3']= $array[$i]['cur_rcv_mail']; //recieve mail about studies?
	$data['custom_4']= $array[$i]['cur_signup_enewsletter']; //sign up for e-newsletter?
	$data['custom_5']= $array[$i]['cur_signup_tweets']; //sign up for text messages/tweets?
	$data['custom_6']= $array[$i]['cur_pref_contact_method'];
	$data['custom_7']= $array[$i]['cur_ref_method'];
	$data['custom_8']= $array[$i]['cur_ref_method_other'];
	$data['custom_29']= $array[$i]['cur_sex'];
	$data['custom_28']= (int)$array[$i]['cur_weight'];
	$data['custom_33']= 88; //DELETE WHEN INPUT DATA IS CORRECT
	$data['custom_39']= check999($array[$i]['cur_mom_age']);
	$data['custom_42']= check999($array[$i]['cur_mom_death']);
	$data['custom_46']= check999($array[$i]['cur_dad_age']);
	$data['custom_49']= check999($array[$i]['cur_dad_death_age']);
	$data['custom_59']= check999($array[$i]['cur_cancer_dx_date']);
	$data['custom_63']= check999($array[$i]['cur_cancer_treat_recent_year']);
	$data['custom_175']= $array[$i]['cur_partner'];
	//parsing multiselect fields
	$data['custom_34']= multi_select($array[$i]['cur_spoke_lang']);
	$data['custom_36']= multi_select($array[$i]['cur_prim_lang']);
	$data['custom_40']= multi_select($array[$i]['cur_mom_medhx']);
	$data['custom_47']= multi_select($array[$i]['cur_dad_medhx']);
	$data['custom_52']= multi_select($array[$i]['cur_medical_condition']);
	//$data['custom_57']= 
	$data['custom_61']= multi_select($array[$i]['cur_cancer_treat_type']);
	$data['custom_65']= multi_select($array[$i]['cur_neuro_dx_type']);
	$data['custom_100']= multi_select($array[$i]['cur_psychiatric_history']);

	$ch= curl_init();
	curl_setopt($ch, CURLOPT_URL, "http://192.168.137.14/wp-content/plugins/civicrm/civicrm/extern/rest.php?");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
	$result= curl_exec($ch);
	//After succesfully creating the contact, the civicrm server will send back an xml data set, in which we can extract the 
	//contact ID which we need to continously update the contact with more information
	$xml_data= simplexml_load_string($result);


	$contact_id= (int) $xml_data -> {'Result'} -> {'id'};
	curl_close($ch);

	//retreiving the state_province_ID from the xml data last we got before the for loop
	for ($y= 0; $y <= 59; $y++)
	{
		if($state_data -> {'Result'} -> {$y} -> {'value'} == $array[$i]['cur_state'])
			$state_ID = (int) $state_data -> {'Result'} -> {$y} -> {'id'};
	}


	//Sending another API POST request to update the address and contact information of the newly created contact
	$data= array(
		'api_key' => 'nLbAvy7i8CzPpQQDLPBR4ew8',
		'key' => '6af3aaa08ed7efaa1a8dcb543286043d',
		'entity' => 'address',
		'action' => 'create',
		'location_type_id' => 1,
		'contact_id' => $contact_id,
		'street_address' => $array[$i]['cur_street'],
		'street_unit' => $array[$i]['cur_apt_num'],
		'city' => $array[$i]['cur_city'],
		'country_id' => 1228,
		'state_province_id' => $state_ID,
		'postal_code' => $array[$i]['cur_zip'],
		'is_primary' => 1,
	);

	
	$ch= curl_init();
	curl_setopt($ch, CURLOPT_URL, "http://192.168.137.14/wp-content/plugins/civicrm/civicrm/extern/rest.php?");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
	$result= curl_exec($ch);
	$phone =$array[$i]['cur_phone'];

	//Send antoher API request to update the phone contact information of the newly created contact
	$data= array(
		'api_key' => 'nLbAvy7i8CzPpQQDLPBR4ew8',
		'key' => '6af3aaa08ed7efaa1a8dcb543286043d',
		'entity' => 'phone',
		'action' => 'create',
		'location_type_id' => 1,
		'phone_type_id' => 1,
		'contact_id' => $contact_id,
		'phone' => $phone,
		'is_primary' => 1,
		'debug' => 1,
	);

	curl_close($ch);
	$ch= curl_init();
	curl_setopt($ch, CURLOPT_URL, "http://192.168.137.14/wp-content/plugins/civicrm/civicrm/extern/rest.php?");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
	$result= curl_exec($ch);
	curl_close($ch);
	
	echo '.';
}

echo "\r\n";
?>
