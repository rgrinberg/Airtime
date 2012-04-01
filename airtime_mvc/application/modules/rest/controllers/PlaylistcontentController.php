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
    
    public static function getRestUrl($p_id = null, $p_position = null)
    {
        global $CC_CONFIG;
        $url = $CC_CONFIG["rest_base_url"];
        $router = Zend_Controller_Front::getInstance()->getRouter();
        $url .= $router->assemble(array($p_id, $p_position));
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
        $trackInfo = Rest_MediaController::formatData($p_playlistContentItem->getCcFiles());
        $result2 = array_merge($result2, $trackInfo);
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
     * Add a clip to a playlist.
     */
    public function postAction()
    {
        Logging::log(__CLASS__.":".__FUNCTION__);
        
        // Check param
//         $id = $this->_getParam("id");
//         if (empty($id)) {
//             // if not found, return 404
//             $this->getResponse()->setHttpResponseCode(404)
//                 ->appendBody("Please specify an ID.\n");
//             return;
//         }
        
//         // Find 
//         $pl = CcPlaylistQuery::create()->findOneByDbId($id);
//         if (is_null($pl)) {
//             // if not found, return 404
//             $this->getResponse()->setHttpResponseCode(404)
//                 ->appendBody("Playlist $id not found.\n");
//             return;
//         }
        
//         // Update values
//         $params = Rest_RemoveDefaultParams($this->_getAllParams());
//         foreach ($params as $key => $value) {
//             // check if this value is allowed to be updated
//             if (in_array($key, self::$updateColumns)) {
//                 // map public key to internal key
//                 if ($internalKey = array_search($key, self::$displayColumns)) {
//                     $methodName = "set$internalKey";
//                     $pl->$methodName($value);
//                 }
//             } else {
//                 $this->getResponse()->setHttpResponseCode(404)
//                     ->appendBody("Not allowed to update field '$key'.\n");
//                 return;
//             }
//         }
//         $pl->save();
        
//         // Send updated media info
//         $pl = $pl->toArray();
//         $pl = self::formatData($pl);
//         $this->getResponse()
//             ->setHttpResponseCode(200)
//             ->appendBody(json_encode($pl)."\n");        
    }
    
    /**
     * Create a new playlist.
     */
    public function putAction()
    {
        Logging::log(__CLASS__.":".__FUNCTION__);
        //var_dump($this->_getAllParams());
//         $name = $this->_getParam("name");
//         if (empty($name)) {
            
//         }
//         $pl = new Application_Model_Playlist();
//         $pl->setName($this->getParam("name"));
        
//         $out = array();
//         $out["id"] = $pl->getId();
//         $out["link"] = array("href" => $this->getBaseUrl."/".$pl->getId(), "rel" => "self");
        
//         $this->getResponse()
//             ->setHttpResponseCode(200)
//             ->appendBody(json_encode($out)."\n");
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