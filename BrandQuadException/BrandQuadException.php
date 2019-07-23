<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 11.01.2019
 * Time: 11:49
 */

namespace Taber\BrandQuad\BrandQuadException;


use Taber\Podrygka\TaberLogs\TaberExceptionLog;

class BrandQuadException extends \Exception
{

    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        self::writeLog();  // при возникновении ошибки сразу пишет ее в таблицу логов taber_logs
    }

    public function writeLog()
    {
        if (!NO_TABER_BRAND_QUAD_LOGS) {  // можно отключить запись ошибок в таблицу taber_logs
            new TaberExceptionLog($this);
        }
    }
}