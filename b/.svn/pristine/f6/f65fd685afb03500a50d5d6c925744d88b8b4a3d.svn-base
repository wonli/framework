<?php
//词库
class D_Base
{
	public static $priceStandard=array(1=>'月',2=>'季度',3=>'半年',4=>'年',5=>'终身');
	public static $notification=array(1=>'bbs',2=>'friend',3=>'correction',4=>'dict',5=>'mgr');//1：论坛，2：好友相关，3：纠错，4：词典审核发布相关，5：词典管理人员申请
	public static $viptype=array(1=>'月',2=>'季度',3=>'半年',4=>'年',5=>'终身');
	
	public static $soundtype=array(1=>'英语',2=>'法语',3=>'德语');
	
	public static $channels=array(100=>'中兴');
	const dictlog_database="idictlog";//词典日志数据库
	const notelog_database="inotelog";//笔记本日志
	const readlog_database="ireadlog";//阅读日志
	const sound_database="isound";//声音列表
	//const dictlog_database="idictlog";
	
	public $db;
	public $mongo;
	public function __construct($table="media",$database="iyunci")
	{
		$this->mongo=DataConnect::MongoDB();
		$this->db=$this->mongo->$database->selectCollection($table);
	}
	function group($key, $initial, $reduce, $condition)
	{
		return $this->db->group($key, $initial, $reduce, $condition);
	}
	//按照条件分页查询数据
	public function ListData($con,$sort=array(),$skip=0,$limit=20)
	{
		//$this->mongo->setSlaveOkay(false);
		return $this->db->find($con)->sort($sort)->skip($skip)->limit($limit);
	}
    
	//按照条件查询数据
	public function ListRows($con=array(),$sort=array())
	{
		return $this->db->find($con)->sort($sort);
	}
	//保存数据
	public function SaveRow($row)
	{
		return $this->db->save($row,array("safe"=>true));
	}
	//更新一条数据
	public function UpdateRow($con,$row,$para=array("safe"=>true))
	{
		return $this->db->update($con,array('$set'=>$row),$para);
	}
	//更新所有满足条件的数据
	public function UpdateAll($con,$row,$para=array("multiple" => true))
	{
		return $this->db->update($con,array('$set'=>$row),$para);
	}
	//更新满足条件的所有记录
	public function UpdateAll2($con,$row,$para=array("multiple" => true))
	{
		return $this->db->update($con,$row,$para);
	}
	
	//删除行
	public function RemoveRow($con)
	{
		return $this->db->remove($con,array("safe"=>true));
	}
	//获取行的个数
	public function CountRow($con=array())
	{
		return $this->db->count($con);
	}
	//查询指定行
	public function QueryRow($con=array())
	{
		//$this->mongo->setSlaveOkay(false);
		return $this->db->findOne($con);
	}
	//查询指定行的指定列
	public function QueryRowField($con=array(),$fields=array())
	{
		return $this->db->findOne($con,$fields);
	}	
    //查询指定列
	public function QueryField($con=array(),$fields=array())
	{
		return $this->db->find($con,$fields);
	}
		//更新或插入
	public function UpdateInsert($con,$row)
	{
		return 	$this->db->update($con,array('$set'=>$row),array("upsert" => true,"safe"=>true));
	}
	//某字段加1
	public function ColumnAdd($con,$column,$num=1,$para=array("safe"=>true))
	{
		return $this->db->update($con, array('$inc' => array($column => $num)),$para);
	}
	//分词服务
	public static function GetWords($str)
	{
		/*
		$result=@ictc_word($str);
		$words=@explode(' ',$result);
		$tmp=array();
		$words=$words?$words:array();
		foreach($words as $w)
		{
			$w=trim($w);
			if($w&&strlen($w)>1)
			{
			 	$tmp[]=$w;
				$tmp[]=strtolower($w);
				$tmp[]=strtoupper($w);
				$tmp[]=strtoupper(substr($w,0,1)).substr($w,1);
			}
		}
		if(!in_array($str,$tmp))
		{
			$tmp[]=$str;
		}
		$tmp= array_unique($tmp);
		
		return array_values($tmp);	
		*/
		return $str;
	}
	//插入词典操作日志获取日志操作码 did：词典id cid：关联内容id，$t:关联的表（T_TREE，T_TEMPLATE，T_KVS_DICT，T_TREE_ITEM，T_KVS_FILE），$opt（9删除,18插入，23更新） 操作,$state:是否通过审核
	public static function SaveDictLog($did,$cid,$t,$opt,$state=0,$seek=NULL,$do=NULL,$pos=NULL)
	{
		$logi=new D_Base('log.dict.index',D_Base::dictlog_database);
		$index=$logi->QueryRow(array('_id'=>new MongoId($did)));
		$i=intval($index['i'])+1;
		$log=new D_Base('log.dict',D_Base::dictlog_database);
		$con=array('did'=>$did,'cid'=>$cid,'t'=>$t,'s'=>$state);
		$row=array('did'=>$did,'cid'=>$cid,'t'=>$t,'s'=>$state,'opt'=>$opt,'i'=>$i);
		if($seek)
		{
			$row['seek']=$seek;
		}
		if($do)
		{
			$row['do']=$do;
		}
		if($pos)
		{
			$row['p']=$pos;
		}

		if($index)
		{
			$logi->ColumnAdd(array('_id'=>new MongoId($did)),'i');
		}
		else
		{
			$logi->SaveRow(array('_id'=>new MongoId($did),'i'=>1));
		}
		$log->UpdateInsert($con,$row);
		return $i;
	}
	//保存笔记本日志nid:笔记本id cid:关联的内容的id，$t 操作所关联到的表，，$opt（9删除,18插入，23更新）
	public static function SaveNoteLog($nid,$cid,$t,$opt,$did='',$key='')
	{
		$logi=new D_Base('log.note.index',D_Base::notelog_database);
		$index=$logi->QueryRow(array('_id'=>new MongoId($nid)));
		$i=intval($index['i'])+1;
		$log=new D_Base('log.note',D_Base::notelog_database);
		$con=array('nid'=>$nid,'cid'=>$cid,'t'=>$t);
		$row=array('nid'=>$nid,'cid'=>$cid,'t'=>$t,'opt'=>$opt,'i'=>$i,'did'=>$did,'key'=>$key);

		if($index)
		{
			$logi->ColumnAdd(array('_id'=>new MongoId($nid)),'i');
		}
		else
		{
			$logi->SaveRow(array('_id'=>new MongoId($nid),'i'=>1));
		}
		$log->UpdateInsert($con,$row);
		return $i;
	}
	
