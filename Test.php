<?php
/**
 * ------------------------
 *  Email  : yc_1224@163.com
 *  Author : 阿超
 *  DATE   : 2022/12/7
 * ------------------------
 */
namespace log_viewer;

class Test
{
    public function test()
    {
        /**
         * 文件分页
         * offset 分页起始节点
         */
        $class = new LogViewer();
        $class->setFilePath('D:\projects\log-viewer\LogViewer.php');
        [
            "rows" => $class->fetch($_GET['offset'] ?? 0),
            "next" => $class->getNextPageUrl(),
            "prev" => $class->getPrevPageUrl(),
        ];

        /**
         * 目录下树状
         */
        $class->getFiles('D:\projects','text','nodes');

        /**
         * 清空文件
         */
        $class->clear('D:\projects\log-viewer\LogViewer.php');
    }

}