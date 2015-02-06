<?php

class admin extends api
{
  protected function Reserve()
  {
    return
    [
      "design" => "index",
    ];
  }

  protected function IgnoreList()
  {
    return
    [
      "design" => "ignore",
      "data" =>
      [
        "list" => db::Query("SELECT url, name FROM database WHERE ignore=true"),
      ],
    ];
  }

  protected function ChangeStatus( $url, $field, $value )
  { // SQLINJ. but it doesnt matter since its project have single user
    $res = db::Query("UPDATE database SET $field=$2 WHERE url=$1 RETURNING url", [$url, $value], true);
    if ($res())
      return;
    db::Query("DELETE FROM tasks WHERE addr=$1", [$url]);
    db::Query("INSERT INTO database(url, ignore) VALUES ($1, true)", [$url]);
  }

  protected function Fresh($days = 1)
  {
    $list = db::Query("SELECT * FROM database WHERE now()-updated<$1::interval ORDER BY since DESC", ["$days days"]);
    return
    [
      "design" => "fresh",
      "data" => ["list" => $list],
    ];
  }

  protected function Filter($install = null, $rating = null, $comments = null, $date = null)
  {
    if ($date == 0)
      $date = null;

    if ($install == null)
      $res = null;
    else
      $res = db::Query("SELECT * FROM database WHERE
        saw = false AND ignore = false AND
        ($1::int4 IS NULL OR installs > $1::int4) AND
        ($2::float IS NULL OR rating > $2::float) AND
        ($3::int4 IS NULL OR comments > $3::int4) AND
        ($4::date IS NULL OR updated > $4::date)
        ORDER BY since DESC
        ", [$install, $rating, $comments, $date]);
    return
    [
      "design" => "filter",
      "data" => ["list" => $res],
    ];
  }

  protected function Export()
  {

  }

  protected function Saw($url)
  {
    db::Query("UPDATE database SET ignore=true WHERE url=$1", [$url]);
  }
}