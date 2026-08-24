<?php
declare(strict_types=1);function startPlatformSession():void{if(session_status()!==PHP_SESSION_NONE)return;session_set_cookie_params(['httponly'=>true,'secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off','samesite'=>'Strict','path'=>'/plataforma']);session_name('MINIERP_PLATFORM');session_start();}
