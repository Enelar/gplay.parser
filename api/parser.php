<?php

class parser extends api
{
  protected function NextOne()
  {
    $res = db::Query("
      WITH one AS
      (
        SELECT addr as name FROM tasks WHERE \"lock\" IS NULL OR now()-\"lock\">'5 min'::interval LIMIT 1
      ) UPDATE tasks SET \"lock\"=now() FROM one WHERE addr=name RETURNING name", [], true);
    if (!$res())
      return;
    return $this->ParseOne($res->name);
  }

  private function RawScan($url)
  {
    $result = tempnam("/tmp", "parser_tmp_");

    $pjs = LoadModule("api", "phantomjs");
    $res = $pjs->Execute("parse.js", [$result, $url]);

    $return = file_get_contents($result);
    
    unlink($result);

    $obj = json_decode($return, true);
    if ($obj)
      return $obj;
    return $res;
  }

  protected function ParseGame($name)
  {
    $obj = $this->RawScan('http://play.google.com/store/apps/details?hl=ru&id='.$name);
    var_dump($obj);
    return $obj['parsed'];
  }

  private function ParseOne($name)
  {
    $res = $this->ParseGame($name);
    $trans = db::Begin();
    var_dump(      [ 
        $name,
        (int)$res['installs'],
        $res['rating'],
        $res['rated'],
        $this->CrapCodedDateConvert($res['updated']),
        $res['category'],
      ]);

    db::Query("DELETE FROM tasks WHERE addr=$1", [$name]);
    db::Query("DELETE FROM database WHERE url=$1", [$name]);
    db::Query("INSERT INTO database
      (url, name, installs, rating, comments, updated, category)
      VALUES
      ($1, $2, $3, $4, $5, $6, $7)",
      [
        $name,
        $res['name'],
        (int)$res['installs'],
        $res['rating'],
        $res['rated'],
        $this->CrapCodedDateConvert($res['updated']),
        $res['category'],
      ]);

    $trans->Commit();

    foreach ($res['urls'] as $url)
    {
      $url = str_replace("/store/apps/details?id=", "", $url);
      $trans = db::Begin();
      db::Query("INSERT INTO database(url, name) VALUES ($1, '')", [$url]);
      db::Query("INSERT INTO tasks(addr) VALUES ($1)", [$url]);
      $trans->Commit();
    }
  }

  protected function CrapCodedDateConvert($date)
  {
    $rus =
    [
      'января',
      'февраля',
      'марта',
      'апреля',
      'мая',
      'июня',
      'июля',
      'августа',
      'сентября',
      'октября',
      'ноября',
      'декабря',
    ];

    $eng =
    [
      'January',
      'February',
      'March',
      'April',
      'May',
      'June',
      'July',
      'August',
      'September',
      'October',
      'November',
      'December',
    ];

    $date = str_replace("г.", "", $date);
    return str_replace($rus, $eng, $date);
  }
}