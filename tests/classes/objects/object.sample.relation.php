<?php
class ObjectSampleRelation extends \ObjectAbstract
{
  public function __construct()
  {
    parent::__construct('sample_relation');
  }

  public function getOne($username)
  {
    $query = $this->select()->filter('username', $username)->limit(1);
    $user_id = $this->execute($query);

    if (count($user_id) != 1)
    {
      return false;
    }

    $user = $this->get($user_id[0]);
    if ($user === false)
    {
      return false;
    }

    $userObj = $user->getObject();
    $userObj['flags'] = $user->flags();
    return $userObj;
  }

}
