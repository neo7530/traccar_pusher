<?php
//for debugging json-part uncomment next 2 lines
//file_put_contents("data.txt",file_get_contents('php://input'),FILE_APPEND);
//file_put_contents("data.txt","\n\n\n",FILE_APPEND);

//link to traccar-server
$serverurl = 'https://traccar.server.org:9443/traccar/';
$json = file_get_contents('php://input');
$data = json_decode($json);

$usepush = $data->device->attributes->push;

if($usepush =='1'){

	$eventtype = $data->event->type;
	$devicename = $data->device->name;
	$devicestatus = $data->device->status;
	$battery = $data->position->attributes->batteryLevel;
//	$latitude = $data->position->latitude;
//	$longitude = $data->position->longitude;
//	$address = $data->position->address;
//	$speed = $data->position->speed;
//	$course = $data->position->course;

foreach($data->users as $i=>$a){

	$username = $data->users[$i]->name;
	$pushonline = $data->users[$i]->attributes->pushonline;
	$pushalarm = $data->users[$i]->attributes->pushalarm;
	$pushunknown = $data->users[$i]->attributes->pushunknown;
	$pushoffline = $data->users[$i]->attributes->pushoffline;
	$pushmoving = $data->users[$i]->attributes->pushmoving;
	$webtoken = $data->users[$i]->token;

	if($eventtype == 'deviceOnline' and $pushonline == '1'){
		$priority = -1;
		$gotpriority = 1;
		$headline = 'Info.';
		$text = 'Hallo '.$username.'! '.$devicename.' is '.$devicestatus.'. ';
	};

	if($eventtype == 'deviceOffline' and $pushoffline == '1'){
		$priority = -1;
		$gotpriority = 1;
		$headline = 'Info.';
		$text = 'Hallo '.$username.'! '.$devicename.' is '.$devicestatus.'. ';
	};

	if($eventtype == 'deviceUnknown' and $pushunknown == '1'){
		$priority = -1;
		$gotpriority = 1;
		$headline = 'Info.';
		$text = 'Hallo '.$username.'! '.$devicename.' is '.$devicestatus.'. ';
	};

	if($eventtype == 'alarm' and $pushalarm == '1'){
		$headline = 'Alarm !!!';
		$priority = 1;
		$gotpriority = 10;
		$alarmtype = $data->event->attributes->alarm;
		$address = $data->position->address;
		$text = 'Hallo '.$username.'! '.$devicename.' hat einen Alarm: '.$alarmtype.'! ';
	};

	if($eventtype == 'geofenceEnter' and $pushalarm == '1'){
		$priority = 0;
		$gotpriority = 5;
		$headline = 'Geozaun betreten.';
		$alarmtype = $data->event->attributes->alarm;
		$address = $data->position->address;
		$geofencename = $data->geofence->name;
		$text = 'Hallo '.$username.'! '.$devicename.' hat den Geozaun: '.$geofencename.' betreten! ';
		$text = $text.' Adresse: '.$address.'. ';
	};

	if($eventtype == 'geofenceExit' and $pushalarm == '1'){
		$priority = 0;
		$gotpriority = 5;
		$headline = 'Geozaun verlassen.';
		$alarmtype = $data->event->attributes->alarm;
		$address = $data->position->address;
		$geofencename = $data->geofence->name;
		$text = 'Hallo '.$username.'! '.$devicename.' hat den Geozaun: '.$geofencename.' verlassen! ';
		$text = $text.' Adresse: '.$address.'. ';
	};

	if($eventtype == 'deviceMoving' and $pushmoving == '1'){
		$priority = 0;
		$gotpriority = 5;
		$headline = $devicename.' ist in Bewegung.';
		$alarmtype = $data->event->attributes->alarm;
		$address = $data->position->address;
		$text = 'Hallo '.$username.'! '.$devicename.' ist in Bewegung! ';
		$text = $text.' Adresse: '.$address.'. ';
	};

	$text = $text.'Quicklink: '.$serverurl.'?token='.$webtoken.' ';

	if($battery != ''){
		$text = $text.'Battery: '.$battery.'% ';
	};

	$usegotify = $data->users[$i]->attributes->gotify;
	$usepushover = $data->users[$i]->attributes->pushover;

	if($usegotify == '1' ){
		$gotifyurl = $data->users[$i]->attributes->pushurl;
		$gotifyapikey = $data->users[$i]->attributes->GotifyApiKey;

                $url = $gotifyurl.'message?token='.$gotifyapikey;

                curl_setopt_array($ch = curl_init(), array(
                        CURLOPT_URL => $url,

                        CURLOPT_POSTFIELDS => array(
                        "message" => $text,
                        "priority" => $gotpriority,
                        "title" => $headline,
                        ),

                        CURLOPT_SAFE_UPLOAD => true,
                        CURLOPT_RETURNTRANSFER => true,                                                                                                                                                                                                              )
                );
        	curl_exec($ch);
        	curl_close($ch);


	};

	if($usepushover == '1' ){
		$pushoverapptoken = $data->users[$i]->attributes->PushoverAppToken;
		$pushoveruserkey = $data->users[$i]->attributes->PushoverUserKey;

		curl_setopt_array($ch = curl_init(), array(
			CURLOPT_URL => "https://api.pushover.net/1/messages.json",

			CURLOPT_POSTFIELDS => array(
			"token" => $pushoverapptoken,
			"user" => $pushoveruserkey,
			"message" => $text,
			"priority" => $priority,
			"title" => $headline,
			),

			CURLOPT_SAFE_UPLOAD => true,
			CURLOPT_RETURNTRANSFER => true,
			)
		);
		curl_exec($ch);
		curl_close($ch);

	}; //if use pushover

}; //for each

}; //if use push

?>
