<?php
    class fun_invest_many2many
    {
        public static function match($puts, $gets,$maxVal=-1)
        {
            $ret=array();
            $sumVal = 0;
            $put    = null;
            //循环取得记录
            foreach ($gets as $getsid=>$get) {
                $i=0;
                while ($get['待匹配金额'] > 0 && $i<1000) {
                    $i++;
                    //如果当前没有put数据,数组中也没有,则返回
                    if($put == null && count($puts) == 0)
                    {
                        return $ret;
                    }
                    //put数组中没有数据,则从数组中拉出来一个
                    if($put == null)
                        $put = array_shift($puts);
                    //能够将付款普配直接吃掉
                    if ($get['待匹配金额'] >= $put['待匹配金额']) {
                        $val = $put['待匹配金额'];
                        $putid=$put['id'];
                        $getid=$get['id'];
                        $get['待匹配金额'] -= $put['待匹配金额'];
                        $put=null;
                    } else {
                        //吃不完
                        $val = $get['待匹配金额'];
                        $putid=$put['id'];
                        $getid=$get['id'];
                        $put['待匹配金额'] -= $get['待匹配金额'];
                        $get['待匹配金额'] = 0;
                    }
                    //计算匹配上限
                    $sumVal += $val;
                    if($maxVal == -1 || $maxVal>=$sumVal)
                    {
                        $ret[]=array($putid,$getid,$val);
                    }
                    else
                    {
                        dump('付款id'.$putid.'收款id'.$getid.'超过上限'.$maxVal);
                        return $ret;
                    }
                }
            }
            return $ret;
        }
    }
