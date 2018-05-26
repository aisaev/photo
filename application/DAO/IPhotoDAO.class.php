<?php
namespace photo\DAO;

use photo\Model\Event;
use photo\Model\Location;
use photo\Model\Person;
use photo\Model\Photo;

interface IPhotoDAO extends IDAO {
    public function getListByEvent(Event $oe): array;
    public function getListByLocation(Location $loc): array;
    public function getListByPerson(Person $psn): array;
    public function createPeopleLink(Photo &$o);
}
?>