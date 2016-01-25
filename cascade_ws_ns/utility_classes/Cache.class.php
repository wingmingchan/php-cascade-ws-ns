<?php
/**
  * Author: Wing Ming Chan
  * Copyright (c) 2014 Wing Ming Chan <chanw@upstate.edu>
  * MIT Licensed
  * Modification history:
  * 5/28/2015 Added namespaces.
  * 7/25/2014 File created.
 */
namespace cascade_ws_utility;

use cascade_ws_constants as c;
use cascade_ws_AOHS as aohs;
use cascade_ws_exception as e;
use cascade_ws_property as p;

class Cache
{
    const DEBUG = false;
    const DUMP  = false;
    
    public function clearCache()
    {
    	$this->cache = array();
    }

    public function retrieveAsset( p\Child $child )
    {
    	$id = $child->getId();
    	
    	if( !isset( $this->cache[ $id ] ) )
    		$this->cache[ $id ] = $child->getAsset( self::$service );
    	return $this->cache[ $id ];
    }
    
    public static function getInstance( aohs\AssetOperationHandlerService $service )
    {
    	self::$service = $service;
    	
    	if( empty( self::$instance ) )
    	{
    		self::$instance = new Cache( $service );
    	}
    	return self::$instance;
    }
    
    private function __construct() { }
    
    private $cache = array();
    private static $instance;
    private static $service;
}
?>