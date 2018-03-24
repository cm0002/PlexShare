<?php


class Model_Library extends Model_Overwrite
{
    protected static $_table_name = 'library';
    protected static $_primary_key = 'id';
    protected static $_properties = array(
        'id',
        'plex_key',
        'server_id',
        'name',
        'type',
        'updatedAt',
        'createdAt',
        'scannedAt',
        'disable'
    );

    /**
     * List all libraries and register it in database
     * @param $server
     * @return bool | array
     * @throws Exception
     * @throws HttpNotFoundException
     * @throws \FuelException
     */
    public static function BrowseLibraries($server)
    {
        $libraries_id_array = [];

        $curl = Request::forge('http://' . $server->url . ($server->port? ':' . $server->port : '') . '/library/sections?X-Plex-Token=' . $server->token, 'curl');
        $curl->execute();

        if($curl->response()->status !== 200)
            return false;

        $list_libraries = Format::forge($curl->response()->body, 'xml')->to_array();

        if(!isset($list_libraries['Directory']))
            return false;

        $list_libraries = $list_libraries['Directory'];

        foreach ($list_libraries as $index => $library) {
            $library = !isset($list_libraries['@attributes']) ? $library : $list_libraries;

            $libraries_id_array[] = ['id' => $library['@attributes']['uuid'], 'name' => $library['@attributes']['title']];

            $new_library = Model_Library::find_by_pk($library['@attributes']['uuid']) ?: Model_Library::forge();

            $new_library->set(array(
                'id'        => $library['@attributes']['uuid'],
                'plex_key'  => $library['@attributes']['key'],
                'server_id' => $server->id,
                'name'      => $library['@attributes']['title'],
                'type'      => $library['@attributes']['type'],
                'updatedAt' => $library['@attributes']['updatedAt'],
                'createdAt' => $library['@attributes']['createdAt'],
                'scannedAt' => $library['@attributes']['scannedAt']
            ));

            $new_library->save();

            //self::getSectionsContent($server, $new_library);

            if(isset($list_libraries['@attributes']))
                break;
        }

        return $libraries_id_array;
    }

    /**
     * @param $server
     * @param $library
     * @return bool
     * @throws Exception
     * @throws FuelException
     * @throws HttpNotFoundException
     * @throws \FuelException
     */
    public static function getSectionsContent($server, $library)
    {
        $curl = Request::forge('http://' . $server->url . ($server->port? ':' . $server->port : '') . '/library/sections/' . $library->plex_key . '/all?X-Plex-Token=' . $server->token, 'curl');
        $curl->execute();

        if($curl->response()->status !== 200)
            return false;

        $section_content = Format::forge($curl->response()->body, 'xml')->to_array();

        if(isset($section_content['Directory']))
            return ['tvshows' => Model_Tvshow::BrowseTvShow($server, $section_content['Directory'], $library)];
        else if(isset($section_content['Video']))
            return ['movies' => Model_Movie::BrowseMovies($server, $section_content['Video'], $library)];
    }
}