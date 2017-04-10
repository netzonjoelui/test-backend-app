$sync = $serviceLocator->get("EntitySync");

// Partner
// -----------------------------------------------------

	// Get a device partner will load up all collections at once
	$partner = $sync->getPartner("DeviceId");

	// Create new partner if it does not exist
	if (!$partner)
	   $partner = $sync->createPartner("DeviceId");

// Collection - get/create
// -----------------------------------------------------

	// Get changed files
	$collection = $partner->getEntityCollection("file", $conditions=array, $addIfMissing=false);
	if (!$collection)
	  $collection = $partner->createEntityCollection("file", $conditions=array);

	// Get changed groupings
	$collection = $partner->getGroupingCollection("file", "categories", $conditions=array, $addIfMissing=false);
	if (!$collection)
	  $collection = $partner->createGroupingCollection("file", "categories", $conditions=array);

	// Get changed entity definition
	$collection = $partner->getEntityDefinitionCollection($addIfMissing=false);
	if (!$collection)
	  $collection = $partner->createEntityDefinitionCollection();

// Collection - export/import local changes to partner
// -----------------------------------------------------

	// Get changed files
	$collection = $partner->getEntityCollection("file", $conditions=array, $addIfMissing=false);
	
	// Get what has changed locally since the last sync
	$stats = $collection->getExportChanged();
	foreach ($stats as $stat)
	{
		$id = $stat["id"];
		$action = $stat["action"]; // 'change'|'delete'
	}

	// Send some changes from the parter to netric
	$changes = array("1", "2", "3");
	$stats = $collection->getImportChanged($changes);
	foreach ($stats as $stat)
	{
		$remoteUniqueId = $stat['uid'];
		$remoteAction = $stat['action']; // 'change'|'delete'
		$localId = $stat["local_id"];
		$localRevision = $stat["local_revision"];

		// TODO: save the remote object locally

		$collection->logImportChange($remoteId, $localId, $localRevision);
	}