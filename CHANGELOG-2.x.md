CHANGELOG for 2.x.x
===================
2.20.1
    * Make sure offset doesn't exceed PHP_INT_MAX in DTO.php.  ticket #73
    * Ensure FileSystemUtils and ContextUtils write to temp file first if not FILE_APPEND mode.  ticket #74
    * Use jQuery builtin .val() to set tags values rather than encodeURIComponent in TagFormManager.js and Taggable.js.  ticket #71

  -- greg.brown May 11, 2013

2.20.0
    * Log URL on errors when throwRender exceptions is false in Renderer and CFTemplateEngine.  Need more info in logs to be able to resovle issues. ticket #67
    * Do not throw exception when site is not found by ApplicationContext, just echo the message and exit. ticket #66
    * ContextUtils::safeFilePutContents will now write to a temp file first and then rename it to avoid reads while a new write is happening.  ticket #68

  -- greg.brown April 29, 2013

    * Upgrade response headers to use HTTP/1.1 in most cases. ticket #58

  -- jb.smith April 9th, 2013


2.19.0
    * Use HTTP/1.1 when response has ETag header (in Response::prepare).  ticket #50

  -- greg.brown April 7, 2013

    * Updated the Dispatcher to prefix ETags with a SystemVersionTimestamp that invalidates etags on redeploy. ticket #50

  -- jb.smith  April 3, 2013

    * Added more logging to fix TemplateEngine bug that fails to find NodeRef. ticket #54

  -- jb.smith  March 26, 2013

    * Implemented ETag support in framework at core/classes/mvc/dispatcher/Dispatcher.php. ticket #50

  -- jb.smith  March 26, 2013


2.18.2

    * Resolved BUG: Tag widget fails to add node whose title begins with double-quote.  ticket #3

  -- jb.smith March 20, 2013

    * Eliminated surrounding "from" tables with parens in Query.php as it's invalid SQL versions > 5.1.  ticket #52
    * Instantiator will now continue processing autowiring if the constructor argument that is missing has a default.  ticket #47

  -- greg.brown March 17, 2013


2.18.1

    * Modified Router.php to ensure all URIs are evaluated with no trailing slash.  ticket #42
    * Modified Router.php to set cache on 301s to allow for modifying a redirect rule. ticket #42

  -- greg.brown March 07, 2013

    * Modified AbstractLogger to include method name in log entry.  ticket #43

  -- jb.smith March 06, 2013


2.18.0

    * Added "OrderBy[Out|In]Tag" option to NodeQuery which will allow sorting by a tag role SortOrder.  Requires a fully qualified tag partial "element:slug#role ASC|DESC".

  -- jb.smith January 08, 2013

    * Added arrayInsert to ArrayUtils.php.
    * Added NumberFilterer to filters.

  -- jb.smith January 15, 2013

    * Added passthruTemplateVariable or passthruParameter for Node[Api|Cli|Web]Controller to deal with OrderByInTag or OrderByOutTag.

  -- jb.smith January 16, 2013

    * Added PSR-0 and Pear autoloader options.  Also, DOC_ROOT/app/src folder will automatically be scanned for PSR-0 classes.
    * Using md5 of aspects and plugin xml files now instead of last modified so merging branches is simpler.
    * Added <node_class/> option to elements so developer can add custom Node classes.  Custom Node classes MUST extend Node.
    * Added interfaces and classes for Container and ContainerAware.  These should be used instead of ApplicationContext where possible.
    * Added CrowdFusion\NCR\AbstractDomainObjectQuery for creating custom NCR queries with domain specific logic.

  -- greg.brown January 20, 2013

    * Added permanent redirect option to routes.  Using "redirect:permanent:/some/location" in a route's view variable will 301, instead of 302.

  -- greg.brown January 25, 2013

    * Added Node to CFT row locals.  Node is now available in templates and filter calls.
    * Added error.verbose and error.multiline to config.  These MUST be added to your config.php.
    * Modified ErrorHandler to not email errors related to files submitted that are too large for PHP.
    * Logger.php now optionally replaces line breaks in error_log writes.  Useful when sending log data to external provider (i.e. loggly).
    * Restored TagLinkActiveDate, TagLinkID and TagLinkSortOrder population in NodeTagsDAO

  -- greg.brown February 1, 2013

    * revised CFTemplateEngine to start using Logger in lieu of using error_log directly
    * Added optional headers array to HttpRequest and HttpRequestInterface methods

  -- jb.smith February 8, 2013

