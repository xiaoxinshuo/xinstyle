<?php
return array(
	//'配置项'=>'配置值'
    //数据库配置
    'db_type'  => 'mysqli',
    'db_user'  => 'root',
    'db_pwd'   => '111111',
    //'db_host'  => '192.168.1.107',
    'db_host'  => '192.168.2.100',
    'db_port'  => '3306',
    'db_name'  => 'wsdb',
    'db_charset'=>'utf8',
    'DB_PREFIX' =>'ws_',
    //安全过滤
    'DEFAULT_FILTER'        => 'strip_tags,htmlspecialchars',
    'SESSION_AUTO_START' => true, //是否开启session
    'SHOW_PAGE_TRACE' =>false,
    'debug'=>true,


    'PUBLIC_RESOURCE_PATH'=>dirname(__FILE__).'/../../../Public/File/',
);