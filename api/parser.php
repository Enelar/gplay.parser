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

  private function ParseGame($name)
  {
    $obj = $this->RawScan('http://playpack.ru/flash/'.$name);
    var_dump($obj);
    return $obj['parsed'];
  }

  private function ParseOne($name)
  {
    $res = $this->ParseGame($name);
    $trans = db::Begin();

    db::Query("DELETE FROM tasks WHERE addr=$1", [$name]);
    db::Query("DELETE FROM database WHERE url=$1", [$name]);
    db::Query("INSERT INTO database
      (url, name, game_url, description, saw)
      VALUES
      ($1, $2, $3, $4, true)",
      [
        $name,
        $res['title'],
        $res['game_url'],
        $res['description'],
      ]);

    $trans->Commit();

    foreach ($res['urls'] as $url)
    {
      $old = $url;
      $url = str_replace("/flash/", "", $old);
      if ($old == $url)
        return; // not interesting
      $trans = db::Begin();
      @db::Query("INSERT INTO database(url, name) VALUES ($1, '')", [$url]);
      @db::Query("INSERT INTO tasks(addr) VALUES ($1)", [$url]);
      $trans->Commit();
    }
?>
<script type='text/javascript'>
setTimeout(function() { location.reload(); }, 5000);
</script>
<?php
    return ["reset" => true];
  }

}
