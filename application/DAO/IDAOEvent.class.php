<?php
namespace photo\DAO;

interface IDAOEvent extends IDAO {
    public function getMinSeqNum($id): int;
}