<?php 

class Rest_MediaController extends Zend_Controller_Action
{

    static $displayColumns = array("DbId" => "id",
                                   "DbGunid" => "gunid",
                                   "DbMime" => "mime",
                                   "DbFtype" => "type",
                                   "DbMtime" => "modified_time",
                                   "DbTrackTitle" => "title",
                                   "DbArtistName" => "artist",
                                   "DbAlbumTitle" => "album",
                                   "DbGenre" => "genre",
                                   "DbYear" => "year",
                                   "DbTrackNumber" => "track_number",
                                   "DbFileExists" => "file_exists");
                                   
    static $updateColumns = array("title", "artist", "album", "genre", "year", "track_number");
    
    /**
     * Get the REST URL for a media item.
     * 
     * @param string $p_id
     * @return string
     */
    public static function getRestUrl($p_id = null)
    {
        global $CC_CONFIG;
        $url = $CC_CONFIG["rest_base_url"]."/media";
        if (!is_null($p_id)) {
            $url .= "/$p_id";
        }
        return $url;
    }
    
    /**
     * Generate public representation of a media item.
     * 
     * @param CcFiles $p_file
     * @return array
     */
    public static function formatData($p_file)
    {
        $fileArray = $p_file->toArray();
        // cut out all data we dont care about
        $result = array_intersect_key($fileArray, self::$displayColumns);
        // rename the keys (this part could be taken care of through propel)
        foreach (self::$displayColumns as $key => $value) {
            $result2[$value] = $result[$key];
        }
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
     * Search for media items.
     * 
     * Available options:
     *   limit=<INT>
     *   orderby=<COLUMN_NAME>|random
     *   <COLUMN_NAME>=<VALUE> (You can use '%' as a wildcard in the value field)
     *   
     */
    public function indexAction()
    {
        Logging::log(__CLASS__.":".__FUNCTION__);
        $params = Rest_RemoveDefaultParams($this->_getAllParams());
        
        // Set limit
        $limit = isset($params["limit"]) && is_numeric($params["limit"])? $params["limit"] : 10;

        // Set filters
        $query = CcFilesQuery::create();
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
        $media = $query->find();
        
        // format the results
        $result = array();
        foreach ($media as $item) {
            $result[] = self::formatData($item);
        }
        
        if (isset($params["orderby"]) && ($params["orderby"] == "random")) {
            shuffle($result);
        }
        
        // send result
        $this->getResponse()->setHttpResponseCode(200)
            ->appendBody(json_encode($result)."\n");        
    }

    /**
     * Get a single media item.
     */
    public function getAction()
    {
        Logging::log(__CLASS__.":".__FUNCTION__);
        try {
            $id = $this->_getParam("id");
            $media = CcFilesQuery::create()->filterByDbId($id)->findOne();
        
            if (is_null($media)) {
                // if not found, return 404
                $this->getResponse()->setHttpResponseCode(404)
                    ->appendBody("Track $id not found.\n");
            } else {
                $media = self::formatData($media);
                $this->getResponse()
                    ->setHttpResponseCode(200)
                    ->appendBody(json_encode($media)."\n");
            }
        } catch (Exception $e) {
            var_dump($e);
        }
    }
    
    /**
     * Update the metadata for an existing media item.
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
        
        // Find media
        $media = CcFilesQuery::create()->findOneByDbId($id);
        if (is_null($media)) {
            // if not found, return 404
            $this->getResponse()->setHttpResponseCode(404)
                ->appendBody("Track $id not found.\n");
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
                    $media->$methodName($value);
                }
            } else {
                $this->getResponse()->setHttpResponseCode(404)
                    ->appendBody("Not allowed to update field '$key'.\n");
                return;
            }
        }
        $media->save();
        
        // Send updated media info
        $media = self::formatData($media);
        $this->getResponse()
            ->setHttpResponseCode(200)
            ->appendBody(json_encode($media)."\n");
    }
    
    /**
     * Create a new media item.
     */
    public function putAction()
    {
        Logging::log(__CLASS__.":".__FUNCTION__);
        var_dump($_FILES["file"]);
        //var_dump($this->_getAllParams());
        $upload_dir = ini_get("upload_tmp_dir");
        $tempFilePath = Application_Model_StoredFile::uploadFile($upload_dir);
        $tempFileName = basename($tempFilePath);
        
        $fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : '';
        $result = Application_Model_StoredFile::copyFileToStor($upload_dir, $fileName, $tempFileName);
    }
    
    /**
     * Delete the media file from disk.
     */
    public function deleteAction()
    {
        Logging::log(__CLASS__.":".__FUNCTION__);
        try {
            $id = $this->_getParam("id");
            // We need to use the Airtime API for this because we 
            // do not remove file records from the database.
            $media = Application_Model_StoredFile::Recall($id);
        
            if (is_null($media)) {
                // if not found, return 404
                $this->getResponse()->setHttpResponseCode(404)
                    ->appendBody("Track $id not found.\n");
            } else {
                $media->delete(true);
                $this->getResponse()
                    ->setHttpResponseCode(200);
            }
        } catch (Exception $e) {
            var_dump($e);
        }
    }
    
}
