# Entity Recurrence

If the definition for any entity contains a recurRules property then any entity can recur so long as the recurRules are valid (see below).


## Valid recurRules

TODO: list properties here

## API

When interacting with the Entity API, recurrence can be set for any eligible entity (has recurRules in the definition) 
simply by json encoding the recur pattern in the body when sending to svr/Entity::save.
 
For a detailed list of supported array params and their values, please review the fromArray in **Netric\Entity\Entity**
and fromArray function in **Netric\Entity\Recurrence\RecurrencePattern**.