<?php

function URL_exists($url){
   $headers=get_headers($url);
   return stripos($headers[0],"200 OK")?true:false;
}

function upload_to_people_imagehost($ImageURL, $Debug=false) {

   global $SiteOptions;

   $parts = parse_url($ImageURL); // get image filename
   $filename = basename($parts['path']); 

                                  // create imagehost filename
   //$filename = $SiteOptions['ImagehostURL'] . "/images/" . $filename;    
   $filename = "https://people.nebulance.io/images/" . $filename; 

   if(URL_exists($filename))      // check if exists
      return $filename;
	
   if(!empty($SiteOptions['ImagehostURL']) && !empty($SiteOptions['ImagehostKey'])){
        //$url = $SiteOptions['ImagehostURL'].'/api/1/upload';
        $url = 'https://people.nebulance.io/api/1/upload';
        //$post_data = array('key'    => $SiteOptions['ImagehostKey'],
        $post_data = array('key'    => 'ff2c783983bdf8f22b91f48d359e7eb8',
                           'source' => $ImageURL,
                           'format' => 'json');

        $post_fields='';
        foreach($post_data as $key=>$value) { $post_fields .= $key.'='.$value.'&'; }
        rtrim($post_fields, '&');

	//open connection
	$ch = curl_init();

	//set the url, number of POST vars, POST data
	curl_setopt($ch,CURLOPT_URL, $url);
	curl_setopt($ch,CURLOPT_POST, count($post_data));
	curl_setopt($ch,CURLOPT_POSTFIELDS, $post_fields);
   curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch,CURLOPT_AUTOREFERER, true);
	curl_setopt($ch,CURLOPT_FOLLOWLOCATION, 1);

	//execute post
	$chev_image = json_decode(curl_exec($ch));

	//close connection
	curl_close($ch);

        if($Debug) {
            header('Content-Type: application/json');
            echo json_encode($chev_image);
            die();
        }

        return $chev_image->image->url;
   }
   return;
}
