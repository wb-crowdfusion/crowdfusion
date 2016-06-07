WB CrowdFusion
========================

[![Build Status](https://api.travis-ci.org/wb-crowdfusion/crowdfusion.svg)](https://travis-ci.org/wb-crowdfusion/crowdfusion)
[![Code Climate](https://codeclimate.com/github/wb-crowdfusion/crowdfusion/badges/gpa.svg)](https://codeclimate.com/github/wb-crowdfusion/crowdfusion)

Summary Overview of Crowd Fusion file structure
-----------------------------------------------

This is a brief overview of the Crowd Fusion system. For a full overview, refer to the [[System Overview]] documentation.

The Crowd Fusion system is comprised of:

* The Crowd Fusion shared public plugins, located in the `crowdfusion/` folder.
* The Crowd Fusion Framework, located in the `system/` folder.

Your Crowd Fusion application is comprised of a few essential ideas:

* private and/or public plugins that provide shared data aspects and PHP functionality, including the supplied Crowd Fusion plugins
* the view or presentation of your front-end site
* a unique configuration of elements representing the major types of content
* application and environment specific configuration
* the content (members, news, profiles, media files, etc.)

# System Layout

For all intents and purposes, you will live inside the `app/` folder while building your application on the Crowd Fusion platform. This tree aims to provide you with an idea of the roles of the different folders within your Crowd Fusion installation.

    app/
        plugins/                  - private plugins for your application
        view/                     - view or presentation files
    build/
        context/                  - environment-specific XML context files
        media/                    - uploaded media content
        config.php                - general configuration for your application
        config_dev.php            - environment-specific configuration for your application
        config_prod.php           - environment-specific configuration for your application
        pluginconfig.php          - plugin-specific configuration (deprecated)
        contexts.xml              - environment-specific contexts/sites XML configuration file
        system.xml                - system XML configuration file (list of installed plugins, elements, aspects, etc.)
    crowdfusion/                  - the Crowd Fusion CMS plugins
    system/                       - the Crowd Fusion Core Framework
    console.php                   - command line (CLI) Front Controller
    web.php                       - web request Front Controller

## View layout

    app/
        view/
            cms/
            shared/
                public/
            web/

The `app/view/cms/` folder contains any design customizations for the CMS screens. The only item within this folder upon installation of the sample app is `app/view/cms/assets/images/cluster-logo.png`. Replacing this file will replace the default Crowd Fusion logo in the upper left corner of the CMS screens for this installation.

The `app/view/shared/` folder includes shared templates used by the system. Additionally, the `app/view/shared/public/` folder contains static files served at the document root of your site and your CMS installation
(for example: `favicon.ico`, `robots.txt`, etc.). These public files can be overridden from within a design, as described below.

The `app/view/web/` folder contains the web design (and any alternate designs) for your site.

Upon installation, there will be a simple design directing you to login to the CMS that lives in the `default/` folder.  To replace this design, you will create a new folder with the same name as your site and a folder within that folder called `main/default/`. For example, `www.YOURDOMAIN.com/` below is a reference device view and design folder to show you the file structure for a front-end design for a site living at `http://www.YOURDOMAIN.com`. If you rename this folder or duplicate this folder and rename it to match the domain of the primary domain where you installed Crowd Fusion, any files contained in the appropriate sections of this design will override the design found in `app/view/web/shared/`.

You'll notice that the first folder within the `www.YOURDOMAIN.com/` folder is also called `main/`. You can create
additional folders alongside this `main/` folder that will serve as multiple device views for this site.  This is so you can serve the desktop, phone, tablet, etc. from the same URL.  Inside of that folder is a `default/` folder which is the design. Switching between these designs for testing purposes can be achieved via a cookie-based switch that is called via an interceptor from a special query string. Once you complete a design and test it, you can copy it over to your `default/` folder to replace your site's default design for everyone.  The device view can also be switched for testing using the same mechanism.  For example, ?device_view=tablet&design_switch=v2design

#### A sample design: www.YOURDOMAIN.com/main/default/

    app/
        view/
            shared/ (available to all contexts)
                assets/
                public/
                templates/
            web/
                default/ (deprecated path, will be removed in later version)
                    assets/
                    public/
                    templates/
                shared/ (renamed to shared for less confusion with "default" design switch)
                    assets/
                    public/
                    templates/
                www.YOURDOMAIN.com/
                    main/ (the only required device view folder)
                        default/ (the DESIGN SWITCH folder)
                            assets/
                            public/
                            templates/
                            redirects.php (optional, will override routes by array key, not by order!)
                            routes.php (optional, will override routes by array key, not by order!)
                    shared/ (available to all device views)
                        assets/
                        public/
                        templates/
                    routes.php (applies to ALL device views)
                    redirects.php (applies to ALL device views)

* `assets/` -contains all static (non-content) design components for your site's presentation
             (CSS, Javascript, images, flashfiles, etc.).
* `public/` -contains static files served at the document root of your site (for example: `favicon.ico`, `robots.txt`,
             etc.); this folder can be used to override the files found in `app/view/shared/public/` on a site by site basis.
* `templates/` -contains all the templates for your design. Templates are called by the [[Renderer]]. The Renderer uses a view name (based on the template name followed by a file extension in the format `template.ext`) to determine the Template Engine and Template to process.  The extension `.cft` signifies the Crowd Fusion [[CFT Template Engine|Template Engine]] as the engine to process the file.  The extension `.xmod`, usually referred to as an xmodule, loads the Crowd Fusion [[XMOD Template Engine|XBuilder Template Engine]], an XML-based template engine. Additional template engines may be defined.
* `routes.php` -map request URIs to physical template locations or actions.
* `redirects.php` -redirect from one URI to another URI.

## Plugin Layout

    app/
        plugins/
            example-com/
                aspects/
                classes/
                context/
                    api-context.xml
                    cli-context.xml
                    cms-context.xml
                    install-context.xml
                    shared-context.xml
                    web-context.xml
                view/
                bootstrap.php
                plugin.xml

The plugin folder includes all the plugins that you have available as part of your app. Plugins provide a combination of views and controls primarily for the CMS context by calling templates, assets, messages, routes, redirects, filter classes that are shared across contexts, xbuilders used by the CMS, and various PHP classes, including controllers, filterers, event handlers, and libraries, to name a few. The only plugins not found in this folder are the core Crowd Fusion plugins located in the `crowdfusion/` folder in the root directory of your installation. You will not need to access the `crowdfusion/` folder, as any functionality that you wish to override or change from those default plugins can be done within your application.

The only item in the above structure that is required is the `plugin.xml` file, which defines the plugin. All the other sections provide added functionality and customization to the plugin.

* `aspects/` -contains any aspect XML definition files required by your plugin.
* `classes/` -contains all the PHP classes required by your plugin; these can be placed in
              any desired sub-directory configuration.
* `context/` -contains the IoC container XML context files to be included in the respective context when your plugin is loaded. Some example files are included in the tree above. Without a context file specific to the current context (web, cms, cli, etc.), the IoC container will locate and include the shared-context.xml file instead. *Note: in the presence of a specific context file, it's assumed you will be including the shared-context.xml manually.*
* `view/` -similar to the `view/` directory at the root of your app that contains all the assets and templates that define the appearance of your app, the `view/` folder within your plugin contains the view or presentation layer for the CMS, CLI, or API areas of your plugin. The contents of this folder should be structured like the `view/` directory at the root of your app. *Important note: while it is possible to include `web` context view files here, this is not recommended; it is only recommended to include CMS, CLI, and API necessary presentation files.*
* `bootstrap.php` -allows plugins to execute code during the loading process, before the Dispatcher or any classes in the container are called. When all plugins are loaded into the context, the container will iterate through them in priority order, and execute a single bootstrap.php file contained at the root of each plugin.  If the file does not exist, the container continues to the next plugin.
* `plugin.xml` -defines the plugin and is the only file required by a plugin.


&copy; 2016 Warner Bros. Entertainment Inc. All rights reserved.
