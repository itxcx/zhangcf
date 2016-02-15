<?php
class fun_dateset extends stru
{
		public $dateRange   ="";
		
		public function getDateBool($time){
            $week = date('w',$time);
            switch($week){
                case 0: $week = "周日";break;
                case 1: $week = "周一";break;
                case 2: $week = "周二";break;
                case 3: $week = "周三";break;
                case 4: $week = "周四";break;
                case 5: $week = "周五";break;
                case 6: $week = "周六";break;
            }
			$date = explode('|',$this->dateRange);
            $result = false;
            foreach($date as $val){
                $dateArr = explode(';',$val);
                if($dateArr[2] !=""){
                    if(in_array($week,explode(',',$dateArr[2]))){
                        $result = true;
                    }else{
                        $result = false;
                        continue;
                    }
                }
                if($dateArr[0] !="" && $dateArr[0] !="任意时间"){
                    $starttime = strtotime($dateArr[0]);
                    if($time >= $starttime){
                        $result = true;
                    }else{
                        $result = false;
                        continue;
                    }
                }
                if($dateArr[1] !="" && $dateArr[1] !="以后"){
                    $endtime = strtotime($dateArr[1]) + 24*60*60;
                    if($time < $endtime){
                        $result = true;
                    }else{
                        $result = false;
                        continue;
                    }
                }
                if($result == true){
                    return true;
                }
            }
            if($result == false){
                return false;
            }
		}
}
?>