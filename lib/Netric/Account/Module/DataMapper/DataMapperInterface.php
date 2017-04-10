<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric\Account\Module\DataMapper;

use Netric\Error\ErrorAwareInterface;
use Netric\Account\Module\Module;

interface DataMapperInterface extends ErrorAwareInterface
{
    /**
     * Save changes or create a new module
     *
     * @param Module $module The module to save
     * @return bool true on success, false on failure with details in $this->getLastError
     */
    public function save(Module $module);

    /**
     * Get a module by name
     *
     * @param string $name The name of the module to retrieve
     * @return Module|null
     */
    public function get($name);

    /**
     * Get all modules installed in this account
     *
     * @param string $scope One of the defined scopes in Module::SCOPE_*
     * @return Module[]
     */
    public function getAll($scope = null);

    /**
     * Delete a non-system module
     *
     * @param Module $module Module to delete
     * @return bool true on success, false on failure with details in $this->getLastError
     */
    public function delete(Module $module);
}