	//保存词典阅读日志$dtid:词典词条与分类关联数据的id,$did：词典id,$u：用户名
	public static function SaveReadLog($k,$did,$u)
	{
		$logi=new D_Base('user.dict');
		$index=$logi->QueryRow(array('did'=>$did,'u'=>$u));
		$i=intval($index['i'])+1;
		$log=new D_Base('user.dict.read',D_Base::readlog_database);
		
		$con=array('k'=>$k,'did'=>$did,'u'=>$u);
		$loginfo=$log->QueryRow($con);
		if(!$loginfo)
		{
			$log->SaveRow(array('k'=>$k,'did'=>$did,'u'=>$u,'i'=>$i));
			$logi->ColumnAdd(array('did'=>$did,'u'=>$u),'i');
			return $i;
		}
		else
		{
			return $loginfo['i'];
		}
	}
	
	//获取发布词典时的索引
	public static function GetDictLastIndex()
	{
		$dict=new D_Base('dict');
		$dictDs=$dict->ListData(array(),array('i'=>-1),0,1);
		$dictInfo=$dictDs->getNext();
		return intval($dictInfo['i'])+1;
	}
	
	//用户登录
	public static function UserLogin($username,$pwd)
	{
		
		$user=new D_Base('user');
		$userInfo=$user->QueryRow(array('_id'=>$username));
		if(!$userInfo&&$username!=strtolower($username))
		{
			$userInfo=$user->QueryRow(array('_id'=>strtolower($username)));
		}
		if($userInfo)
		{
			if(md5(md5($pwd).$userInfo['salt'])==$userInfo['pwd'])
			{
				$userInfo['ok']=1;
				$user->UpdateRow(array('_id'=>$username),array('lt'=>time(),'lp'=>Utility::GetRemoteIp()));
				return $userInfo;
			}
			else
			{
				return array('ok'=>-10002,'error'=>'密码错误');
			}
		}
		else
		{
			return array('ok'=>-10001,'error'=>'用户名错误');
		}
	}
	//判断字符串是否包含了 保留关键字
	public static function RetainKeys($str)
	{
		$retainwords=new D_Base("mgr.register.retain");
		$ds=$retainwords->ListRows();
		while($data=$ds->getNext())
		{
			if(preg_match("|".$data['_id']."|",$str))
			{
				return true;
			}
		}
		return false;
	}
	//用户注册
	public static function UserRegister($username,$pwd,$email='')
	{
		$tmp=strtolower($username);
		$username=strtolower($username);
		$user=new D_Base('user');
		$username=trim($username);
		if(!preg_match("|^[a-zA-Z0-9_\x80-\xff]+[^_]$|",$username))
		{
			return array('ok'=>-10002,'error'=>'用户名不合法');
		}
		if(self::RetainKeys($username))
		{
			return array('ok'=>-10006,'error'=>'不被允许的用户名');
		}
		if(strlen($username)<3)
		{
			return array('ok'=>-10003,'error'=>'用户名需要大于3个字符');
		}
		if(Utility::GetShowLen($username)>8)
		{
			return array('ok'=>-10005,'error'=>'用户名需要小于等于16个字符');
		}
		if(!$pwd)
		{
			return array('ok'=>-10004,'error'=>'密码不能为空');
		}
		if($email)
		{
			if(!preg_match("/^([a-z0-9]*[-_\.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[\.][a-z]{2,3}([\.][a-z]{2})?$/i",$email))
			{
				return array('ok'=>-10007,'error'=>'不合法的邮箱');
			}
		}
		$userInfo=$user->QueryRow(array('_id'=>$username));
		if($userInfo||$user->QueryRow(array('_id'=>$tmp)))
		{
			return array('ok'=>-10001,'error'=>'用户名已经存在');
		}
		else
		{
			$salt = substr(uniqid(rand()), -6);
			$pwd=md5(md5($pwd).$salt);
			$user->SaveRow(array('_id'=>$username,'pwd'=>$pwd,'salt'=>$salt,'capacity'=>1073741824,'lt'=>time(),'lp'=>Utility::GetRemoteIp(),'rt'=>time(),'rp'=>Utility::GetRemoteIp()));
			//插入默认笔记本
			$nrow=array('n'=>'默认笔记本','u'=>$username,'ct'=>time(),'des'=>'默认笔记本','t'=>2,'p'=>0,'logo'=>1);
			$note=new D_Base('user.note');
			$note->SaveRow($nrow);
			//添加默认词典
			//$userDict=new D_Base('user.dict');
			//$userDict->SaveRow(array('did'=>"4f4b222c40008f85b6000207",'expire'=>'0000-00-00 00:00:00','p'=>0,'u'=>$username,'ct'=>time()));
			
			return array('ok'=>1,'username'=>$username);
		}
	}
	
