<?php
require_once("PlaylistcontentController.php");

class Rest_PlaylistController extends Zend_Controller_Action
{
    static $displayColumns = array("DbId" => "id",
            "DbName" => "name",
            "DbMtime" => "modified_time",
            "DbDescription" => "description",
            "DbLength" => "length");
     
    static $updateColumns = array("name", "description");
    
    public static function getRestUrl($p_id = null)
    {
        global $CC_CONFIG;
        $url = $CC_CONFIG["rest_base_url"];
        $router = Zend_Controller_Front::getInstance()->getRouter();
        $url .= $router->assemble(array($p_id));
        return $url;
    }
    
    /**
     * Generate public representation of a media item.
     * Used when converting from internal DB format to public format.
     * 
     * @param CcPlaylist $p_playlist
     * @return array
     */
    public static function formatData($p_playlist, $p_withContents = false)
    {
        
        //var_dump($p_playlist);
        $pl_array = $p_playlist->toArray();
        // cut out all data we dont care about
        $result = array_intersect_key($pl_array, self::$displayColumns);
        //var_dump($result);
        // rename the keys (this part could be taken care of through propel)
        foreach (self::$displayColumns as $key => $value) {
            $result2[$value] = $result[$key];
        }
        if ($p_withContents) {
            $contents = $p_playlist->getCcPlaylistcontentss();
            foreach ($contents as $item) {
                $result2["contents"][] = Rest_PlaylistcontentController::formatData($item);
            }
        }
        //var_dump($result2);
        
        $result2["link"] = array("self" => self::getRestUrl($result["DbId"]));
        return $result2;
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
        $params = Rest_RemoveDefaultParams($this->_getAllParams());
        
        // Set limit
        $limit = isset($params["limit"]) && is_numeric($params["limit"])? $params["limit"] : 10;
        
        // Set filters
        $query = CcPlaylistQuery::create();
        $query->limit($limit);
        foreach ($params as $key => $value) {
            // map public key to internal key
            if ($internalKey = array_search($key, self::$displayColumns)) {
                $methodName = "filterBy$internalKey";
                $query->$methodName($value);
            }
        }
        
        // Set order by
        if (isset($params["orderby"])) {
            if ($internalKey = array_search($key, self::$displayColumns)) {
                $methodName = "orderBy$internalKey";
                $query->$methodName($value);
            }
        }
        
        // execute the query
        $playlists = $query->find();
        
        // format the results
        $result = array();
        foreach ($playlists as $item) {
            $result[] = self::formatData($item->toArray());
        }
        
        if (isset($params["orderby"]) && ($params["orderby"] == "random")) {
            shuffle($result);
        }
        
        // send result
        $this->getResponse()->setHttpResponseCode(200)
            ->appendBody(json_encode($result)."\n");
    }
    
    /**
     * Fetch the requested playlist.
     */
    public function getAction()
    {
        Logging::log(__CLASS__.":".__FUNCTION__);
        //var_dump($this->_getAllParams());
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
            $outPl = self::formatData($pl, true);
                        
            $this->getResponse()
                ->setHttpResponseCode(200)
                ->appendBody(json_encode($outPl)."\n");
        } catch (Exception $e) {
            echo $e;
        }
    }

    /**
     * Update an existing playlist.
     */
    public function postAction()
    {
        Logging::log(__CLASS__.":".__FUNCTION__);
        
        // Check param
        $id = $this->_getParam("id");
        if (empty($id)) {
            // if not found, return 404
            $this->getResponse()->setHttpResponseCode(404)
                ->appendBody("Please specify an ID.\n");
            return;
        }
        
        // Find 
        $pl = CcPlaylistQuery::create()->findOneByDbId($id);
        if (is_null($pl)) {
            // if not found, return 404
            $this->getResponse()->setHttpResponseCode(404)
                ->appendBody("Playlist $id not found.\n");
            return;
        }
        
        // Update values
        $params = Rest_RemoveDefaultParams($this->_getAllParams());
        foreach ($params as $key => $value) {
            // check if this value is allowed to be updated
            if (in_array($key, self::$updateColumns)) {
                // map public key to internal key
                if ($internalKey = array_search($key, self::$displayColumns)) {
                    $methodName = "set$internalKey";
                    $pl->$methodName($value);
                }
            } else {
                $this->getResponse()->setHttpResponseCode(404)
                    ->appendBody("Not allowed to update field '$key'.\n");
                return;
            }
        }
        $pl->save();
        
        // Send updated media info
        $pl = self::formatData($pl, true);
        $this->getResponse()
            ->setHttpResponseCode(200)
            ->appendBody(json_encode($pl)."\n");        
    }
    
    /**
     * Create a new playlist.
     */
    public function putAction()
    {
        Logging::log(__CLASS__.":".__FUNCTION__);
        //var_dump($this->_getAllParams());
        $name = $this->_getParam("name");
        if (empty($name)) {
            
        }
        $pl = new Application_Model_Playlist();
        $pl->setName($this->getParam("name"));
        
        $out = array();
        $out["id"] = $pl->getId();
        $out["link"] = array("href" => $this->getBaseUrl."/".$pl->getId(), "rel" => "self");
        
        $this->getResponse()
            ->setHttpResponseCode(200)
            ->appendBody(json_encode($out)."\n");
    }
    
    /**
     * Delete a playlist.
     */
    public function deleteAction()
    {
        Logging::log(__CLASS__.":".__FUNCTION__);
        try {
            $id = $this->_getParam("id");
            // We need to use the Airtime API for this because we
            // do not remove file records from the database.
            $pl = CcPlaylistQuery::create()->findOneByDbId($id);
        
            if (is_null($pl)) {
                // if not found, return 404
                $this->getResponse()->setHttpResponseCode(404)
                    ->appendBody("Playlist $id not found.\n");
            } else {
                $pl->delete();
                $this->getResponse()
                    ->setHttpResponseCode(200);
            }
        } catch (Exception $e) {
            var_dump($e);
        }
    }
    
}