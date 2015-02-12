CHANGELOG for 3.x.x
===================

* 3.2.3 (2015-02-11)
  * [NodeCache] Use an optional nodeSchemaVersion from `$properties['nodeSchemaVersion']` to form cache key instead
    of using the system version which changes with every release.  ticket #1


* 3.2.2 (2014-12-08)
    * [TransactionManager] Fixed the php doc blocks and made the entire class a fluent interface.
    * [Dispatcher] Added "Dispatcher.terminate" events to all branches that end the request.  ticket #227
    * [Response] Added "Dispatcher.terminate" to sendRedirect method.  ticket #227
    * [DateFactory] Cleaned up php doc blocks.


* 3.2.1 (2014-11-23)
    * [MemcachedCacheStore] Change log levels on connection events to debug.  ticket #220
    * [Request] Added default option to getParameter method.


* 3.2.0 (2014-11-10)
    * [MemcachedCacheStore] Support Memcached extension and AWS EC2 Autodiscovery.  ticket  #187
    * [MemcacheCacheStore] Old, incorrectly named MemcachedCacheStore copied over to maintain old functionality.
        All old parameters/config options have the "d" removed so as to be clear. ticket  #187
    * Removed all "olddb" classes in system/core/classes/systemdb/olddb/
    * [ClassLoader] Set default on for bypassDirectories in addDirectory method.
    * Adds CrowdFusion\\Tests with initial MemcachedCacheStore tests.
    * [AbstractFilterer] Adds $default option which defaults to null for getGlobal, getLocal and getParameter.
    * [AbstractController] Adds $default option which defaults to null for getTemplateVariable and passthruTemplateVariable.
    * Adds composer autoloader_classmap integration, for the love of not wrapping plugins for no reason.  Still dependent
        on crowdfusion's autoloader, for now.
    * Remove the CrowdFusion classes in src/ as they are not in use.  Entire class libraries folder needs to be refactored/organized at some point.
    * [SlugUtils] Added public static $strictMode to allow for operations when slugs are known to have things like a - following a /.


* 3.1.0 (2014-07-07)
    * Bake in environment and context specific config file loading.  ticket #180
    * Removed the following cms routes, templates and controllers from cms.
      * aspects (list and edit views for aspects)
      * cms navigation (list/add/edit for cms nav)
      * elements (list/add/edit for elements)
      * deployment (switches hot deploy mode on/off in cms)
    * Eliminated pointless versioncheck and include_once directive.
    * Eliminated hotdeploy.php include from front controllers and references.
    * Removed install directory/app and the StringUtils tests.
    * Removed logger debug calls in Events.php.  Added noise, useless debug and penality incurred on all requests of a heavy event system.
    * [ApplicationContext] Removed try/catch around loadSystem method call added from ticket #128.
    * [Object] Added JsonSerializable with default handing to return data from toArray method.
    * [DisplayFilterer] Simplified debug and showVars methods to just json encode the data.
    * [NodeRef] Added jsonSerialize override to render the element:slug instead of toArray().


* 3.0.8 (2014-04-10)
    * Upgraded Pear HTTP_Request2 packages from 2.1.1 to 2.2.1.  ticket #164


* 3.0.7 (2014-01-04)
    * [TagsFilterer] Fix invalid reference to tag property.  ticket #158


* 3.0.6 (2014-01-04)
    * [ErrorHandler] Added SYSTEM_VERSION to debug vars.
    * [ApplicationContext] Added check for non object in getMasterCacheFile method.
    * [NodeFindAllDAO] Added #meta.in filter option.  ticket #147
    * [NodesHelper] Added #meta.in filter option.  ticket #147
    * [ContextUtils] parseXMLFile fails on context files with no children.  check for === false.
    * [CFTemplateEngine] fixed evalOneCondition method to check for scalar item before casting to string.
            fixes: Error: Notice(8): Array to string conversion in templateengines/cft/CFTemplateEngine.php on line 1798
    * [NumberUtils] Created new class for bound method.
    * [NumberFilterer] Added method to call bound method on NumberUtils.
    * [PagingFilterer] Added sanitizePage/sanitizePerPage method to return a number between 1 and 1000 (or a custom min/max).
    * [NumberFilterer] Added method to call bound method on NumberUtils.
    * [StringUtils] Added firstLetter method to get first alphanum char from a string.
    * [DisplayFilterer] Added firstLetter method to call new StringUtils method.


