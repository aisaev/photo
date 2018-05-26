<?php
namespace photo\DAO;
use photo\Model\DBModel;

interface IDAO {
    function create(DBModel &$o): bool;
    function update(DBModel $o): bool;
    function delete(DBModel $o): bool;
    function save(DBModel &$o): bool;
    function findById(array $pk): DBModel;
    function getList($listOfPK=null): array;
    function initFromPOST($entry, DBModel &$o);
}
?>