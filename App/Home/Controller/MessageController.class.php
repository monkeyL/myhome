<?php

namespace Home\Controller;

use Home\Controller\IsloginController;

class MessageController extends IsloginController {

    public function index() {
        $owner = M("baidu_push");
        if (IS_POST) {
            $address = I('post.address');

            if ($address)
                $where['area'] = array('LIKE', '%' . $address . '%');
        }
        $count = $owner->where($where)
                ->count();
        $page = initPage($count, $_COOKIE['n'] ? $_COOKIE['n'] : 30);
        $show = $page->show();
     //   print_r($show);exit;
        $currentPage = empty($_GET['p']) ? 1 : intval($_GET['p']);
        $type = M("type");
        $typeList = $type->select();
        $data = $owner//->field('id,name,mobile_phone,fax_mobile,user_name,address,star,lock,list_pic')
                ->where($where)
                ->limit($page->firstRow . ',' . $page->listRows)
                ->select();
        foreach ($data as $k => $v) {
            $len = strlen($v['content']);
            $v['content'] = mb_strcut($v['content'], 0,15);
            if ($len >15) {
                $v['content'] .='...';
            }
            $len = strlen($v['title']);
            $v['title'] = mb_strcut($v['title'], 0,15);
            if ($len >15) {
                $v['title'] .='...';
            }
            $data[$k] = $v;
        }
        $this->assign('type', $typeList);
        $this->assign("currentPage", $currentPage);
        $this->assign("totalPage", $page->totalPages);
        $this->assign("page", $show);
        $this->assign('data', $data);
        $this->display();
    }
    public function send() {
        if (IS_POST) {
            // dump($_POST);die();
            // dump($_POST);
            $city = I('post.city');
            $extent = I('post.extent',0,'intval');
           
            $push['title'] = I('post.title');
            $push['content'] = I('post.content');
            $push['type'] = I('post.type');
            $push['area'] = '';
            //$push['area'] = $this->findArea($push['city']);
            // dump($push);die();
            // $this->push_log($push);die();
            // dump($push);die();
            if ($extent == 1) {
               $city[] = 'Q0';
            }
            foreach ($city as $key => $value) {
                $push['city'] =$value;
                $push['area'][] .= $this->findArea($push['city']);

                if ($push['type'] == 0) {//全部
                
                    $push['machine'] = "android,ios";
                    $bool =$this->test_pushMessage_android($push);                
                    $bool =$this->test_pushMessage_ios($push);
                    
                }elseif($push['type'] == 1){//android
                    $push['machine'] = "android";
                    $bool =$this->test_pushMessage_android($push);
                    
                }elseif($push['type'] == 2){//ios
                    $push['machine'] = "ios";
                    $bool =$this->test_pushMessage_ios($push);
                    
                }
            }
            // dump($push);die();
            // dump($bool);die();
            if ($bool['statue'] == '成功') {
               admin_log('发布推送给'.$push['machine'].'用户');
                $bool = $this->push_log($bool);
                $this->success('消息成功推送给用户',U('index'));
            }else{
                $this->error('发送推送失败');
            }
        // $bool =$this->test_pushMessage_ios($uid);//推送苹果
        }
        
        $extent= array('所有人','按照省份','按照城市','按照地区','按照物业','按照小区');
       
        $this->assign('extent',$extent);
        $data['title'] = "发送推送消息";
        $data['btn'] = "发送推送消息";
        $this->assign('data',$data);
        $this->display();
    }



function error_output ( $str ) 
{
    echo "\033[1;40;31m" . $str ."\033[0m" . "\n";
}

function right_output ( $str ) 
{
    echo "\033[1;40;32m" . $str ."\033[0m" . "\n";
}






//推送android设备按照tag推送
function test_pushMessage_android ($push)
{
    
    global $apiKey;
    global $secretKey;
    $apiKey = C('android_key');
    $secretKey = C('android_secret');
    import("Org.baidu.Channel");//global $channel;
    $channel = new \Channel ($apiKey, $secretKey) ;
    
   /* //推送单播消息，必须指定user_id或者user_id+channel_id
    $push_type = 1;
    $user_id = ‘xxx’;
    $channel_id = ‘xxx’;
    $optional[Channel::USER_ID] = $user_id;
    $optional[Channel::CHANNEL_ID] = $channel_id;
    $message = ‘Hello World’;
    $message_key = ‘msg_key’;
    $ret = $channel->pushMessage ( $push_type, $message, $message_key, $optional );
   */ 
    $push_type = 2;
    //推送通知，必须指定MESSAGE_TYPE为1
    $optional['message_type'] = 1;
    //dump($optional);die();
    //通知必须按以下格式指定
    $message = '{ 
    "title": "'.$push["title"].'",
    "description": "'.$push["content"].'"
    }';
    $message_key = "msg_keys";
    // $ret = $channel->pushMessage ( $push_type, $message, $message_key, $optional );