	//插入通知$u,关联的用户，ru:产生通知的用户，fid 关联数据id，c：通知内容，href 点击跳转地址 t：通知类型（1论坛,2好友,3纠错）
	public static function SentNotification($u,$ru,$fid,$c,$url,$t)
	{
		$notify=new D_Base('user.notification');
		$row=array('u'=>$u,'ru'=>$ru,'ct'=>time(),'new'=>1,'t'=>$t,'fid'=>$fid,'fn'=>1,'c'=>$c,'url'=>$url);
		$ninfo=$notify->QueryRow(array('u'=>$row['u'],'fid'=>$fid,'t'=>$t));
		//print_r($ninfo);
		//print_r($qtinfo);
		//echo $username;exit;
		if($ninfo)
		{
			$ninfo['ru']=$row['ru'];
			$ninfo['ct']=time();
			$ninfo['url']=$url;
			$ninfo['new']=1;
			$ninfo['c']=$c;
			$ninfo['fn']+=1;
			return $notify->SaveRow($ninfo);
		}
		else
		{
			return $notify->SaveRow($row);
		}
	}
	//检查用户的词典权限
	public static function CheckDictPri($dict,$u)
	{
		if($dict['private']==1)
		{
			return array('ok'=>1,'error'=>'');
		}
		else if($dict['private']==2)
		{
			$userfriend = new D_Base("user.friend");
			if($userfriend->QueryRow(array('u'=>$u,'fu'=>$dict['cu'])))
			{
				return array('ok'=>1,'error'=>'');
			}
			else
			{
				return array('ok'=>-200,'error'=>'该词典仅创建者好友可见');
			}
		}
		else if($dict['private']==3)
		{
			if(in_array($u,$dict['puls']))
			{
				return array('ok'=>1,'error'=>'');
			}
			else
			{
				return array('ok'=>-200,'error'=>'该词典仅创建者指定人可见');
			}
		}
		else
		{
			return array('ok'=>1,'error'=>'');
		}
	}
	
	//保存词典下载记录
	public static function SaveDownRecord($_did,$_u,$_pt=100,$_price=0,$_otn='')
	{
        $_time   = time();
        
        list($y,$m,$d,$h) = explode('-',date('Y-m-d-h'));
        $row =  array('did'=>$_did, 'u'=>$_u, 'pt'=>$_pt, 'price'=>floatval($_price), 'otn'=>$_otn, 'time'=>$_time, 'y'=>(int)$y, 'm'=>(int)$m, 'd'=>(int)$d, 'h'=>(int)$h);

		$base= new D_Base('dict.pay.record');
        //防止免费词典产生冗余数据
        if($_pt == '100') {
            $con = array('did'=>$_did,'u'=>$_u);
            $saverecord = $base->UpdateInsert($con, $row);
        } else {
            $saverecord = $base->SaveRow($row);
        }
        if($saverecord) return true;
        else return false;
	}
	//更新用户笔记本容量
	public static function UpdateUserNoteCapacity($u,$c)
	{
		$user=new D_Base('user');						      
		$user->ColumnAdd(array('_id'=>$u),'current',$c);
	}
	
}
?>