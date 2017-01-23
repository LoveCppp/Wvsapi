<?php

header("Content-type: application/json; charset=utf-8");


date_default_timezone_set('prc');
error_reporting(E_ALL & ~E_NOTICE);
//配置引擎
$agent_host = '';
$agent_port = '8183';
//定义通信密钥
$username = 'admin';
$password = 'admin';
//生成token
$token = md5(md5(md5($username).md5($password)).$password);

//扫描设置
$scanType = 'scan';

$targetList = $_POST['targetList'];
$target = array($_POST['target']);
$recurse = '-1';
$date = date("m/d/Y");
$dayOfWeek = '1';
$dayOfMonth = '1';
$time = date('H:i', strtotime($time.'next minute'));
$checkbox = $_POST["checkbox"];
$profile = $_POST['profile'];
$loginSeq = $_POST['loginSeq'];
$settings = $_POST['settings'];
$scanningmode = $_POST['scanningmode'];
$excludedhours = $_POST['excludedhours'];
$reportformat = $_POST['reportformat'];
$reporttemplate = $_POST['reporttemplate'];
$emailaddress = $_POST['emailaddress'];
$deleteAfterCompletion = 'FALSE';
$savetodatabase = 'True';
$savelogs = 'FALSE';
$generatereport = 'FALSE';



//初始化
$curl = curl_init();
//设置抓取的url
curl_setopt($curl, CURLOPT_URL, 'https://'."$agent_host".':'."$agent_port".'/api/addScan');
//自定义http header
curl_setopt($curl, CURLOPT_HTTPHEADER,array('Accept: application/json, text/javascript, */*; q=0.01','Content-Type: application/json','RequestValidated: true','X-Requested-With: XMLHttpRequest','Referer: https://'."$agent_host".':'."$agent_port",'Accept-Language: zh-CN','Accept-Encoding: gzip, deflate','User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko','Host: '."$agent_host".':'."$agent_port",'DNT: 1','Connection: Keep-Alive','Cache-Control: no-cache','Cookie: wvs_auth= '."$token"));
//定义超时
curl_setopt ($curl, CURLOPT_TIMEOUT, 20 );
//设置头文件的信息作为数据流输出
//curl_setopt($curl, CURLOPT_HEADER, 1);
//关闭SSL证书验证
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
//设置获取的信息以文件流的形式返回，而不是直接输出。
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
//设置post方式提交
curl_setopt($curl, CURLOPT_POST,1);
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($curl, CURLOPT_HEADER, 0);


//设置post数据

//原始数据
$params = array("profile" => "$profile","loginSeq" => "$loginSeq","settings" => "$settings","scanningmode" => "$scanningmode","excludedhours" => "$excludedhours","savetodatabase" => "$savetodatabase","savelogs" => "$savelogs","generatereport" => "$generatereport","reportformat" => "$reportformat","reporttemplate" => "$reporttemplate","emailaddress" => "$emailaddress");
$data_string = array("scanType" => $scanType,"targetList" => $targetList,"target" => $target,"recurse" => "$recurse","date" => "$date","dayOfWeek" => "$dayOfWeek","dayOfMonth" => "$dayOfMonth","time" => "$time","deleteAfterCompletion" => $deleteAfterCompletion,"params" => $params
);


//以json方式提交

$post_data = json_encode($data_string, JSON_UNESCAPED_SLASHES);
curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);

//执行命令
$data = curl_exec($curl);


curl_close($curl);
//显示获得的数据

$response_data = json_decode($data,TRUE);

//判断返回状态，result ok，返回的scanid不为空
if($response_data['result'] == 'OK'){
	echo '添加成功';
}else{
	echo '添加失败';
}

	



?>
