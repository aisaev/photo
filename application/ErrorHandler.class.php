<?php
namespace photo\common;

class ErrorHandler
{
    const PDO_INIT_FAILURE = 1001; //failed to connect to DB
    const PDO_GET_NEXT_ID = 1002; //failed to get next ID from sequence
}

