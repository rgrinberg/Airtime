<?php 

// TODO list:
// Create playlistcontent functions
// Be able to import other controllers
//
// Creating an item should be done in POST
// Updating an item should be done in POST
// Dont use PUT
// Add offset to searches
//

/** 
 * Module's bootstrap file. 
 * Notice the bootstrap class' name is "Modulename_"Bootstrap. 
 */  
function Rest_RemoveDefaultParams($p_params)
{
    $outParams = array();
    foreach ($p_params as $key => $value) {
        if (!in_array($key, array("module", "controller", "action", "id"))) {
            $outParams[$key] = $value;
        }
    }
    return $outParams;
}

class Rest_Bootstrap extends Zend_Application_Module_Bootstrap  
{  
    protected function _bootstrap()  
    {  
        set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ ."/controllers");
        Logging::log("Rest_Bootstrap");
        global $CC_CONFIG;
        if (isset($CC_CONFIG['baseUrl'])){
            $serverName = $CC_CONFIG['baseUrl'];
        } else {
            $serverName = $_SERVER['SERVER_NAME'];
        }
        
        if (isset($CC_CONFIG['basePort'])){
            $serverPort = $CC_CONFIG['basePort'];
        } else {
            $serverPort = $_SERVER['SERVER_PORT'];
        }
        
        $CC_CONFIG["rest_base_url"] = "http://$serverName:$serverPort"; 
    }  

}