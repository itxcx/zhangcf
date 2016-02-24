<?php
    class fun_dateset extends stru
    {
        public $dateRange ="";
            /**
             * 判断日期是否在设定的节假日中 (节假日: 日期范围、 周、 日期范围&&周)
             * @access public
             * @return bool
             */
        public function getDateBool($time){
                // 节假日为空 则返回指定日期不在节假日中
                if($this->dateRange == ''){
                    return false;
                }

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

                foreach($date as $val){
                    $dateArr = explode(';',$val);
                    // 判断周是否在节假日中
                    if($dateArr[2] !=""){
                        if(!in_array($week,explode(',',$dateArr[2]))){
                            continue;
                        }
                    }

                    // 判断日期是否在节假日范围
                    if($dateArr[0] == '任意时间'){
                        if($time>strtotime($dateArr[1]))continue;
                    }
                    else if($dateArr[1] == '以后'){
                        if($time<strtotime($dateArr[0]))continue;
                    } 
                    else if($dateArr[0] !="" && $dateArr[1] !=""){
                        // 解决日期可能前大后小
                        if(strtotime($dateArr[0])>strtotime($dateArr[1])){
                            $t = $dateArr[0];
                            $dateArr[0] = $dateArr[1];
                            $dateArr[1] = $t;
                        }
                        if(($time<strtotime($dateArr[0])) || ($time>strtotime($dateArr[1]))){
                            continue;
                        }
                    }
                    return true; // 在节假日中
                }
                return false; 
        }
    }
?>