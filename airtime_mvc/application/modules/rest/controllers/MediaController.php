<?php 

/**
 * Api module's index controller. You should notice the "Api" Namespace
 */
class Rest_MediaController extends Zend_Controller_Action
{

    public function init()
    {
        Logging::log(__CLASS__.":".__FUNCTION__);
        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
    }

    public function indexAction()
    {
        Logging::log(__CLASS__.":".__FUNCTION__);

        $this->getResponse()->setHttpResponseCode(200)
            ->appendBody("all content");        
    }
    
    public function getAction()
    {
        Logging::log(__CLASS__.":".__FUNCTION__);
        // Get the requested track
        try {
            #Logging::log(var_dump($this->_getAllParams()));
            $id = $this->_getParam("id");
            Logging::log($id);
            // getRequest();
            // getInvokeArgs()
            // _getAllParams()
            // _getParam()
    
            $track = Application_Model_StoredFile::Recall($id);
        
            if (is_null($track)) {
                // if not found, return 404
                $this->getResponse()->setHttpResponseCode(404)
                    ->appendBody("Track $id not found.\n");
            } else {
                $ret = array();
                $ret["id"] = $id;
                $ret["gunid"] = $track->getGunId();
                $ret["file_exists"] = $track->getFileExistsFlag();
                $ret["metadata"] = $track->getMetadata();
                $this->getResponse()
                    ->setHttpResponseCode(200)
                    ->appendBody(json_encode($ret)."\n");
            }
        } catch (Exception $e) {
            var_dump($e);
        }
    }
    
    /**
     * Update an existing media item.
     */
    public function postAction()
    {
        Logging::log(__CLASS__.":".__FUNCTION__);
        echo __CLASS__.":".__FUNCTION__."\n";
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
            $track = Application_Model_StoredFile::Recall($id);
        
            if (is_null($track)) {
                // if not found, return 404
                $this->getResponse()->setHttpResponseCode(404)
                    ->appendBody("Track $id not found.\n");
            } else {
                $track->delete(true);
                $this->getResponse()
                    ->setHttpResponseCode(200);
            }
        } catch (Exception $e) {
            var_dump($e);
        }
    }
    
}