2.17.0

    * Use '===' in place of strcmp in Tag::matchExact for increased performance.

 -- dan.wash Wed 21 Nov, 2012 12:00 CDT

    * In-Tags are deleted by ID when it's the in-tag side initiating the delete.

 -- dan.wash Fri 16 Nov, 2012 11:00 CDT

    * Added Tag::diff to get the specific differences between tags. Useful for knowing if all fields match except for one.
    * NodeTagsDAO::saveOutTags & NodeTagsDAO::saveInTags no longer perform a remove/add for tags only differing in sort order.
      Instead if tags only differ by sort order, just update the database record.

 -- dan.wash Thu 15 Nov, 2012 16:00 CDT

    * Stopped duplicate tags deleted from the database in NodeTagsDAO->findTags for 'read repair' from being returned in the results array.

 -- dan.wash Fri 2 Nov, 2012 10:20 CDT

    * NodeDAO's addTag, addOutTag & addInTag now enforce multiple=false on tag additions.

 -- dan.wash Tue 30 Oct, 2012 15:30 CDT

 UPGRADE NOTE:

 crowdfusion-maintenance plugin provides the CLI NodetagsmaintenanceCliController::enforceMultipleFalse to check for
 tags corrupted by multiple=false not being enforced and can correct them. Highly recommended that this is run prior
 to updating to determine potential impact.

2.15.0

    * Added AbstractBulkCountsHandler to keep a running list of nodes and counts to change all at once on commit.
    * Fixed AbstractCountsHandler IoC functions not having matching variables.

 -- dan.wash Mon 15 Jul, 2012 10:15 CDT

2.14.0

    * Changed CondFilterer.php so in array filter checks if array parameter is instance of Meta and uses Meta Value if so.

 -- raul.reynoso Mon 30 Jul, 2012 11:00 EDT

2.13.0

    * MemcachedCacheStore: add hashed key to debug log message in addition to user key

 -- clay.hinson Tue 6 Jun, 2012 11:00 EDT

    * Add support for the tag-prepend xmod attribute and tagprepend option for tag widgets.  This option defaults to false, but when explicitly set to true adds tags to the top of the tag list.

 -- matt.surabian Tue 6 Jun, 2012 11:00 EDT

2.12.1-dev

    * Changed AbstractTagWidget.js renderChosen function. Checking for multivalued tags now looks at previous tags element as well as slug.

 -- raul.reynoso Tue 5 Jun, 2012 9:16 EDT

    * ensure TagUtils::deleteTags/filterTags/diffTags always return sequential indexed arrays

-- andy.scholz Tue, 22 May 2012 8:50 NZST

2.12.0

    * add bindNothing method to DefaultBindHandler
    * deprecate thumbnails.gqQuality, see v2.9.0

 -- ryan.scheuermann Fri 13 Apr, 2012  14:41 EDT

    * Dispatcher: handle controller method not found exception as a 404, rather than 503.

 -- clay.hinson Mon 9 Apr, 2012 15:45 EDT

    * Add EmailTagInterface, to be used in conjunction with EmailInterface if email
      service supports tagging.
    * Update ErrorHandler to add a tag when sending email, if EmailTagInterface exists.

 -- eric.byers Tue 27 Mar, 2012 15:15 CDT

    * Include SERVER_ADDR in server variable dump in CLI error emails.

 -- dan.wash Mon 26 Mar, 2012 9:30 CST

    * Added CSS min-height to CMS edit view fieldset list items, to fully wrap
      when a display-only field is last in a row

 -- clay.hinson Thu 22 Mar, 2012 10:30 EDT

    * Adding IoC Setters to FileLogger/AbstractLogger in addition to setting via constructor()
    * Fixing issue where FileLogger injected the local timezone, but was not actually using it.

 -- eric.byers wed 21 Mar, 2012 10:30 CDT

    * Redirect to default design when design cookie is set to non-existent design.

 -- raul.reynoso Mon 19 Mar, 2012 12:30 EST

2.11.1

    * fix issue where Response headers could be inadvertently duplicated

 -- andy.scholz Tue 24 Apr, 2012 17:12 EST

