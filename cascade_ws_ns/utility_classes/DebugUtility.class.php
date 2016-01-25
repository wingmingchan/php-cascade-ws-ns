<?php
/**
  * Author: Wing Ming Chan
  * Copyright (c) 2014 Wing Ming Chan <chanw@upstate.edu>
  * MIT Licensed
  * Modification history:
  * 5/28/2015 Added namespaces.
  * 7/16/2014 Class created.
 */
namespace cascade_ws_utility; 

class DebugUtility
{
    public static function dump( $var )
    {
    	self::getCallingInfo( $class, $line );
        echo $class . "::" . $line . ": " . BR . S_PRE;
        var_dump( $var );
        echo E_PRE . HR;
    }
    
    private static function getCallingInfo( &$class, &$line ) 
    {
    	//get the trace
    	$trace = debug_backtrace();
    	
    	echo S_PRE;
    	//var_dump( $trace );
    	echo E_PRE;

    	// Get the class that is asking for who awoke it
    	$class_temp = $trace[1]['class'];
    	$line       = $trace[1]['line'];

    	// +1 to i cos we have to account for calling this function
    	for ( $i = 1; $i < count( $trace ); $i++ ) 
    	{
        	if ( isset( $trace[ $i ] ) ) // is it set?
        	{
             	if ( $class_temp != $trace[ $i ][ 'class' ] ) // is it a different class
             	{
                 	$class_temp = $trace[ $i ][ 'class' ];
                 	break;
                }
            }
    	}
    	$class = $class_temp;
	}
	
    public static function out( $msg )
    {
    	self::getCallingInfo( $class, $line );
        echo $class . "::" . $line . ": " . $msg . BR;
    }
}