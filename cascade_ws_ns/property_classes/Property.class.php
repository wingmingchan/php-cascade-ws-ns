<?php 
/**
  * Author: Wing Ming Chan
  * Copyright (c) 2014 Wing Ming Chan <chanw@upstate.edu>
  * MIT Licensed
  * Modification history:
  * 5/28/2015 Added namespaces.
 */
namespace cascade_ws_property;

use cascade_ws_constants as c;
use cascade_ws_AOHS      as aohs;
use cascade_ws_utility   as u;
use cascade_ws_exception as e;

/**
 * Property
 * The ancestor class of all property classes.
 *
 * @link http://www.upstate.edu/cascade-admin/projects/web-services/oop/classes/property-classes/property.php
 */
abstract class Property
{
    public abstract function __construct(
        \stdClass $obj=NULL, 
        aohs\AssetOperationHandlerService $service=NULL, 
        $data1=NULL, 
        $data2=NULL, 
        $data3=NULL );
        
    public abstract function toStdClass();
}
?>
