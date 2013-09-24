<?php
//词库
class MongoBase
{
	public $db;
	public $mongo;
	public function __construct($table="media",$database="iyunci")
	{
		$this->mongo=new Mongo("mongodb://192.168.1.100:27017", array( 'connect'=>true ));
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
}
?>