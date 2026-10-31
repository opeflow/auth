<?php
return ['name'=>env('APP_NAME','Operix Auth'),'env'=>env('APP_ENV','production'),'debug'=>(bool) env('APP_DEBUG',false),'url'=>env('APP_URL','http://localhost:8081'),'timezone'=>'UTC','locale'=>'pt_BR','fallback_locale'=>'en','faker_locale'=>'pt_BR','cipher'=>'AES-256-CBC','key'=>env('APP_KEY'),'previous_keys'=>[], 'maintenance'=>['driver'=>'file','store'=>'database']];
