<?php

class Constants {
    const TRUE = 1;
    const FALSE = 0;

    //response code (More at: vendor\symfony\http-foundation\Response.php)
    const STATUS_CODE_SUCCESS                 = 200;
    const STATUS_CODE_VALIDATION_ERROR        = 400;
    const STATUS_CODE_UNAUTHORIZED_ERROR      = 401;
    const STATUS_CODE_NOT_FOUND_ERROR         = 404;
    const STATUS_CODE_ERROR                   = 500;
}