<?php
$start_time = time();

try
{
    require_once('cascade_ws/auth_chanw.php');

	$site_name = 'cascade-admin';
	$page_name = 'test';
	$page      = $cascade->getAsset( Page::TYPE, $page_name, $site_name );
	$test      = new AssetTest( $page );
	$temp_name = 'temp';

/* */
	// test copy
	$test->assertEquals( 
		$temp_page = 
			$cascade->getPage( $temp_name, $site_name ) == NULL ?
				$page->copy( $page->getParentFolder(), 'temp' ) :
			$cascade->getPage( $temp_name, $site_name )
		, 
		$temp_page );

	// test delete
	$cascade->deletePage( $temp_name, $site_name );
	$test->assertEquals( $cascade->getPage( $temp_name, $site_name ), NULL );

	// test exception
	$test->assetExceptionThrown( "\$this->cascade->getAsset( Page::TYPE, 'temp', 'cascade-admin' );" );
/* */
	// test getId
	$test->assertEquals( 'getId', '87e6d0cf8b7f0856002a5e11c8e6bd21' );
	
	// test getName
	$test->assertEquals( 'getName', $page_name );
	
	// test getIdentifiers
	$test->assertIsInArray( 'getIdentifiers', "content-group;0" );
/* */
	
	// test appendSibling, getNumberOfSiblings, removeLastSibling
	$num_sib_before = $page->getNumberOfSiblings( "content-group;0" );
	$page->appendSibling( "content-group;0" );
	$num_sib_after  = $page->getNumberOfSiblings( "content-group;0" );
	$test->assertEquals( $page->getNumberOfSiblings( "content-group;0" ), $num_sib_before + 1 );
	$test->assertNotEquals( $num_sib_before, $num_sib_after );
	
	$num_sib_before = $page->getNumberOfSiblings( "content-group;0" );
	$page->removeLastSibling( "content-group;0" );
	$num_sib_after  = $page->getNumberOfSiblings( "content-group;0" );
	$test->assertEquals( $page->getNumberOfSiblings( "content-group;0" ), $num_sib_before - 1 );
	$test->assertGreaterThan( $num_sib_before, $num_sib_after );
/* */
	
	
	
	
	echo "All tests passed without issues." . BR;
    $end_time = time();
    echo "\nTotal time taken: " . ( $end_time - $start_time ) . " seconds\n";	
}
catch( \Exception $e )
{
    echo S_PRE . $e . E_PRE;
    $end_time = time();
    echo "\nTotal time taken: " . ( $end_time - $start_time ) . " seconds\n";
}
?>