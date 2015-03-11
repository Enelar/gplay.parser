<?php
error_reporting(E_ALL);
ini_set('display_errors','On');

include_once('phpsql/phpsql.php');
include_once('phpsql/pgsql.php');
$sql = new phpsql();
$pg = $sql->Connect("pgsql://postgres@localhost/games");
include_once('phpsql/wrapper.php');

include_once('phpsql/db.php');
db::Bind(new phpsql\utils\wrapper($pg));

function phoxy_conf()
{
  $ret = phoxy_default_conf();

  return $ret;
}

function default_addons( $name )
{
  $ret =
  [
    "cache" => "no",
    "result" => "canvas",
  ];
  return $ret;
}

include('phoxy/index.php');
