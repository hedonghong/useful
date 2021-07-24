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

    /**
     * 创建多维数组
     * $keys = ['a', 'b', 'c']
     * 输出 $original['a']['b']['c'] = $value
     * @param array $keys 键值
     * @param array $original 想动态创建的多维数组名
     * @param null $value 创建后赋值，默认为null
     * @date 2021/7/24 10:19 上午
     * @author sky.he 782101031@qq.com
     */
    public function multiArrayCreate(array $keys, &$original = [], $value = null)
    {
        $ref  = &$original;
        $keys = ['a', 'b', 'c'];
        foreach ($keys as $key) {
            $ref[$key] = [];
            $ref = &$ref[$key];
        }
        $ref = $value;
    }
