<?php 
/**
  * Author: Wing Ming Chan
  * Copyright (c) 2014 Wing Ming Chan <chanw@upstate.edu>
  * MIT Licensed
  * Modification history:
  * 5/28/2015 Added namespaces.
  * 7/18/2014 Added isXmlIdentical.
 */
namespace cascade_ws_utility; 

class XmlUtility
{
	public static function isXmlIdentical( \SimpleXMLElement $xml1, \SimpleXMLElement $xml2 )
	{
		return $xml1->asXML() == $xml2->asXML();
	}
	
    public static function replaceBrackets( $string )
    {
        $string = str_replace( '<', '&lt;', $string );
        $string = str_replace( '>', '&gt;', $string );
        
        return $string;
    }
}
?>
