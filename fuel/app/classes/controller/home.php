<?php

use Fuel\Core\Controller_Template;
use Fuel\Core\Debug;
use Fuel\Core\Lang;
use Fuel\Core\Response;
use Fuel\Core\Session;
use Fuel\Core\View;

class Controller_Home extends Controller_Template
{
    public $template = 'layout/index';

    public function before()
    {
        parent::before();

        $user = Session::get('user');
        $sessionServer = Session::get('server');

        if(null === $user)
            Response::redirect('/login');

        $server = $sessionServer ? Model_Server::find_by_pk($sessionServer->id) : (Model_Server::find_one_by('user_id', $user->id) ?: Model_Server::find_one_by([
                ['online', '=', 1],
                ['disable', '=', 0],
            ], null, null)[0]
        );

        if(!$server)
            Response::redirect('/login');

        Lang::load('menu');
        Lang::load('settings');

        $this->template->title = 'Home';

        $libraries = $server->getLibraries();

        $this->template->servers = Model_Server::find([
            'where' => [
                ['online', '=', 1],
                ['disable', '=', 0],
            ],
        ]);
        $this->template->user = Session::get('user');
        $this->template->MenuServer = $server;
        $this->template->MenuLibraries = $libraries;

        $this->template->js_bottom = ['clappr.min.js', 'player.js', 'plex_alert.js'];
    }

    public function action_index()
    {
        Lang::load('home');
        Lang::load('season');

        $body = View::forge('home/index');

        $server_id = $this->param('server_id');

        if ($server_id !== NULL) {
            $server = Model_Server::find_by_pk($server_id);

            if ($server)
                $this->template->MenuServer = $server;
        }

        Session::delete('server');
        Session::set('server', $this->template->MenuServer);

        $this->template->MenuLibraries = $this->template->MenuServer->getLibraries();

        $episodes = $this->template->MenuServer->getThirtyLastedTvShows();

        $movies = $this->template->MenuServer->getThirtyLastedMovies();

        $body->set('episodes', $episodes);
        $body->set('movies', $movies);

        $this->template->body = $body;
    }
}