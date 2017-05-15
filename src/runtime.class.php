<?php
/*计算运行时间Count Script Runing Time*/
class RunTime
{
    private $starttime = 0;
    private $stoptime = 0;
 
    function getMicroTime()
    {
        list($usec, $sec) = explode(' ', microtime());
        return ((float)$usec + (float)$sec);
    }
 
    function start()
    {
        $this->starttime = $this->getMicroTime();
    }
 
    function stop()
    {
        $this->stoptime = $this->getMicroTime();
    }
 
    function spent()
    {
        return round(($this->stoptime - $this->starttime) * 1000, 1);
    }
 
}
?>