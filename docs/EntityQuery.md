# EntityQuery

The EntityQuery module is a set of classes for performing queries against collections of
entities. 

There are really two main parts to performing a query:

## 1. Create a Query

    $query = new EnityQuery("obj_type");
    $query->where("field_name")->equals("condition value");

*Note: There are many more operators besides 'equals' for the where conditions.
See the class Netric\EntityQuery\Where for more details*

## 2. Use an Index to Execute the Query

    // Use our service manager to initialize the index
    $seviceManager = $application->getAccount()->getServiceManager();
    $entityIndex = $serviceManager->get("EntityQuery_Index");
    
    // Run execute the query and get results
    $results = $entityIndex->executeQuery($query);
    $numEntities = $results->getNum();
    for ($i = 0; $i < $num; $i++)
    {
        $entity = $results->getEntity($i);
        // Do something with the entity here
    }