* 3.0.5 (2013-12-13)
    * [ApplicationContext] Log directory recursion failures and fail gracefully.  ticket #145, #128
    * [ApplicationContext] Add system.version to properties.
    * [CFTemplateEngine] Add SYSTEM_VERSION to constants.
    * [HttpRequest] Set CURLOPT_SSL_VERIFYHOST to 2.  true is deprecated.
    * [AbstractFileDeploymentService] Added aggregatePath to exception in getDeployedFiles method.
    * [CFTemplateEngine] Added asset block output to exception in callbackFinalAssets method.


* 3.0.4 (2013-10-24)
    * [SlugUtils] Update slugutils to avoid the creation of mutant slugs that might contain improperly arranged dashes in the lead, trail, or doubled up in any part of the string.  ticket #119
    * [ApplicationContext] Remove $this reference calls to internal methods... $this->someMethod($this->someProp...).  ticket #128


* 3.0.3 (2013-09-19)
    * [NodeMetaDAO] incrementMeta and decrementMeta updated to use old style delta on UPDATE to avoid clobbering in
        high volume update scenarios but also catch exceptions to catch boundaries that MySql can't catch.  ticket #109

        Note: The use of LAST_INSERT_ID to capture the result of the increment/decrement fails when the result is
        a bigint.  This is a mysql bug.  No error is thrown but a warning is raised "1264 Out of range value for column".
        The column will end up being zero (on the updated record) but the result from LAST_INSERT_ID may either be
        zero, the original value to increment/decrement or the wrap around.  Depends on the direction of the update, the
        operand and the datatype.  An incrementMeta that reaches a bigint will reset to zero.  This is considered an
        acceptable risk because in reality, no meta should ever get that big, especially one that is being incremented
        based on user interaction or normal system events.


* 3.0.2 (2013-09-04)
    * [MySQLDatabase] use exponential/logarithmic backoff when retrying queries. ticket #103
    * [MySQLDatabase] reduced default deadlockRetries to 6 from 10. ticket #103


* 3.0.1 (2013-08-21)
    * [NodeMetaDAO] incrementMeta and decrementMeta now pull value from DB and evaluate in php.  ticket #99
    * [Dispatcher] new event announced "Dispatcher.terminate" with Transport argument.  ticket #100


#### base rev 2.20.1

* 3.0.0 (2013-05-25)
    * Make framework device aware.  ticket #75
        * $_SERVER['DEVICE_VIEW'] is used to determine which device view to render to a client.
        * New system config `$properties['deviceView']` and `$properties['response.vary']`
        * [CFTemplateEngine] Populate DEVICE_VIEW, ASSETS_BASEURL and MEDIA_BASEURL in constants.
        * [Dispatcher] Make sure ETag includes device view, design and accept encoding to ensure no overlap/mismatch.
        * [AbstractDeploymentService,AbstractFileDeploymentService,AbstractPHPAggregatorService] Aggregates routes and redirects from domain folder, device folder and design folder, in that order.
        * [ErrorHandler] add the device view and design to the error log if we have it.
        * Created a new cli called console.php using named parameters
  * [EventFilterer] Ensure Node is passed through on filter method.  Available as Transport->Node
  * [AbstractLogger] Check for "Logger" string in class instead of "MultiLogger"
  * [Instantiator] Ensure autowireDefinition ignores methods called "set"
  * Changed shared-context.xml Container object id to "CFContainer" to prevent conflicts with crowdfusion-symfony bridge
