<?php

function ShowCubitsRate($Seeding, $SeedSize) {

   $CAP = BONUS_TORRENTS_CAP;
   
   if($Seeding > $CAP) $Seeding = $CAP;
   
   $Oldway = round((sqrt(($Seeding * 0.4) + 1.0) - 1.0) * 10); 

   $SeedSize = $SeedSize/1073741824; 
   $SeedTime = 0; 
   $Files = $Oldway; 
   $Formula = $Files + ( $SeedSize * ( 0.25 + ( 0.6 * log(1+$SeedTime) ) ) );

   return round($Formula,2);
}