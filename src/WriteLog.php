<?php
/**
 * ------------------------
 *  Email  : yc_1224@163.com
 *  Author : 阿超
 *  DATE   : 2022/12/29
 * ------------------------
 */
namespace yangchao\log_viewer;

class WriteLog
{
    protected static $instance;

    protected $level_info = 'INFO';
    protected $level_error = 'ERROR';
    protected $level_warning = 'WARNING';

    protected $message = '';

    protected $log_root_dir = 'logs';//日志目录文件夹
    protected $log_root_path = '';//日志文件根目录
    protected $log_dir_path = '';//日志文件所在目录
    protected $log_path = '';//日志完整路径

    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * 记录写入消息
     * @param $message 断句消息
     * @return $this
     */
    public function record($message)
    {
        $this->message .= $message . "\n";
        return $this;
    }

    /**
     * 写入详情日志
     * @param $path
     * @param $message
     * @return void
     */
    public function info($path, $message = '')
    {
        $this->log($path, $this->write($this->level_info, $message));
    }

    /**
     * 写入错误日志
     * @param $path
     * @param $message
     * @return void
     */
    public function error($path, $message = '')
    {
        $this->log($path, $this->write($this->level_error, $message));
    }

    /**
     * 写入警告日志
     * @param $path
     * @param $message
     * @return void
     */
    public function waring($path, $message = '')
    {
        $this->log($path, $this->write($this->level_warning, $message));
    }

    /**
     * 写入日志
     * @param $path
     * @param $str
     * @return void
     */
    public function log($path,$str)
    {
        file_put_contents($this->createDir($path),$str,FILE_APPEND);
    }

    /**
     * 日志字符串
     * @param $level
     * @param $message
     * @return string
     */
    public function write($level, $message)
    {
        if ($this->message) $message = $this->message . $message;
        return date('Y-m-d H:i:s') . "[{$level}] " . $message . "\n";
    }

    /**
     * 创建目录
     * @param $path
     * @return string
     */
    private function createDir($path)
    {
        $this->log_dir_path = $this->rootPath() . '/' . $this->log_root_dir . '/' . $path . '/' . date('Ym');
        if (! is_dir( $this->log_dir_path )){
            mkdir($this->log_dir_path,0777, true);
        }
        $this->log_path = $this->log_dir_path .'/'. date('d') . '.log';
        return $this->log_path;
    }

    public function rootPath()
    {
        $this->log_root_path = (str_ireplace(str_replace("/","\\",$_SERVER['PHP_SELF']),'',__FILE__)."\\");
        return $this->log_root_path;
    }

}