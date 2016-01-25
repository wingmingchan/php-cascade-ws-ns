<?php
/**
  * Author: Wing Ming Chan
  * Copyright (c) 2014 Wing Ming Chan <chanw@upstate.edu>
  * MIT Licensed
  * Modification history:
  * 5/28/2015 Added namespaces.
  * 9/8/2014 Added getParentPathFromPath.
  * 8/13/2014 Added removeSiteNameFromPath.
  * 8/9/2014 Added getFullyQualifiedIdentifierWithoutPositions.
  * 7/29/2014 Added getMethodName.
  * 7/11/2014 Added getNameFromPath.
  * 5/22/2014 Fixed some bugs.
 */
namespace cascade_ws_utility; 

class StringUtility
{
    public static function endsWith( $haystack, $needle )
    {
        return $needle === "" || substr( $haystack, -strlen( $needle ) ) === $needle;
    }
    
    public static function getExplodedStringArray( $delimiter, $string )
    {
        $temp   = array();
        $tokens = explode( $delimiter, $string );
        
        if( count( $tokens ) > 0 )
        {
            foreach( $tokens as $token )
            {
                if( trim( $token, " \n\t" ) != "" && trim( $token, " \n\t" ) != "<?xml version=\"1.0\"?>" )
                {
                    $temp[] = trim( $token, " \n\t" );
                }
            }
        }
        return $temp;
    }
    
    public static function getFullyQualifiedIdentifierWithoutPositions( $identifier )
    {
    	$temp         = self::getExplodedStringArray( ";", $identifier );
    	$result_array = array();
    	
    	if( count( $temp ) > 0 )
    	{
    		foreach( $temp as $part )
    		{
    			if( !is_numeric(  $part ) )
    			{
    				$result_array[] = $part;
    			}
    		}
    	}
    	return implode( ";", $result_array );
    }
    
    public static function getMethodName( $property_name )
    {
        return 'get' . ucwords( $property_name );
    }
    
    public static function getNameFromPath( $path )
    {
    	$array = StringUtility::getExplodedStringArray( '/', $path );
    	$count = count( $array );
    	
    	if( $count > 0 )
    		return $array[ $count - 1 ]; // last element
    		
    	return ""; // empty string
    }
    
    public static function getParentPathFromPath( $path )
    {
    	$array = StringUtility::getExplodedStringArray( '/', $path );
    	$count = count( $array );
    	
    	if( $count == 1 )
    		return "/";
    	else if( $count > 1 )
    	{
			return
				implode( '/', array_slice( $array, 0, count( $array ) - 1 ) );    	
    	}
    		
    	return ""; // empty string
    }
    
    public static function removeSiteNameFromPath( $path )
    {
    	if( strpos( $path, ":" ) !== false )
			$path = substr( $path, strpos( $path, ":" ) + 1 );
		return $path;
    }
    
    public static function startsWith( $haystack, $needle )
    {
        return $needle === "" || strpos( $haystack, $needle ) === 0;
    }
}
?>