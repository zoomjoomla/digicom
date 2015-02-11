<?php
/**
* @package			DigiCom Joomla Extension
 * @author			themexpert.com
 * @version			$Revision: 341 $
 * @lastmodified	$LastChangedDate: 2013-10-10 14:28:28 +0200 (Thu, 10 Oct 2013) $
 * @copyright		Copyright (C) 2013 themexpert.com. All rights reserved.
* @license			GNU/GPLv3
*/

defined ('_JEXEC') or die ("Go away.");

class DigiComSessionHelper {
	var $_sid = null;
	var $user = null;
	var $_customer;
	var $_Itemid = null;

	function digicomSessionHelper () {

		global $Itemid;

		$db = JFactory::getDBO();
		$my = JFactory::getUser();
		$reg = JFactory::getSession();
		$time = time();
		$digicomid = 'digicomid';
		$sid = $reg->get($digicomid, 0);
		
		$sql = "delete from #__digicom_session where create_time<'".($time - 3600*24)."'";
		$db->setQuery($sql);
		$db->query();
		
		if (!$sid) {
			$sql = "select * from #__digicom_session where uid='".$my->id."'";
			$db->setQuery($sql);
			$digisession = $db->loadObject();
			if(isset($digisession->sid)){
				$sql = "UPDATE #__digicom_session SET `create_time`='".time()."'";
				$db->setQuery($sql);
				$db->query();
				$reg->set($digicomid, $digisession->sid);
			}else{
				$sql = "INSERT INTO #__digicom_session (`uid`,`create_time`, `cart_details`, `transaction_details`, `shipping_details`)
					VALUES
					('".$my->id."','".$time."', '', '', '')
					 ";
				$db->setQuery($sql);
				$db->query();
				$sid = $db->insertId();
				$reg->set($digicomid, $sid);
			}
		} else {
			//check if has userid
			$sql = "select * from #__digicom_session where sid='".$sid."'";
			$db->setQuery($sql);
			$digisession = $db->loadObject();
			if($digisession->uid == 0 && $my->id != 0){
				$sql = "UPDATE #__digicom_session SET `uid`='".$my->id."'";
				$db->setQuery($sql);
				$db->query();
			}
			
			$sid_time = $digisession->create_time;
			if (!$sid_time || ($sid_time + 3600*24) < $time) {
				$sql = "delete from #__digicom_session where sid='".$sid."'";
				$db->setQuery($sql);
				$db->query();
				$sql = "INSERT INTO #__digicom_session (`uid`,`create_time`, `cart_details`, `transaction_details`, `shipping_details`)
					VALUES
					('".$my->id."','".$time."', '', '', '')
					 ";
				$db->setQuery($sql);
				$db->query();
				$sid = $db->insertId();
				$reg->set($digicomid, $sid);
			}
		}

		$this->_sid = $sid;
		$this->_Itemid = $Itemid;
		$this->_user = $my;
		if ($this->_user->id > 0) {
			$sql ="select * from #__digicom_customers where id='".$this->_user->id."'";
			$db->setQuery($sql);
			$tmp = $db->loadObject();

			if ( $tmp ) {
				$this->_customer = $tmp;
			} else {
				$this->_customer = new stdClass();
			}
		} else {
			$this->_customer = new stdClass();
		}

		if (!isset($this->_customer->shipcountry)) $this->_customer->shipcountry = '';
		if (!isset($this->_customer->shipstate)) $this->_customer->shipstate = '';
		if (!isset($this->_customer->shipzipcode)) $this->_customer->shipzipcode = '';
		if (!isset($this->_customer->id)&&$my->id) $this->_customer->id = $my->id;
		$name_array = explode(" ", $my->name);
		$first_name = "";
		$last_name = "";
		if(count($name_array) == 1){
			$name = $my->name;
			$first_name = $name;
			$last_name = $name;
		} else {
			$last_name = $name_array[count($name_array)-1];
			unset($name_array[count($name_array)-1]);
			$first_name = implode(" ", $name_array);
		}
		if (!isset( $this->_customer->firstname )&& $my->id ) $this->_customer->firstname 	= $first_name;
		if (!isset( $this->_customer->lastname )&& $my->id ) $this->_customer->lastname 	= $last_name;
	}

	function getTransactionData() {
		if (empty($this->_sid) || $this->_sid < 1) return null;
		$db = JFactory::getDBO();
		$sql = "select transaction_details from #__digicom_session where sid=".$this->_sid;
		$db->setQuery($sql);
		$data = $db->loadResult();
		$data = unserialize(base64_decode($data));

		if (is_object($data) || !isset($data['cart']['orderid']) || empty($data['cart']['orderid'])){
			$data = array();
			$data['cart']['orderid'] = -1;
		}
		return $data;
	}
}