    //推送消息到一群人，按tag推送,必须指定tag_name
    // $message_type = 1;
    //$push_type = 2;
    $tag_name = $push['city'];
    // dump($Channel->TAG_NAME);
    // $Channel->TAG_NAME = $tag_name;
    $optional['tag'] = $tag_name;
    // dump($optional);
    $ret = $channel->pushMessage($push_type, $message, $message_key, $optional);

   /* //推送消息到某个应用下的所有人，不用指定user_id, channel_id, tag_name
    $push_type = 3;
    $ret = $channel->pushMessage($push_type, $messages, $message_keys);
  */  
    //检查返回值

    if ( false === $ret )
    {
        $push['statue'] = "失败";
        //echo "<script>alert('信息发送失败')</script>";
        // echo ( 'WRONG, ' . __FUNCTION__ . ' ERROR!!!!\n' );
        // echo ( 'ERROR NUMBER: ' . $channel->errno ( ) . '\n' );
        // echo ( 'ERROR MESSAGE: ' . $channel->errmsg ( ) . '\n' );
        // echo ( 'REQUEST ID: ' . $channel->getRequestId ( ) . '\n' );
    }
    else
    {
        // echo ( 'SUCC, ' . __FUNCTION__ . ' OK!!!!!'. '\n' );
        // echo ( 'result: ' . print_r ( $ret, true ) . '\n' );
        $push['statue'] = "成功";
        //echo "<script>alert('信息发送成功')</script>";
       
        
    }
    //dump($push);
    return $push;
    
}

//推送ios设备消息
function test_pushMessage_ios ($push)
{
    global $apiKey;
    global $secretKey;
    $apiKey = C('ios_key');
    $secretKey = C('ios_secret');
    import("Org.baidu.Channel");
    $channel = new \Channel ( $apiKey, $secretKey ) ;

    /*$push_type = 3; //推送单播消息

    $optional[$channel->USER_ID] = $user_id; //如果推送单播消息，需要指定user
*/  $push_type = 2;
    //指定发到ios设备
    $optional[device_type] = 4;
    //指定消息类型为通知
    $optional[message_type] = 1;
    //如果ios应用当前部署状态为开发状态，指定DEPLOY_STATUS为1，默认是生产状态，值为2.
    //旧版本曾采用不同的域名区分部署状态，仍然支持。
    $optional[deploy_status] = 1;
    //通知类型的内容必须按指定内容发送，示例如下：
    // dump($push);die();
    
    $message = '{ 
        "aps":{
            "alert":"'.$push[content].'",
            "sound":"",
            "badge":0
        }
    }';
    $tag_name = $push['city'];
    // dump($tag_name);die;
    // $tag_name = 'meihong';
    // dump($Channel->TAG_NAME);
    // $Channel->TAG_NAME = $tag_name;
    $optional['tag'] = $tag_name;
    
    $message_key = "msg_key";
    $ret = $channel->pushMessage ( $push_type, $message, $message_key, $optional ) ;
    if ( false === $ret )
    {
        $push['statue'] = "失败";
        // echo "<script>alert('信息发送失败')</script>";
        // $this->error_output ( 'WRONG, ' . __FUNCTION__ . ' ERROR!!!!!' ) ;
        // $this->error_output ( 'ERROR NUMBER: ' . $channel->errno ( ) ) ;
        // $this->error_output ( 'ERROR MESSAGE: ' . $channel->errmsg ( ) ) ;
        // $this->error_output ( 'REQUEST ID: ' . $channel->getRequestId ( ) );
    }
    else
    {
        $push['statue'] = "成功";
        // echo "<script>alert('信息发送成功')</script>";
        // $this->right_output ( 'SUCC, ' . __FUNCTION__ . ' OK!!!!!' ) ;
        // $this->right_output ( 'result: ' . print_r ( $ret, true ) ) ;
    }
    // dump($push);die();
    return $push;
}


function test_initAppIoscert ( $name, $description, $release_cert, $dev_cert )
{
    global $apiKey;
    global $secretKey;
    import("Org.baidu.Channel");
    $channel = new \Channel ($apiKey, $secretKey) ;
    //如果ios应用当前部署状态为开发状态，指定DEPLOY_STATUS为1，默认是生产状态，值为2.
    //旧版本曾采用不同的域名区分部署状态，仍然支持。
    //$optional[Channel::DEPLOY_STATUS] = 1;
    
    $ret = $channel->initAppIoscert ($name, $description, $release_cert, $dev_cert) ;
    if ( false === $ret )
    {
        $this->error_output ( 'WRONG, ' . __FUNCTION__ . ' ERROR!!!!' ) ;
        $this->error_output ( 'ERROR NUMBER: ' . $channel->errno ( ) ) ;
        $this->error_output ( 'ERROR MESSAGE: ' . $channel->errmsg ( ) ) ;
        $this->error_output ( 'REQUEST ID: ' . $channel->getRequestId ( ) );
    }
    else
    {
        $this->right_output ( 'SUCC, ' . __FUNCTION__ . ' OK!!!!!' ) ;
        $this->right_output ( 'result: ' . print_r ( $ret, true ) ) ;
    }
}

