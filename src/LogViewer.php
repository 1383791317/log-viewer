<?php
/**
 * ------------------------
 *  Email  : yc_1224@163.com
 *  Author : 阿超
 *  DATE   : 2022/12/5
 * ------------------------
 */
namespace yangchao\log_viewer;

use yangchao\log_viewer\exception\LogViewerException;

class LogViewer
{
    /**
     * 文件名称
     * @var string
     */
    protected $file_name;

    /**
     * 文件路径
     * @var string
     */
    protected $filePath;

    /**
     * 操作的文件夹
     * @var string
     */
    protected $root_dir;

    /**
     *
     * @var array
     */
    protected $pageOffset = [];

    /**
     * 设置操作的文件夹
     * @param string $dir
     * @return $this
     */
    public function setRootDir(string $dir)
    {
        if (!is_dir($dir)) {
            throw new LogViewerException('不是目录');
        }
        $this->root_dir = $dir;
        return $this;
    }

    /**
     * 设置操作的文件夹
     * @param string $dir
     * @return $this
     */
    public function setFilePath(string $file_path)
    {
        if (!is_file($file_path)) {
            throw new LogViewerException('不是文件');
        }
        $this->filePath = $file_path;
        return $this;
    }

    /**
     * 获取目录下所有文件.
     * @param int $count
     * @return array
     */
    public function getFiles($dir_path = '', $nameKey = 'name', $childKey = 'child') :array
    {
        $dir_path = $dir_path ?: $this->root_dir;

        $list = [];
        $temp_list = scandir($dir_path);

        foreach ($temp_list as $file){

            if ($file != '..' && $file != '.'){

                $file_path = $dir_path . '/' . $file;

                if (is_dir($file_path)){
                    $list[] = [
                        $nameKey    => $file,
                        'path'      => $file_path,
                        $childKey   => $this->getFiles($file_path, $nameKey, $childKey)
                    ];
                }else{
                    $list[] = [
                        $nameKey    => $file,
                        'path'      => $file_path,
                        'size'      => filesize($file_path),
                        'update'    => filemtime($file_path),
                    ];
                }
            }
        }
        return $list;
    }

    /**
     * Get previous page url.
     *
     * @return bool|string
     */
    public function getPrevPageUrl()
    {
        if ($this->pageOffset['end'] >= $this->getFilesize() - 1) {
            return false;
        }
        return [
            'file' => $this->filePath,
            'offset' => $this->pageOffset['end'],
        ];
    }

    /**
     * Get Next page url.
     *
     * @return bool|string
     */
    public function getNextPageUrl()
    {
        if ($this->pageOffset['start'] == 0) {
            return false;
        }

        return [
            'file' => $this->filePath,
            'offset' => -$this->pageOffset['start']
        ];
    }

    /**
     * 通过给定偏移量来获取日志
     * @param $seek
     * @param $lines
     * @param $buffer
     * @return array
     */
    public function fetch($seek = 0, $lines = 20, $buffer = 4096)
    {
        if (!file_exists($this->filePath) || is_dir($this->filePath)) {
            $this->pageOffset = ['start' => 0, 'end' => 0];

            return $this->parseLog('');
        }

        $f = fopen($this->filePath, 'rb');

        if ($seek) {
            fseek($f, abs($seek));
        } else {
            fseek($f, 0, SEEK_END);
        }

        if (fread($f, 1) != "\n") {
            $lines -= 1;
        }

        fseek($f, -1, SEEK_CUR);

        // 从前往后读,上一页
        // Start reading
        if ($seek > 0) {
            $output = '';

            $this->pageOffset['start'] = ftell($f);

            while (!feof($f) && $lines >= 0) {
                $output = $output.($chunk = fread($f, $buffer));
                $lines -= substr_count($chunk, "\n[20");
            }

            $this->pageOffset['end'] = ftell($f);

            while ($lines++ < 0) {
                $strpos = strrpos($output, "\n[20") + 1;
                $_ = mb_strlen($output, '8bit') - $strpos;
                $output = substr($output, 0, $strpos);
                $this->pageOffset['end'] -= $_;
            }

            // 从后往前读,下一页
        } else {
            $output = '';

            $this->pageOffset['end'] = ftell($f);

            while (ftell($f) > 0 && $lines >= 0) {
                $offset = min(ftell($f), $buffer);
                fseek($f, -$offset, SEEK_CUR);
                $output = ($chunk = fread($f, $offset)).$output;
                fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
                $lines -= substr_count($chunk, "\n2022-");
            }

            $this->pageOffset['start'] = ftell($f);

            while ($lines++ < 0) {
                $strpos = strpos($output, "\n2022-") + 1;
                $output = substr($output, $strpos);
                $this->pageOffset['start'] += $strpos;
            }
        }

        fclose($f);

        return $this->parseLog($output);
    }

    /**
     * 清空文件
     * @return void
     */
    public function clear($path)
    {
        if (!is_file($path)) {
            throw new LogViewerException('不是文件');
        }
        file_put_contents($path,"");
    }

    /**
     * 在日志文件中获取尾日志
     * @param int $seek
     * @return array
     */
    public function tail($seek)
    {
        // Open the file
        $f = fopen($this->filePath, 'rb');

        if (!$seek) {
            // Jump to last character
            fseek($f, -1, SEEK_END);
        } else {
            fseek($f, abs($seek));
        }

        $output = '';

        while (!feof($f)) {
            $output .= fread($f, 4096);
        }

        ftell($f);

        fclose($f);

        return $this->parseLog(trim($output));
    }

    protected function parseLog($raw)
    {
        $logs = preg_split('/(\d{4}(?:-\d{2}){2} \d{2}(?::\d{2}){2})\[(\w+)\] ?/', trim($raw), -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        if (empty($logs)) {
            return [];
        }

        $parsed = [];

        foreach (array_chunk($logs, 3) as $log) {
            $parsed[] = [
                'time'  => $log[0] ?? '',
                'env'   => $log[1] ?? '',
                'info'  => trim($log[2] ?? ''),
            ];
        }

        unset($logs);

        rsort($parsed);
        return $parsed;
    }

    /**
     * 获取文件大小
     * @return int
     */
    public function getFilesize()
    {
        return filesize($this->filePath);
    }
}