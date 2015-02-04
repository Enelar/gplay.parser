<?php

function override_strpos($string, $needle, $offset = 0)
{
  if (!is_array($needle))
    return strpos($string, $needle, $offset);

  foreach ($needle as $need)
    if (($ret = strpos($string, $need, $offset)) !== false)
      return $ret;
  return false;
}

// http://php.net/manual/en/function.strip-tags.php#86964
function strip_tags_content($text, $tags = '', $invert = FALSE)
{ 
  preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($tags), $tags); 
  $tags = array_unique($tags[1]);
    
  if(is_array($tags) AND count($tags) > 0)
  { 
    if($invert == FALSE)
      return preg_replace('@<(?!(?:'. implode('|', $tags) .')\b)(\w+)\b.*?>.*?</\1>@si', '', $text); 
    else
      return preg_replace('@<('. implode('|', $tags) .')\b.*?>.*?</\1>@si', '', $text); 
  } 
  else if($invert == FALSE)
    return preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text); 
  return $text; 
} 

class phantomjs extends api
{
  public function Execute( $file, $arguments )
  {
    $args = [];
    foreach ($arguments as $a)
      $args[] = escapeshellarg($a);
    
    if (override_strpos($file, ["..", '"', "'", "\b"]) !== false)
      phoxy_protected_assert(false, ["error" => "Security bless you"]);

    $query = "./phantomjs '{$file}' ".(implode(' ', $args));

    var_dump($query);
    $res = system($query);
    var_dump($res);
    //$res = exec($query);

    return $res;
  }

  protected function Show( $id )
  {
    //var_dump($code);
    $res = db::Query("SELECT id, data, md5(data) FROM spider.page_cache WHERE id=$1", [$id], true);

    if (!count($res))
      return "NOT FOUND";
    echo strip_tags_content($res['data'], "<script><noscript>", true);
    die();
  }
}