function test_updateAppIoscert ( $name, $description, $release_cert, $dev_cert )
{
    global $apiKey;
    global $secretKey;
    import("Org.baidu.Channel");
    $channel = new \Channel ($apiKey, $secretKey) ;
    //如果ios应用当前部署状态为开发状态，指定DEPLOY_STATUS为1，默认是生产状态，值为2.
    //旧版本曾采用不同的域名区分部署状态，仍然支持。
    //$optional[Channel::DEPLOY_STATUS] = 1;

    $optional[ Channel::NAME ] = $name;
    $optional[ Channel::DESCRIPTION ] = $description;
    $optional[ Channel::RELEASE_CERT ] = $release_cert;
    $optional[ Channel::DEV_CERT ] = $dev_cert;
    $ret = $channel->updateAppIoscert ($optional) ;
    if ( false === $ret )
    {
        $this->error_output ( 'WRONG, ' . __FUNCTION__ . ' ERROR!!!!' ) ;
        $this->error_output ( 'ERROR NUMBER: ' . $channel->errno ( ) ) ;
        $this->error_output ( 'ERROR MESSAGE: ' . $channel->errmsg ( ) ) ;
        $this->error_output ( 'REQUEST ID: ' . $channel->getRequestId ( ) );
    }
    else
    {
        $this->right_output ( 'SUCC, ' . __FUNCTION__ . ' OK!!!!!' ) ;
        $this->right_output ( 'result: ' . print_r ( $ret, true ) ) ;
    }
}

/*function test_queryAppIoscert ( )
{
    global $apiKey;
    global $secretKey;
    $channel = new Channel ($apiKey, $secretKey) ;
    //如果ios应用当前部署状态为开发状态，指定DEPLOY_STATUS为1，默认是生产状态，值为2.
    //旧版本曾采用不同的域名区分部署状态，仍然支持。
    //$optional[Channel::DEPLOY_STATUS] = 1;

    $ret = $channel->queryAppIoscert () ;
    if ( false === $ret )
    {
        $this->error_output ( 'WRONG, ' . __FUNCTION__ . ' ERROR!!!!' ) ;
        $this->error_output ( 'ERROR NUMBER: ' . $channel->errno ( ) ) ;
        $this->error_output ( 'ERROR MESSAGE: ' . $channel->errmsg ( ) ) ;
        $this->error_output ( 'REQUEST ID: ' . $channel->getRequestId ( ) );
    }
    else
    {
        $this->right_output ( 'SUCC, ' . __FUNCTION__ . ' OK!!!!!' ) ;
        $this->right_output ( 'result: ' . print_r ( $ret, true ) ) ;
    }
}
*/
/*function test_deleteAppIoscert ( )
{
    global $apiKey;
    global $secretKey;
    $channel = new Channel ($apiKey, $secretKey) ;
    //如果ios应用当前部署状态为开发状态，指定DEPLOY_STATUS为1，默认是生产状态，值为2.
    //旧版本曾采用不同的域名区分部署状态，仍然支持。
    //$optional[Channel::DEPLOY_STATUS] = 1;

    $ret = $channel->deleteAppIoscert () ;
    if ( false === $ret )
    {
        $this->error_output ( 'WRONG, ' . __FUNCTION__ . ' ERROR!!!!' ) ;
        $this->error_output ( 'ERROR NUMBER: ' . $channel->errno ( ) ) ;
        $this->error_output ( 'ERROR MESSAGE: ' . $channel->errmsg ( ) ) ;
        $this->error_output ( 'REQUEST ID: ' . $channel->getRequestId ( ) );
    }
    else
    {
        $this->right_output ( 'SUCC, ' . __FUNCTION__ . ' OK!!!!!' ) ;
        $this->right_output ( 'result: ' . print_r ( $ret, true ) ) ;
    }
}*/
// push标题的数据记录
function push_log($push){
    // dump($push);    
    
    $data = array(
        'name'=>session('admin.name'),
        'time'=>time(),
        'title'=>$push['title'],
        'content'=>$push['content'],
        'statue'=>$push['statue'],
        'machine'=>$push['machine'],
        'area'=>implode(',', $push['area']),
        );
    // dump($data);
    $bool = M('baidu_push')->add($data);
    // dump(M('baidu_push')->getlastSql());

}
/**
     * 异步删除用户的申请即不同意用户的申请
     * @author xujun
     * @email  [jun0421@163.com]
     * @time   2015-01-15T15:45:58+0800
     * @return [type]                   [description]
     */
    public function ajax_del() {
        // echo 1;exit;
        $id = I('request.id', 0,'intval');
        $model = D('baidu_push');
        $bool = $model->delete($id);
        if ($bool) {
            $out['statue'] = 1;
            $out['msg'] = "操作成功";
        }else{
            $out['statue'] = 0;
            $out['msg'] = '操作失败';
        }
        $this->ajaxReturn($out);
    }
    private function select($num,$arr){
        //dump($num);
        switch ($num) {
            case '1':
                return 'Q0';
                break;
            case '2':
                return $arr[0];
                break;
            case '3':
                return $arr[1];
                break;
            case '4':
                return $arr[2];
                break;
            case '5':
                return $arr[3];
                break;
            case '6':
                return $arr[4];
                break;
        }

    }
    private function findArea($push){
        $start = substr($push, 0,1);
        $num = substr($push, 1);

        switch ($start) {
            case 'Q':
                return "全国";
                break;
            case 'P':
                $field = 'REGION_NAME';
                $table = "region";
                $w = array('REGION_ID'=>$num);
                break;
            case 'C':
                $field = 'REGION_NAME';
                $table = "region";
                $w = array('REGION_ID'=>$num);
                break;
            case 'A':
                $field = 'REGION_NAME';
                $table = "region";
                $w = array('REGION_ID'=>$num);
                break;
            case 'W':
                $field = 'pname';
                $table = "property";
                $w = array('id'=>$num);
                break;
            case 'V':
                $field = 'name';
                $table = "village";
                $w = array('id'=>$num);
                break;                      
        } 
        $data =  M($table)->field($field)->where($w)->find();
        // dump($data);die();
        return current($data);   
    }
