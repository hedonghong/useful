<?php
//array_multisort(array_column($datas,'sortKey'),SORT_ASC, $datas);
//array_column 指定需要排序的数组 key，本例将按 sortKey 的值进行排序
//array_multisort 的第二个参数是排序规则，第三个参数是需要排序的数组
$datas = [
    ['key1' => 3, 'key2' => [1,2,3]],
    ['key1' => 1, 'key2' => [1,2,3]],
    ['key1' => 5, 'key2' => [1,2,3]],
    ['key1' => 4, 'key2' => [1,2,3]],
];

array_multisort(array_column($datas,'key1'),SORT_ASC, $datas);
print_r($datas);
/*
 * 输出结果
Array
(
    [0] => Array
        (
            [key1] => 1
            [key2] => Array
                (
                    [0] => 1
                    [1] => 2
                    [2] => 3
                )

        )

    [1] => Array
        (
            [key1] => 3
            [key2] => Array
                (
                    [0] => 1
                    [1] => 2
                    [2] => 3
                )

        )

    [2] => Array
        (
            [key1] => 4
            [key2] => Array
                (
                    [0] => 1
                    [1] => 2
                    [2] => 3
                )

        )

    [3] => Array
        (
            [key1] => 5
            [key2] => Array
                (
                    [0] => 1
                    [1] => 2
                    [2] => 3
                )

        )

)
 */