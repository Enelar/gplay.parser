<?php

class spider extends api
{
  protected function Add($name, $high_priority = true)
  {
    if ($this->IsKnown($name))
      return;
    db::Query("INSERT INTO tasks(name, priority) VALUES ($1, $2)", [$name, $high_priority]);
  }

  private function IsKnown($name)
  {
    $res = db::Query("
      SELECT 
        (SELECT count(*) FROM database WHERE name=$1) as db,
        (SELECT count(*) FROM tasks WHERE name=$1) as tasks", [$name], true);
    return $res->db || $res->tasks;
  }
}