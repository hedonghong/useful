<?php

/**
 * 写的比较随意，应该都看得懂的，保留用户的配置项，目前我是用于项目的配置项合并
 * Class merge_array
 */
class merge_array
{
    public function test1()
    {
        /**
         * $a系统的
         */
        $a = [
            'name' => [
                'tran1' => [1, 2],
                'tran2' => 'x',
            ],
            'name1' => [
                'tran3' => [
                    't1' => 1,
                    't2' => 2,
                ],
            ],
            'name3' => '5',
            'name4' => ['x1', 'x2'],
        ];

        /**
         * $b用户的
         */
        $b = [
            'name' => [
                'tran1' => [3, 4],
                'tran2' => 'x1',
            ],
            'name1' => [
                'tran3' => [
                    't1' => 3,
                    't2' => 4,
                ],
            ],
            'name5' => ['xx', 'xxx']
        ];

        $this->my_merge($a, $b);
        print_r($a);

    }


    function my_merge(&$a, $b)
    {

        foreach ($a as $key => &$val) {
            if (is_array($val) && array_key_exists($key, $b) && is_array($b[$key])) {
                $this->my_merge($val, $b[$key]);
                $val = array_merge($val, $b[$key]);
            } else if (is_array($val) || (array_key_exists($key, $b) && is_array($b[$key]))) {
                $val = is_array($val) ? $val : $b[$key];
            }
        }
        $a = array_merge($a, $b);
    }
}

$ob = new test1();
$ob->test1();