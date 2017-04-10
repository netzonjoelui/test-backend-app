<?php
/**
 * This is an update to EntitySync which changes local_revision in
 * object_sync_import from entity revision to entity commit_id
 */
$account = $this->getAccount();
$serviceManager = $account->getServiceManager();
$db = $serviceManager->get("Netric/Db/Db");
$entityLoader = $serviceManager->get("Netric/EntityLoader");

$sql = "SELECT osi.id, osi.object_id, osi.revision, osc.object_type
        FROM object_sync_import as osi inner join object_sync_partner_collections as osc ON (
          osi.collection_id=osc.id
        )
        WHERE
          osi.object_id IS NOT NULL AND
          osi.revision IS NOT NULL AND
          osc.object_type IS NOT NULL AND
          (osc.field_name IS NULL OR osc.field_name='')
          ";
$result = $db->query($sql);
$num = $db->getNumRows($result);
for ($i = 0; $i < $num; $i++) {
    $row = $db->getRow($result, $i);
    $entity = $entityLoader->get($row['object_type'], $row['object_id']);

    if ($entity && $entity->getValue("commit_id")) {
        $sql = "UPDATE object_sync_import
                SET revision=" . $entity->getValue("commit_id") . "
                WHERE id=" . $row['id'];
        if (!$db->query($sql)) {
            throw new \RuntimeException("Query failed: " . $db->getLastError());
        }
    } else {
        // At some point an entity was imported but it looks like it may be stale now.
        $db->query("DELETE FROM object_sync_import WHERE id={$row['id']}");
    }
}
