<?php
/* *
 * 功能：支付宝服务器异步通知页面
 * 版本：3.3
 * 日期：2012-07-23
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。


 *************************页面功能说明*************************
 * 创建该页面文件时，请留心该页面文件中无任何HTML代码及空格。
 * 该页面不能在本机电脑测试，请到服务器上做测试。请确保外部可以访问该页面。
 * 该页面调试工具请使用写文本函数logResult，该函数已被默认关闭，见alipay_notify_class.php中的函数verifyNotify
 * 如果没有收到该页面返回的 success 信息，支付宝会在24小时内按一定的时间策略重发通知
 */
// var_dump('<pre>');
// var_dump($_SERVER);
// var_dump('</pre>');

$path = dirname($_SERVER['SCRIPT_FILENAME']).'/alipay_log.php';
// $str = file_get_contents($path);
// var_dump($str);
// $str = '';
$str = '<br/>'.date('Y-m-d H:i:s',time());
// var_dump($path);
// var_dump(is_dir($path));

require_once("alipay.config.php");
require_once("lib/alipay_notify.class.php");

//计算得出通知验证结果
$alipayNotify = new AlipayNotify($alipay_config);
$verify_result = $alipayNotify->verifyNotify();

if($verify_result) {//验证成功
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//请在这里加上商户的业务逻辑程序代
	
	
	//——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
	
    //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
	
	//商户订单号

	$out_trade_no = $_POST['out_trade_no'];
	//支付宝交易号

	$trade_no = $_POST['trade_no'];
	//交易状态
	$trade_status = $_POST['trade_status'];
	// die();
    if($_POST['trade_status'] == 'TRADE_FINISHED') {
		//判断该笔订单是否在商户网站中已经做过处理
			//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
			//如果有做过处理，不执行商户的业务程序
				
		//注意：
		//该种交易状态只在两种情况下出现
		//1、开通了普通即时到账，买家付款成功后。
		//2、开通了高级即时到账，从该笔交易成功时间算起，过了签约时的可退款时限（如：三个月以内可退款、一年以内可退款等）后。

        //调试用，写文本函数记录程序运行情况是否正常
        // logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
    }elseif ($_POST['trade_status'] == 'TRADE_SUCCESS') {
		$connect = mysql_connect('127.0.0.1','root','5ae5ee52b8')or die('连接失败');//5ae5ee52b8
		//$connect = mysql_connect('127.0.0.1','root','root')or die('连接失败');//5ae5ee52b8
			
		mysql_select_db('wrtdata')or die('数据库选择失败！');

		mysql_query('START TRANSACTION');//开始事务
		// 更新订单
		$sql = "update wrt_order set statue = 1,alipay='".$trade_no."' where number=".$out_trade_no;
		$bool1 = mysql_query($sql);
		// var_dump($bool1);
		// 查找用户订单的信息
		$sql = 'select * from wrt_order where number='.$out_trade_no;
		$result = mysql_query($sql);
		// var_dump($result);
		$data = mysql_fetch_assoc($result);
		// var_dump($data['type']);
		// var_dump($data['type'] == 1);
		if ($data['type'] == 1) {//用户充值
			if ($data['check_statue'] ==0) {
				$sql = 'update wrt_user set user_money = user_money+'.$data['totle'].' where user_id='.$data['user_id'];
			// var_dump($sql);
			
				$bool2 = mysql_query($sql);			
				var_dump($bool2);
				if ($bool2) {
				$sql = "update wrt_recharge set statue = 1,pay=".$trade_no." where number = ".$out_trade_no;
				$bool1 = mysql_query($sql);
				$sql = "update wrt_order set check_statue = 1 where alipay='".$trade_no."' and number = '".$out_trade_no."'";
				var_dump($sql);
				$bool0 = mysql_query($sql);
				}
				// var_dump($bool2);
				if ($bool1 && $bool0) {
					
					$result = true;
				}
			}
		}else{
				$url = "http://master.huishare.com/api.php?s=Recharge/payfor&num=".$out_trade_no;
				//$url = "http://127.0.0.1/wrt/api.php?s=Recharge/payfor&num=".$out_trade_no;
				var_dump($url);
				$ch = curl_init();
			    curl_setopt($ch, CURLOPT_URL, $url); 
			    
			    
			    curl_setopt($ch, CURLOPT_POST, TRUE); 
			     	    
			    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); // 在启用时，返回原生的（Raw）输出 
			    // curl_setopt($ch, CURLOPT_NOPROGRESS, TRUE);
			    
			    $buffer = curl_exec($ch);
			    curl_close($ch);
			    $result = $buffer;
			    var_dump($result);
				}
				if ($result) {		
					mysql_query('COMMIT');//提交事务
				}else{
					mysql_query(' ROLLBACK ');//回滚事务
				}
				// var_dump($sql);die();
				
				// echo $result;
			    //验证失败
			   	mysql_close($connect);
			    
			    file_put_contents($path, $str);
	    
		//——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
	        
		echo "success";		//请不要修改或删除
	}elseif ($_POST['trade_status'] == 'WAIT_BUYER_PAY') {
		$connect = mysql_connect('127.0.0.1','root','5ae5ee52b8')or die('连接失败');//5ae5ee52b8
		//$connect = mysql_connect('127.0.0.1','root','root')or die('连接失败');//5ae5ee52b8
			
		mysql_select_db('wrtdata')or die('数据库选择失败！');

		mysql_query('START TRANSACTION');//开始事务
		// 更新订单
		$sql = "update wrt_order set statue = 1,alipay='".$trade_no."' where number=".$out_trade_no;
		$bool1 = mysql_query($sql);
		// var_dump($bool1);
		// 查找用户订单的信息
		$sql = 'select * from wrt_order where number='.$out_trade_no;
		$result = mysql_query($sql);
		// var_dump($result);
		$data = mysql_fetch_assoc($result);
		// var_dump($data['type']);
		// var_dump($data['type'] == 1);
		if ($data['type'] == 1) {//用户充值
			if ($data['check_statue'] ==0) {
				$sql = 'update wrt_user set user_money = user_money+'.$data['totle'].' where user_id='.$data['user_id'];
			// var_dump($sql);
			
				$bool2 = mysql_query($sql);			
				var_dump($bool2);
				if ($bool2) {
				$sql = "update wrt_recharge set statue = 1,pay=".$trade_no." where number = ".$out_trade_no;
				$bool1 = mysql_query($sql);
				$sql = "update wrt_order set check_statue = 1 where alipay='".$trade_no."' and number = '".$out_trade_no."'";
				var_dump($sql);
				$bool0 = mysql_query($sql);
				}
				// var_dump($bool2);
				if ($bool1 && $bool0) {
					
					$result = true;
				}
			}
		}else{
				$url = "http://master.huishare.com/api.php?s=Recharge/payfor&num=".$out_trade_no;
				//$url = "http://127.0.0.1/wrt/api.php?s=Recharge/payfor&num=".$out_trade_no;
				var_dump($url);
				$ch = curl_init();
			    curl_setopt($ch, CURLOPT_URL, $url); 
			    
			    
			    curl_setopt($ch, CURLOPT_POST, TRUE); 
			     	    
			    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); // 在启用时，返回原生的（Raw）输出 
			    // curl_setopt($ch, CURLOPT_NOPROGRESS, TRUE);
			    
			    $buffer = curl_exec($ch);
			    curl_close($ch);
			    $result = $buffer;
			    var_dump($result);
				}
				if ($result) {		
					mysql_query('COMMIT');//提交事务
				}else{
					mysql_query(' ROLLBACK ');//回滚事务
				}
				// var_dump($sql);die();
				
				// echo $result;
			    //验证失败
			   	mysql_close($connect);
			    
			    file_put_contents($path, $str);
	        
		echo "success";		//请不要修改或删除
		
		/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	}
		/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}else {

		// $trade_no ='111111111';
		// // $out_trade_no ='2015031897575256';
		// $connect = mysql_connect('127.0.0.1','root','5ae5ee52b8')or die('连接失败');//5ae5ee52b8
		// //$connect = mysql_connect('127.0.0.1','root','root')or die('连接失败');//5ae5ee52b8
			
		// mysql_select_db('wrtdata')or die('数据库选择失败！');

		// mysql_query('START TRANSACTION');//开始事务
		// // 更新订单
		// $sql = "update wrt_order set statue = 1,alipay='".$trade_no."' where number=".$out_trade_no;
		// $bool1 = mysql_query($sql);
		// // var_dump($bool1);
		// // 查找用户订单的信息
		// $sql = 'select * from wrt_order where number='.$out_trade_no;
		// $result = mysql_query($sql);
		// // var_dump($result);
		// $data = mysql_fetch_assoc($result);
		// // var_dump($data['type']);
		// // var_dump($data['type'] == 1);
		// if ($data['type'] == 1) {//用户充值
		// 	if ($data['check_statue'] ==0) {
		// 		$sql = 'update wrt_user set user_money = user_money+'.$data['totle'].' where user_id='.$data['user_id'];
		// 	// var_dump($sql);
			
		// 		$bool2 = mysql_query($sql);			
		// 		var_dump($bool2);
		// 		if ($bool2) {
		// 		$sql = "update wrt_recharge set statue = 1,pay=".$trade_no." where number = ".$out_trade_no;
		// 		$bool1 = mysql_query($sql);
		// 		$sql = "update wrt_order set check_statue = 1 where alipay='".$trade_no."' and number = '".$out_trade_no."'";
		// 		var_dump($sql);
		// 		$bool0 = mysql_query($sql);
		// 		}
		// 		// var_dump($bool2);
		// 		if ($bool1 && $bool0) {
					
		// 			$result = true;
		// 		}
		// 	}
		// }else{
		// 		//$url = "http://master.huishare.com/api.php?s=Recharge/payfor&num=".$out_trade_no;
		// 		$url = "http://127.0.0.1/wrt/api.php?s=Recharge/payfor&num=".$out_trade_no;
		// 		var_dump($url);
		// 		$ch = curl_init();
		// 	    curl_setopt($ch, CURLOPT_URL, $url); 
			    
			    
		// 	    curl_setopt($ch, CURLOPT_POST, TRUE); 
			     	    
		// 	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); // 在启用时，返回原生的（Raw）输出 
		// 	    // curl_setopt($ch, CURLOPT_NOPROGRESS, TRUE);
			    
		// 	    $buffer = curl_exec($ch);
		// 	    curl_close($ch);
		// 	    $result = $buffer;
		// 	    var_dump($result);
		// 		}
		// 		if ($result) {		
		// 			mysql_query('COMMIT');//提交事务
		// 		}else{
		// 			mysql_query(' ROLLBACK ');//回滚事务
		// 		}
		// 		// var_dump($sql);die();
				
		// 		// echo $result;
		// 	    //验证失败
		// 	   	mysql_close($connect);
			    
		// 	    file_put_contents($path, $str);

			        
		// echo "success";		//请不要修改或删除
		//——请根据您的业务逻辑来编写程序（以上代码仅作参考）——

		$str .= '付款失败';
		file_put_contents($path, $str);
		
	    echo "fail";

	    //调试用，写文本函数记录程序运行情况是否正常
	    //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
	}