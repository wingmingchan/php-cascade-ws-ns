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
use cascade_ws_AOHS as aohs;
use cascade_ws_utility as u;
use cascade_ws_exception as e;

class Plugin extends Property
{
    public function __construct( 
    	\stdClass $p=NULL, 
    	aohs\AssetOperationHandlerService $service=NULL, 
    	$data1=NULL, 
    	$data2=NULL, 
    	$data3=NULL )
    {
        if( isset( $p ) )
        {
            $this->name  = $p->name;
            
            if( isset( $p->parameters->parameter ) )
            {
                $this->processParameters( $p->parameters->parameter );
            }
        }
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function getParameter( $name )
    {
        foreach( $this->parameters as $parameter )
        {
            if( $parameter->getName() == $name )
            {
                return $parameter;
            }
        }
    }
    
    public function getParameters()
    {
        return $this->parameters;
    }
    
    public function hasParameter( $name )
    {
        foreach( $this->parameters as $parameter )
        {
            if( $parameter->getName() == $name )
            {
                return true;
            }
        }
        return false;
    }
    
    public function setParameterValue( $name, $value )
    {
        $parameter = $this->getParameter( $name );
        $parameter->setValue( $value );
        
        return $this;
    }
    
    public function toStdClass()
    {
        $obj       = new \stdClass();
        $obj->name = $this->name;
        $count     = count( $this->parameters );
        
        $obj->parameters = new \stdClass();
        
        if( $count == 0 )
        {
            // nothing
        }
        else if( $count == 1 )
        {
            $obj->parameters->parameter = $this->parameters[0];
        }
        else
        {
            $obj->parameters->parameter = array();
            
            foreach( $this->parameters as $parameter )
            {
                $obj->parameters->parameter[] = $parameter->toStdClass();
            }
        }
        
        return $obj;
    }
    
    private function processParameters( $parameters )
    {
        $this->parameters = array();

        if( !is_array( $parameters ) )
        {
            $parameters = array( $parameters );
        }
        foreach( $parameters as $parameter )
        {
            $this->parameters[] = new Parameter( $parameter );
        }
    }

    private $name;
    private $parameters;
}
?>