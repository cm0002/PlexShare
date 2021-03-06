<?php

use Fuel\Core\Lang;
use Fuel\Core\Response;
use Fuel\Core\View;

class Controller_Tvshow extends Controller_Home
{
    public function action_index()
    {
        $tvshow_id = $this->param('tvshow_id');

        $tvshow = Model_Tvshow::find_by_pk($tvshow_id);

        if(!$tvshow)
            Response::redirect('/home');

        Lang::load('movie');
        Lang::load('season');
        Lang::load('action');

        $body = View::forge('tvshow/index');

        $this->template->title = $tvshow->title;

        $tvshow->getMetaData();

        $body->set('tvshow', $tvshow);

        $this->template->body = $body;
    }
}