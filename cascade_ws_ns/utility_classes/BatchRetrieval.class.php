<?php
class BatchRetrieval
{
	public function __construct( AssetOperationHandlerService $service )
	{
		if( $service == NULL )
			throw new \Exception( "The service object is NULL." );
			
		$this->service = $service;
		$this->ids     = array();
	}

	public function add( $type, $id )
	{
		if( !isset( $batch[ $type ] ) )
		{
			$ids[ $type ] = array();
		}
		else
		{
			$ids[ $type ][] = $id;
		}
	}
	
	public function retrieve()
	{
		$result = array();
		
		if( count( $ids ) > 0 )
		{
			$types = array_keys( $ids );
			foreach( $types as $type )
			{
				foreach( $ids[ $type ] as $id )
				{
					
				}
			}
		}
		
		return $result;
	}
	
	private $batch;
	private $result;
	private $service;
	
}
?>