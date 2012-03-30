<?php

class Rest_PlaylistController extends Zend_Controller_Action
{
    public static function getRestUrl($p_id = null)
    {
        global $CC_CONFIG;
        $base = $CC_CONFIG["rest_base_url"]."playlist";
        if (!is_null($p_id)) {
            $base .= "/$p_id";
        }
        return $base;
    }
    
    private static function getUrlRef($p_url) 
    {
        $outPl["link"] = array("self" => $this->getRestUrl($pl->getDbId()));
        
    }
    
    private static function PlaylistToArray($p_propelPlaylist)
    {
        $outPl = $p_propelPlaylist->toArray();
        $outPl["link"] = array("href" => Rest_PlaylistController::getRestUrl($p_propelPlaylist->getDbId()), "rel"=>"self");
        $outPl["contents"] = $p_propelPlaylist->getCcPlaylistcontentss()->toArray();
        // add in self ref URLs to the contents
//         foreach ($outPl["contents"] as $key => $mediaItem) {
//             $outPl["contents"][$key]["link"] = Rest_MediaController::getRestUrl();
//                 array("href" => Rest_MediaController::getRestUrl($mediaItem["DbId"]),
//                       "rel" => "self");
//         }
        return $outPl;
    }
    
    
    public function init()
    {
        Logging::log(__CLASS__.":".__FUNCTION__);
        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
    }

   /**
    * Get a list of all playlists.
    */
    public function indexAction()
    {
        Logging::log(__CLASS__.":".__FUNCTION__);
        $args = Rest_RemoveDefaultParams($this->_getAllParams());
        
        $playlists = CcPlaylistQuery::create()
            ->filterByDbName("%".$args["name"]."%")
            ->find();
        foreach ($playlists as $pl) {
            var_dump($pl->toArray());
            echo "\n";
        }                
    }
    
    /**
     * Fetch the requested playlist.
     */
    public function getAction()
    {
        Logging::log(__CLASS__.":".__FUNCTION__);
        try {
            $id = $this->_getParam("id");
    
            $pl = CcPlaylistQuery::create()
                ->filterByDbId($id)
                ->findOne();

            if (is_null($pl)) {
                // if not found, return 404
                $this->getResponse()->setHttpResponseCode(404)
                    ->appendBody("Playlist $id not found.\n");
                return;                
            }
            $outPl = Rest_PlaylistController::PlaylistToArray($pl);
                        
            $this->getResponse()
                ->setHttpResponseCode(200)
                ->appendBody(json_encode($outPl)."\n");
        } catch (Exception $e) {
            echo $e;
        }
    }

    /**
     * Create a new playlist.
     */
    public function putAction()
    {
        Logging::log(__CLASS__.":".__FUNCTION__);
        var_dump($this->_getAllParams());
        $pl = new Application_Model_Playlist();
        $pl->setName($this->getParam("name"));
        
        $out = array();
        $out["id"] = $pl->getId();
        $out["link"] = array("href" => $this->getBaseUrl."/".$pl->getId(), "rel" => "self");
        
        $this->getResponse()
            ->setHttpResponseCode(200)
            ->appendBody(json_encode($out)."\n");
    }
    
}