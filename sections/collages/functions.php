<?php

function get_collage_sort($Season, $Tags)
{

   if(!$Season || !$Tags) return 0;	
	
   $Sort = 100 * $Season; // season selector
   
   $Sources = array('bluray','bdrip','webdl','webrip','dvdr','dvdrip','hdtv');   
   $Resolutions = array('2160p','1080p','720p','576p','480p');   
   
   foreach($Sources as $Search) {
     if (preg_match('/'.$Search.'/',$Tags))
         $Source =  $Search;
   }       
   
   foreach($Resolutions as $Search) {
     if (preg_match('/'.$Search.'/',$Tags))
         $Resolution =  $Search;   	
   }       

   switch($Source) {
      case 'bluray': $Sort += 0;  break;	
      case 'bdrip':  $Sort += 20; break;	
      case 'webdl':  $Sort += 25; break;	
      case 'webrip': $Sort += 45; break;	
      case 'dvdr':   $Sort += 65; break;	
      case 'dvdrip': $Sort += 70; break;	
      case 'hdtv':   $Sort += 80; break;	
   }

   switch($Resolution) {
      case '2160p':  $Sort += 0;  break;	
      case '1080p':  $Sort += 5;  break;	
      case '720p':   $Sort += 10; break;	
      case '576p':   $Sort += 15; break;	
      case '480p':   $Sort += 20; break;	
   }

   return $Sort;
}