2.11.0

    * Update to SystemXMLParser to set Enabled to false for Sites within a context that is not enabled.
    * Allow framework class loader to ignore .AppleDouble folders (for use in local vm's)
    * FileSystemUtils::getMimeType Adding additional extensions/mimetypes
    * EmailUtils::isEmailAddress is now static
    * Adding DisplayFilterer::implode method, takes in 'value' and 'glue'
    * Updated EditCmsBuilder::display to use display-implode when a tag is present.  This allows
      tag widgets with multiple tags to display their values instead of 'Array'.
    * Adding 'preformatted' attribute option to EditCmsBuilder::display.  When using
      <display> in an xmod mod, you can set preformatted="1" and it will wrap <pre></pre> tags
      around the output.
    * Updating HttpRequest to include sending/receiving of headers
    * Adding PEAR folder in vendors with
      - Pear_Exception
      - Http_Request2
      - Net_Url2

 -- eric.byers Thu 8 Mar, 2012 15:45 EST

2.10.0
    * Introducing new template block {% begin exec %}.  The exec block does not iterate over
      the data, it only executes conditions,filters,variables,included templates and assets.
      The exec block solves the problem of meta data from the last row filling values for rows
      with null meta data in included templates called with both passed data and inherit=true.

-- raul.reynoso Wed 29 Feb, 2012

2.9.2
    * Fix unclosed li when rendering dropdown element in xmod

-- raul.reynoso Tue 21 Feb 2012, 11:20 ET

    * Add 'enabled' to be set for Context array in URLUtils::resolveSiteContextObject

 -- eric.byers Fri 17 Feb 2012, 16:45 CST

    * Update query building process in NodeFindAllDAO to prepend table name to the order by field in the
      ORDER BY clause and the SELECT clause

-- elliot.betancourt  Fri, 17 Feb 2012, 10:38 ET

2.9.1

    * #2702 Short term fix for AbstractPHPAggregatorService producing broken cms/redirects.php

  -- alex.johnson  Fri, 17 Feb 2012  15:00 PST

2.9.0

    * added various methods to Node Query object to make creating queries less verbose. Methods
      such as ->setElementsIn, ->setSlugsIn, ->setOutTagsSelect, etc.

-- elliot.betancourt  Wed, 15 Feb 2012, 12:14 ET

    * ability to specify file format when creating thumbnails.

  -- noel.bennett  Fri, 10 Feb 2012 14:25 EST

    * allow removal of trailing slashes for CLI execution

  -- ryan.scheuermann  Wed, 8 Feb 2012 18:00 EST

    * Thumbnails config properties are now thumbnails.jpegQuality and thumbnails.pngQuality
      which are used by every conversion method.  thumbnails.gdQuality should be deprecated.

    NOTE: Support for config property thumbnails.gdQuality should be removed in the next major version.

  -- noel.bennett  Fri, 3 Feb 2012, 12:15 EST

    * Error emailer was failing to recognise extraRecipients parameter

  -- noel.bennett  Thu, 2 Feb 2012, 13:15 EST

    * Accept thumbnail quaility config property for imagick mode.
      If the config property corresponding to the conversion utility is set,
      we use it.  If it's a scalar, we use that as the image quality, if it's
      an array and there exists a value under the format, we use that,
      otherwise use the value under the key "default".

  -- noel.bennett  Mon, 30 Jan 2012, 20:35 EST

2.8.0
    * Adding Node CLI method to migrate In Tags from one node to another. Changes to delete method so migrate and delete can be used in tandem effectively
 -- raul.reynoso Thurs, 2 Feb 2012, 10:40 ET

    * NodeTagsDAO->findTags using the previously found definition rather than skipping that field. (A variable is being reused and not cleared) implemented diff supplied by Noel Bennett
    * Gracefully handle Nonce Mismatch exceptions in API context

 -- elliot.betancourt  Wed, 1 Feb 2012, 14:18 ET

    * Split stripHtml logic out of DisplayFilterer and move into StringUtils
      to allow access to stripHtml logic outside of DisplayFilterer
    * Adding AbstractLogger.  Logger now extends AbstractLogger
      PLEASE NOTE: All plugins/client code will need to check 'LoggerInterface' instead of 'Logger'
                   to allow proper compatibility with Logger plugins/functionality.
    * Switch classes to check for LoggerInterface instead of Logger.
    * Update AbstractLogger::getCalleeClass to better determine correct calling class.

  -- eric.byers   Mon, 30 Jan 2012 10:45 CST

2.7.1

    * Fix issue where first request serves 404 - aggregated routes fail on initial deployment

  -- ryan.scheuermann  Thurs, 26 Jan 2012 20:30 EST


2.7.0

    * Delete and Reassign from NodeCLI Contrller

 -- raul.reynoso  Wed, 18 Jan 2012, 17:30 ET

    * Better error emails

 -- noel.bennett  Wed, 28 Dec 2011, 14:40 GMT

2.6.0

    * Fix segmentation fault when more than one error is hit on BulkpublishCmsController

  -- elliot.betancourt  Wed, 21 Dec 2011 17:30 EST

    * Debug and failure logging for MemcachedCacheStore

  -- ryan.scheuermann  Mon, 19 Dec 2011 15:30 EST

    * NodesHelper::filterNodes() bug: use getMetaValue() instead of getMeta()
    * TransactionManager::resetTransactionalRequest()

  -- noel.bennett  Thu, 3 Nov 2011 11:15 EDT

    * Fix inline edit height when using tag widgets

  -- rus.werner Thu, 17 Nov 2011 14:51 NZDT

    * Added cached.redirect.ttl property to config.php

  -- kevin.irlen Tue, 1 Nov 2011 9:52 EDT

2.5.4

    * #2151 Remove console.log debug line from NodeService.js

  -- alex.johnson  Mon, 5 Dec 2011  15:35 PST

2.5.3

    Changes to support crowdfusion-advanced-media v1.4.0:
    * Node API: Tag updates (for asynchronous updates to non-fieldlike tags - specifically reordering and clearing
    * Node API: Addition of tags with a sort order. While this change modifies existing function NodeApiController::addTag() and its corresponding NodeService call, this should not negatively affect existing usage.
    * JsonFiltererChanges: Changes a == null test to is_null to prevent errors with empty JSON (specifically when NodeApiController::getTags() is invoked on a node with no tags).
   * EditCmsBuilderChanges: Adds support for the uid attribute in xmods to append a unique suffix identifier to fields and tagwidgets for rendering. See section heading Upload Preview in Technical Notes.

  -- clay.hinson Wed, 5 Oct 2011 11:25 EDT

    * Patched StringUtils::trim to fix keeptags parsing bugs

  -- dan.wash Wed, 5 Oct 2011 12:50 EDT

2.5.2
    * Fix bug in NodesHelper::sortNodes() when sorting by more than one field

  -- noel.bennett Fri, 7 Oct 2011 15:00 EDT

2.5.1
    * Setting boolean meta to false when the existing value is false executes an unnecessary MySQL query. When done
      under massive concurrency, table rows deadlock

  -- ryan.scheuermann  Wed, 24 Aug 2011 14:45 EDT

2.5.0
    DEPLOY NOTES:
    * Remove plugin crowdfusion-list-filter to prevent conflicts.
      The entire functionality of that plugin has been moved into the core.

    * #541 - Add ListfilterFilterer class.

  -- noel.bennett  Mon 08 Aug 2011 09:25 EDT

    * Make CompressUtils:gzdecode static

  -- rus.werner Monm 08 Aug 2011 15:08 NZST

    * Remove CreationDate, ModifiedDate from element schema
    * Remove CreationDate from plugin schema
    * Remove CreationDate from aspect schema
    * Add secondary sort on CMS nav items
    * Add sorting for element aspects

  -- ryan.scheuermann  Tue, 02 Aug 2011 13:30 EDT

2.4.1
    * Merge 2.3.8 changes into 2.4

  -- kevin.irlen  Tue, 02 Aug 2011 11:06 EDT

    * added getLog() method to SubversionTools and deprecated getPathList()

  -- clay.hinson Mon, 18 Jul 2011 22:15 EDT

    * Merge 2.3.7 changes into 2.4

  -- ryan.scheuermann  Mon, 18 Jul 2011 17:30 EDT

2.4.0
    * Add TreeID.depth condition to Node queries, allowing filtering nodes by tree depth

  -- clay.hinson Wed, 13 Jul 13:00 EST

    * Add meta JSON datatype
    * Add flv,swf to FileSystemUtils::getMimetype()

  -- rus.werner Tue, 14 Jun 2011 10:07 EST

2.3.8
    * #P567 prevent SimpleXMLExtended.asPrettyXML() from failing due to regexp errors
    * #P569 provide a configurable timeout for cached redirects

  -- kevin.irlen  Mon, 18 Jul 2011 10:00 EDT

2.3.7
    * #713 add headers to StorageFacilityFile
    * #714 add CompressUtils::gzdecode

  -- ryan.scheuermann  Wed, 15 Jun 2011 10:00 EDT


2.3.6
    * #708 update autolink regex to allow the @ character in links

  -- noel.bennett Thu, 9 Jun 2011 21:30 CEST

2.3.5
    * #707 fixed event handler callback init for Eventable decorator

  -- rus.werner Tue, 31 May 2011 10:37 EDT

2.3.4
    * #705 add humanFilesize() to general.js
    * #704 gracefully handle empty json responses in NodeService.js

  -- rus.werner Tue, 24 May 2011 10:36 NZT

2.3.3
    * make MetaUtils::truncatToMax() behaviour match string meta validation

  -- noel.bennett  Mon, 9 May 2011  09:20 EDT

2.3.2
    * #697 add NodeCliController->purgeAll() function

  -- alex.johnson  Wed, 4 May 2011  13:51 PDT


2.3.1
    * clear tag widget results only if it is a new search

  -- noel.bennett  Wed, 27 Apr 2011  15:50 EDT


2.3.0
    * add TemplatedEmail / TemplatedEmailInterface as convenience class for sending emails from a CFT template

  -- ryan.scheuermann  Mon, 25 Apr 2011  9:42 EDT


2.2.4
    * #691 extend SubversionTools to copy, list and retrieve info on svn paths

  -- clay.hinson Mon, 11 Apr 2011 16:50 EDT

    * #692 MemcachedCacheStore::increment bug

  -- noel.bennett Wed, 20 Apr 2011 9:00 EDT

    * #686 createSlug filter should support allowSlashes

  -- kevin.irlen Fri, 8 Apr 2011 13:00 EDT


2.2.3

    * Check for console before logging in window.onerror handler
    * #685 Do not set time to 00:00:00 for DateOnly meta because it breaks when storage timezone differs from local timezone

 -- brian.abent Fri, 8 Apr 2011 16:12 EST


2.2.2

    * Assign unique DOM IDs to FieldsetExpander links
    * Wrap global window.onerror handler in a config.php parameter condition
        - Add the following line to config.php
        $properties['cms.onerror.alert.enabled'] = $_SERVER['ENVIRONMENT'] != 'prod';

    * add ElementService::findAllFromString($aspectsOrElements)

 -- noel  Thu, 31 Mar 2011 13:13 EST


2.2.1
    * 2.1.5 patch: fix Integrity Constraint violation when ordering by Slug
    * 2.1.4 patch: fix bug caused by #662 in renaming event triggers
    * 2.1.3 patch: #677 Status.isActive and Status.all should be treated as booleans
    * 2.1.3 patch: #678 Filtering nodes via PHP doesn't filter deleted nodes by default

 -- ryan  Fri, 11 Mar 2011 14:00 EST


2.2.0

    * #674 Any different environment that is set up should have different front end coloring
         - Additional themes can be added to the app/view/cms/assets/css/themes/ folder
         - Please add the following to config.php: $properties['cms.theme'] = 'default';
    * #675 Fix AbstractTagWidget to only increment TagSortOrder when AllowReorderChosenList is true, otherwise
      set TagSortOrder to 0

 -- rus Tue, 2 Mar 2011 03:00 EST

    * #665 Added new date attribute, unix, to support dates before 1970

 -- brian.abent Thu, 10 Mar 2011 03:16 EST


2.1.2

    * #669 Add DisplayFilterer->humanFilesize
    * #670 List screens firing multiple ajax requests when redrawHeadings is called
    * #658 HttpRequest library should throw exceptions for >=400 and >=500 response codes
    * #660 Add context to error email subject line
    * #661 Send error emails for Exception's thrown in NodeCmsController
    * #666 Include custom HTTP request headers in error emails
    * #662 Replace all event triggers using __CLASS__ or __FUNCTION__ with strings

 -- ryan Mon, 28 Feb 2011 09:00 EST

2.1.1

    * #664 CMS bulk actions are not running inside a transaction

 -- rus Mon, 21 Feb 2011 18:30 EST

2.1.0

    * initial versioned commit

 -- ryan  Wed, 26 Jan 2010 22:17 EST
