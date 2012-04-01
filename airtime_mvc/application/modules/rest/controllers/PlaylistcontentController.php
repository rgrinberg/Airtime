<?php
require_once("MediaController.php");

class Rest_PlaylistcontentController extends Zend_Controller_Action
{
    static $displayColumns = array("DbId" => "id",
            "DbPlaylistId" => "playlist_id",
            "DbFileId" => "file_id",
            "DbPosition" => "position",
            "DbCliplength" => "cliplength",
            "DbCuein" => "cuein",
            "DbCueout" => "cueout",
            "DbFadein" => "fadein",
            "DbFadeout" => "fadeout");
     
    static $updateColumns = array("position", "cuein", "cueout", "fadein", "fadeout");
    
    public static function getRestUrl($p_id, $p_contentId = null)
    {
        global $CC_CONFIG;
        $url = $CC_CONFIG["rest_base_url"]."/playlist/$p_id/content";
        if (!is_null($p_contentId)) {
            $url .= "/$p_contentId";
        }
        return $url;
    }
    
    /**
     * Generate public representation of a media item.
     *
     * @param CcPlaylistcontent $p_playlistContent
     * @return array
     */
    public static function formatData($p_playlistContentItem)
    {
        $content = $p_playlistContentItem->toArray();
        //var_dump($content);
        
        // cut out all data we dont care about
        $result = array_intersect_key($content, self::$displayColumns);
        // rename the keys (this part could be taken care of through propel)
        foreach (self::$displayColumns as $key => $value) {
            $result2[$value] = $result[$key];
        }
        $file = $p_playlistContentItem->getCcFiles();
        if (!is_null($file)) {
            $trackInfo = Rest_MediaController::formatData($file);
            $result2 = array_merge($result2, $trackInfo);
        }
        //$result2["link"] = array("self" => self::getRestUrl($result["DbId"], $result["DbPosition"]));
        return $result2;
    }
    
    public function init()
    {
        Logging::log(__CLASS__.":".__FUNCTION__);
        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
    }

   /**
    * Get a list of all content in the playlist.
    */
    public function indexAction()
    {
        Logging::log(__CLASS__.":".__FUNCTION__);
        $contentId = $this->_getParam("contentid");
        //var_dump($contentId);
        
        // Use dumb routing for the moment - route everything
        // not intended for the index controller to it's specified method.
        $requestType = $this->getRequest()->getMethod();
        if (!(is_null($contentId) && $requestType == "GET")) {
            $methodName = strtolower($requestType)."Action";
            $this->$methodName();
            return;
        } 
        
        $id = $this->_getParam("id");
        //var_dump($this->_getAllParams());
        $params = Rest_RemoveDefaultParams($this->_getAllParams());
        // Set limit
        $limit = isset($params["limit"]) && is_numeric($params["limit"])? $params["limit"] : 100;
        
        // Set filters
        $query = CcPlaylistcontentsQuery::create();
        $query->filterByDbPlaylistId($id);
        $query->limit($limit);
        $query->joinWith("CcFiles");
        foreach ($params as $key => $value) {
            // map public key to internal key
            if ($internalKey = array_search($key, self::$displayColumns)) {
                $methodName = "filterBy$internalKey";
                $query->$methodName($value);
            }
        }
        
        // execute the query
        $content = $query->find();
        
        // format the results
        $result = array();
        foreach ($content as $item) {
            $result[] = self::formatData($item);
        }
        
        // send result
        $this->getResponse()->setHttpResponseCode(200)
            ->appendBody(json_encode($result)."\n");
    }
    
    /**
     * Fetch the requested playlist content item.
     */
    public function getAction()
    {
        Logging::log(__CLASS__.":".__FUNCTION__);
        //var_dump($this->_getAllParams());
        try {
            $contentId = $this->_getParam("contentid");
            $pl = CcPlaylistcontentsQuery::create()
                ->filterByDbId($contentId)
                ->findOne();

            if (is_null($pl)) {
                // if not found, return 404
                $this->getResponse()->setHttpResponseCode(404)
                    ->appendBody("Playlist content item $contentId not found.\n");
                return;                
            }
            $outPl = self::formatData($pl);
                        
            $this->getResponse()
                ->setHttpResponseCode(200)
                ->appendBody(json_encode($outPl)."\n");
        } catch (Exception $e) {
            echo $e;
        }
    }

    /**
     * Add content to a playlist.
     * 
     * To insert:
     * - position
     * - file ID
     * - playlist ID
     * 
     */
    public function postAction()
    {
        Logging::log(__CLASS__.":".__FUNCTION__);
        
        // Check param
        $id = $this->_getParam("id");
        $contentId = $this->_getParam("contentid");
        if (empty($id)) {
            // if not found, return 404
            $this->getResponse()->setHttpResponseCode(404)
                ->appendBody("Please specify an ID.\n");
            return;
        }
        
        // Find 
        $item = CcPlaylistcontentsQuery::create()->findOneByDbId($contentId);
        if (is_null($item)) {
            // if not found, return 404
            $this->getResponse()->setHttpResponseCode(404)
                ->appendBody("Playlist content item $contentId not found.\n");
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
                    $item->$methodName($value);
                }
            } else {
                $this->getResponse()->setHttpResponseCode(404)
                    ->appendBody("Not allowed to update field '$key'.\n");
                return;
            }
        }
        $item->save();
        
        // Send updated media info
        $item = self::formatData($item);
        $this->getResponse()
            ->setHttpResponseCode(200)
            ->appendBody(json_encode($item)."\n");        
    }
    
    /**
     * Update an item that already exists.
     */
    public function putAction()
    {
        Logging::log(__CLASS__.":".__FUNCTION__);
    }
    
    /**
     * Delete an item from a playlist.
     */
    public function deleteAction()
    {
        Logging::log(__CLASS__.":".__FUNCTION__);
        try {
            $id = $this->_getParam("id");
            $contentId = $this->_getParam("contentid");
            
            $item = CcPlaylistcontentsQuery::create()
                ->filterByDbId($contentId)
                ->findOne();
        
            if (is_null($item)) {
                // if not found, return 404
                $this->getResponse()->setHttpResponseCode(404)
                    ->appendBody("Playlist content item $contentId not found.\n");
            } else {
                $playlist = new Application_Model_Playlist($id);
                $playlist->delAudioClips(array($contentId));
                $this->getResponse()
                    ->setHttpResponseCode(200);
            }
        } catch (Exception $e) {
            var_dump($e);
        }
     }
    
}