public function url_ajax_choce(){
	$id = I('post.id');

	switch ($id) {        
        case '2'://按照省份发布推送
            $gid = M('user')->distinct(true)->field('province')->where('province != 0')->select();
            
            if (is_array($gid)) {
                $str = '';
                foreach ($gid as $key => $value) {
                    // dump($value);die();
                    if ($str == '') {
                        $str .= $value["province"];
                    }else{
                        $str .= ','.$value["province"];
                    }
                    
                }
            }
            $arr = M('region')->field('REGION_NAME as name,REGION_ID as id')->where('REGION_ID in ( '.$str." )")->select();
            $out= $arr;            
            break;
        case '3'://按照城市发布推送
            $gid = M('user')->distinct(true)->field('city')->where('city != 0')->select();
            
            if (is_array($gid)) {
                $str = '';
                foreach ($gid as $key => $value) {
                    // dump($value);die();
                    if ($str == '') {
                        $str .= $value["city"];
                    }else{
                        $str .= ','.$value["city"];
                    }
                    
                }
            }
            $arr = M('region')->field('REGION_NAME as name,REGION_ID as id')->where('REGION_ID in ( '.$str." )")->select();
            $out = $arr;
            
            break;
        case '4'://按照地区发布推送
            $gid = M('user')->distinct(true)->field('area')->where('province != 0')->select();
            
            if (is_array($gid)) {
                $str = '';
                foreach ($gid as $key => $value) {
                    // dump($value);die();
                    if ($str == '') {
                        $str .= $value["area"];
                    }else{
                        $str .= ','.$value["area"];
                    }
                    
                }
            }
            $arr = M('region')->field('REGION_NAME as name,REGION_ID as id')->where('REGION_ID in ( '.$str." )")->select();
            $out = $arr;; 
            break;
        case '5'://按照物业发布推送
             $gid = M('user')->distinct(true)->field('property_id')->where('property_id != 0')->select();
            
            if (is_array($gid)) {
                $str = '';
                foreach ($gid as $key => $value) {
                    // dump($value);die();
                    if ($str == '') {
                        $str .= $value["property_id"];
                    }else{
                        $str .= ','.$value["property_id"];
                    }
                    
                }
            }
            $out = M('property')->field('id,pname as name')->where('id in ('.$str.')')->select();
            break;
        case '6'://按照小区发布推送
            $gid = M('user')->distinct(true)->field('village_id')->where('village_id != 0')->select();
            // dump($gid);
            if (is_array($gid)) {
                $str = '';
                foreach ($gid as $key => $value) {
                    // dump($value);die();
                    if ($str == '') {
                        $str .= $value["village_id"];
                    }else{
                        $str .= ','.$value["village_id"];
                    }
                    
                }
            }
            $out = M('village')->field('id,name')->where('id in ('.$str.')')->select();
            // dump(M('property')->getlastSql());
            // dump($out);
            break;
        default:
            $out = 0;
            break;            
    }
    $this->ajaxReturn($out);
	
}
}
