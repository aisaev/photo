<?php
namespace photo\DAO;

use photo\Model\Event;

interface IEventDAO
{
    function createEvent(Event &$o): bool;
}

