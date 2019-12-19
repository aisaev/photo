<?php
namespace photo\DAO;

use photo\Model\Event;

interface IDAOEvent extends IDAO {
    public function getMinSeqNum($id): int;
    public function findById(array $pk): Event;
}