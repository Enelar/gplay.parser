<?php

class main extends api
{
  protected function Reserve()
  {
    unset($this->addons['result']);
    return
    [
      "design" => "menu"
    ];
  }

  protected function Home()
  {
    return $this('api', 'admin', true)->Reserve();
  }
}