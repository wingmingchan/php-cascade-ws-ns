<?php
/**
  * Author: Wing Ming Chan
  * Copyright (c) 2016 Wing Ming Chan <chanw@upstate.edu>
  * MIT Licensed
  * Modification history:
  * 8/14/2016 Class being tested with extra comment.
  * 8/13/2016 Methods added.
  * 8/12/2016 File created.
 */
namespace cascade_ws_utility;

use cascade_ws_asset     as a;
use cascade_ws_property  as p;
use cascade_ws_constants as c;

/**
<p>This class can be used to expose class and method information of any class, using reflection. 
All methods provided in this class are static. 
For practical purposes, use only those <code>show</code> methods. The <code>get</code>
methods are used to generate documentation pages in cascade-admin.</p>
*/
class ReflectionUtility
{
/**
Returns the class information given right before the class definition.
@param mixed $obj A string (the class name) or an object
<documentation><description>Returns the class information given right before the class definition.</description>
<example>$info = u\ReflectionUtility::getClassInfo( "cascade_ws_utility\ReflectionUtility" );</example></documentation>
*/
    public static function getClassInfo( $obj )/* : string */
    {
        $class_info = "";
        $r = new \ReflectionClass( $obj );
        $class_info = $r->getDocComment();
        $class_info = str_replace( "*/", "", str_replace( "/**", "", $class_info ) );
        
        return $class_info;
    }
    
/**
Returns the class name.
@param mixed $obj A string (the class name) or an object
@return string The class name
<documentation><description>Returns the class name.</description>
<example>echo u\ReflectionUtility::getClassName( $service );</example></documentation>
*/
    public static function getClassName( $obj )/* : string */
    {
        $r = new \ReflectionClass( $obj );
        return $r->getName();
    }
    
/**
Returns the named method.
@param mixed $obj A string (the class name) or an object
@return ReflectionMethod The method
<documentation><description>Returns the named method.</description>
<example>$method = u\ReflectionUtility::getMethod( $service, "getType" );</example></documentation>
*/
    public static function getMethod( $obj,/* string */$method_name )/* : \ReflectionMethod */
    {
        $r = new \ReflectionClass( $obj );
        return $r->getMethod( $method_name );
    }
    
/**
Returns all the textual information of a method provided right before the method definition.
@param ReflectionMethod $method The method object
@return string The info string
<documentation><description>Returns all the textual information of a method provided right before the method definition.</description>
<example>$info = u\ReflectionUtility::getMethodInfo( $method );</example></documentation>
*/
    public static function getMethodInfo( \ReflectionMethod $method )/* : string */
    {
        $method_info = self::getMethodSignature( $method ) . "\n";
        $method_info .= $method->getDocComment();
        
        return $method_info;
    }
        
/**
Returns the all the textual information of a method.
@param mixed $obj A string (the class name) or an object
@param string $method_name The method name
@return string The info string
<documentation><description>Returns the all the textual information of a method.</description>
<example>$info = u\ReflectionUtility::getMethodInfoByName( $service, "getType" );</example></documentation>
*/
    public static function getMethodInfoByName( $obj,/* string */$method_name )/* : string */
    {
        $r        = new \ReflectionClass( $obj );
        $method   = $r->getMethod( $method_name );
        
        return self::getMethodInfo( $method );
    }
    
/**
Returns an array of methods of the class.
@param mixed $obj A string (the class name) or an object
@return array The array containing all methods
<documentation><description>Returns an array of methods of the class.</description>
<example>$methods = u\ReflectionUtility::getMethods( $service );</example></documentation>
*/
    public static function getMethods( $obj )/* : array */
    {
        $r = new \ReflectionClass( $obj );
        return $r->getMethods();
    }
    
/**
Returns the signature of a method.
@param ReflectionMethod $method The method object
@return string The info string
<documentation><description>Returns the information of a method.</description>
<example>echo u\ReflectionUtility::getMethodSignature( $method );</example></documentation>
*/
    public static function getMethodSignature( \ReflectionMethod $method )/* : string */
    {
        $method_info = "";
        $class       = $method->getDeclaringClass();
        $return_type = ( method_exists( $method, "getReturnType" )  ? 
            $method->getReturnType() : "" );
        
        $method_info .=
            implode( ' ', \Reflection::getModifierNames( $method->getModifiers() ) ) .
            ( $return_type != "" ? " " . $return_type . " " : "" ) .
            $method_info . " " .
            $method->getDeclaringClass()->getName() . "::" .
            $method->getName() . "(";
        
        $num_of_params = $method->getNumberOfParameters();
        
        if( $num_of_params )
        {
            $params      = $method->getParameters();
            $count       = 1;
            
            foreach( $params as $param )
            {
                $param_type = ( method_exists( $param, "getType" )  ? 
                    $param->getType() : "" );
                    
                $method_info .=
                    ( $param_type != ""  ? $param_type : "" ). 
                    " " .
                    "$" . $param->getName();
                
                if( $param->isOptional() )
                {    
                    $default_value = $param->getDefaultValue();
                
                    if( isset( $default_value ) )
                    {
                        if( $default_value == 1 && $param_type == "bool" )
                        {
                            $method_info .= " = true";
                        }
                        elseif( $default_value == 0 &&  $param_type == "bool" && $default_value != "" )
                        {
                            $method_info .= " = false";
                        }
                        elseif( $default_value == "" )
                        {
                            if( $default_value === "" )
                                $method_info .= " = \"\"";
                            else
                                $method_info .= " = false";
                        }
                        else
                        {
                            $method_info .= " = $default_value";
                        }
                    }
                    else
                    {
                        $method_info .= ' = NULL';
                    }
                }
                    
                if( $count < $num_of_params )
                {
                    $method_info .= ", ";
                }
                $count++;
            }
            $method_info .= " ";
        }
        $method_info .= ")";
        
        return trim( $method_info );
    }
    
/**
Returns the information of a method.
@param mixed $obj A string (the class name) or an object
@param string $method_name The method name
@return string The info string
<documentation><description>Returns the information of a method.</description>
<example>$info = u\ReflectionUtility::getMethodSignatureByName( $service, "getType" );</example></documentation>
*/
    public static function getMethodSignatureByName( $obj,/* string */$method_name )/* : string */
    {
        $r        = new \ReflectionClass( $obj );
        $method   = $r->getMethod( $method_name );
        
        return self::getMethodSignature( $method );
    }
    
/**
Returns an unordered list of information of methods defined in the class.
@param mixed $obj A string (the class name) or an object
@return string The string containing information of all methods
<documentation><description>Returns an unordered list of information of methods defined in the class.</description>
<example>echo u\ReflectionUtility::getMethodSignatures( $service );</example></documentation>
*/
    public static function getMethodSignatures( $obj )/* : string */
    {
        $methods = self::getMethods( $obj );
        $method_info = S_UL;
        
        foreach( $methods as $method )
        {
            $method_info .= S_LI . S_CODE . self::getMethodSignature( $method ) . E_CODE . E_LI;
        }
        
        $method_info .= E_UL;
        
        return $method_info;
    }
    
/**
Displays the class information given right before the class definition.
@param mixed $obj A string (the class name) or an object
<documentation><description>Displays an unordered list of information of methods defined in the class.</description>
<example>u\ReflectionUtility::showClassInfo( "cascade_ws_utility\ReflectionUtility" );</example></documentation>
*/
    public static function showClassInfo( $obj )
    {
        echo self::getClassInfo( $obj );
    }
    
/**
Displays the description of a method.
@param mixed $obj A string (the class name) or an object
@param string $method_name The method name
<documentation><description>Displays the description of a method.</description>
<example>u\ReflectionUtility::showMethodDescription( "cascade_ws_utility\ReflectionUtility", "showMethod" );</example></documentation>
*/
    public static function showMethodDescription( $obj,/* string */$method_name,/* bool */$with_hr=false )
    {
        echo self::getXmlValue( $obj, $method_name, "description", S_P, E_P );
        if( $with_hr ) echo HR;
    }
    
/**
Displays an example of how to use a method.
@param mixed $obj A string (the class name) or an object
@param string $method_name The method name
<documentation><description>Displays an example of how to use a method.</description>
<example>u\ReflectionUtility::showMethodExample( "cascade_ws_utility\ReflectionUtility", "showMethod" );</example></documentation>
*/
    public static function showMethodExample( $obj,/* string */$method_name,/* bool */$with_hr=false )
    {
        echo self::getXmlValue( $obj, $method_name, "example", S_PRE, E_PRE );
        if( $with_hr ) echo HR;
    }

/**
Displays all textual information give right before the definition of a method.
@param mixed $obj A string (the class name) or an object
@param string $method_name The method name
<documentation><description>Displays all textual information give right before the definition of a method.</description>
<example>u\ReflectionUtility::showMethodInfo( "cascade_ws_utility\ReflectionUtility", "showMethod" );</example></documentation>
*/
    public static function showMethodInfo( $obj,/* string */$method_name,/* bool */$with_hr=false )
    {
        echo S_PRE,
            self::getMethodInfo( self::getMethod( $obj, $method_name ) ),
            E_PRE;
        if( $with_hr ) echo HR;
    }
    
/**
Displays an unordered list of information of methods defined in the class.
@param mixed $obj A string (the class name) or an object
<documentation><description>Displays an unordered list of information of methods defined in the class.</description>
<example>u\ReflectionUtility::showMethodSignatures( "cascade_ws_utility\ReflectionUtility" );</example></documentation>
*/
    public static function showMethodSignatures( $obj,/* bool */$with_hr=false )
    {
        echo self::getMethodSignatures( $obj );
        if( $with_hr ) echo HR;
    }
    
/**
Displays the information of a method.
@param mixed $obj A string (the class name) or an object
@param string $method_name The method name
<documentation><description>Displays the information of a method.</description>
<example>u\ReflectionUtility::showMethodSignature( "cascade_ws_utility\ReflectionUtility", "showMethod" );</example></documentation>
*/
    public static function showMethodSignature( $obj,/* string */$method_name,/* bool */$with_hr=false )
    {
        echo self::getMethodSignature( self::getMethod( $obj, $method_name ) );
        if( $with_hr ) echo HR;
    }
    
    private static function getXmlValue( $obj,/* string */$method_name,/* string */$ele_name,/* string */$s_html,/* string */$e_html )/* : string */
    {
        // retrieve the method documentation
        $method  = self::getMethod( $obj, $method_name );
        $xml_str = $method->getDocComment();
        // chop off everything before the first <
        $xml_str = substr( $xml_str, strpos( $xml_str, "<" ) );
        // trim */
        $xml_str = str_replace( "*/", "", $xml_str );
        // create the SimpleXMLElement object
        try
        {
            $xml_ele = new \SimpleXMLElement( $xml_str );
            // look for the element and return the formatted text
            foreach( $xml_ele->children() as $child )
            {
                if( $child->getName() == $ele_name )
                {
                    return $s_html . $child->__toString() . $e_html;
                }
            }
            return c\M::INFORMATION_NOT_AVAILABLE;
        }
        catch( \Exception $e )
        {
            return c\M::INFORMATION_NOT_AVAILABLE;
        }
    }
}