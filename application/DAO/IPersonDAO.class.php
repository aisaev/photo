<?php
namespace photo\DAO;

interface IPersonDAO extends IDAO {
    public function refreshPPN(): int;
}
?>