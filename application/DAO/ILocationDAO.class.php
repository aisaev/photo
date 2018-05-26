<?php
namespace photo\DAO;

use photo\Model\Location;

interface ILocationDAO {
    public function refreshPPN();
    public function readChildren(Location &$o);
}
?>