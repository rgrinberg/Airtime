<?php
/**
 * Api module's index controller. You should notice the "Api" Namespace
 */
class Rest_PlaylistController extends Zend_Controller_Action
{

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
    }
    
    /**
     * Fetch the requested playlist.
     */
    public function getAction()
    {
        Logging::log(__CLASS__.":".__FUNCTION__);
        try {
            $id = $this->_getParam("id");
    
            try {
                $pl = new Application_Model_Playlist($id, null, false);
            } catch (PlaylistNotFoundException $ple) {
                // if not found, return 404
                $this->getResponse()->setHttpResponseCode(404)
                    ->appendBody("Playlist $id not found.\n");
                return;                
            }
            $outPl = array();
            $outPl["name"] = $pl->getName();
            $outPl["description"] = $pl->getDescription();
            $outPl["contents"] = $pl->getContents();
            $contents = $pl->getContents();
                        
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
        $pl = new Application_Model_Playlist();
        echo json_encode($pl->getId());
    }
    
}