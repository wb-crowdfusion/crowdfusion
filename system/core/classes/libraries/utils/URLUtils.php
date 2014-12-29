<?php
/**
 * URLUtils
 *
 * PHP version 5
 *
 * Crowd Fusion
 * Copyright (C) 2009-2010 Crowd Fusion, Inc.
 * http://www.crowdfusion.com/
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted under the terms of the BSD License.
 *
 * @package     CrowdFusion
 * @copyright   2009-2010 Crowd Fusion Inc.
 * @license     http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version     $Id: URLUtils.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * URL Utilities
 * Provides:
 *  Our current URL,
 *  Autolinking of urls,
 *  URL validation,
 *  and other utilities
 *
 * @package     CrowdFusion
 */
class URLUtils
{
    // REGEX FROM: http://immike.net/blog/2007/04/06/5-regular-expressions-every-web-programmer-should-know/
    // UPDATED ON 5-21-2010 FROM: http://data.iana.org/TLD/tlds-alpha-by-domain.txt
    const URL_MATCH =  "\b
          (?:
            (?:https?):\/\/[-\w]+(?:\.\w[-\w]*)+
          |
            (?i: [a-z0-9] (?:[-a-z0-9]*[a-z0-9])? \. )+
            (?-i:
                  ac\b
                | ad\b
                | ae\b
                | aero\b
                | af\b
                | ag\b
                | ai\b
                | al\b
                | am\b
                | an\b
                | ao\b
                | aq\b
                | ar\b
                | arpa\b
                | as\b
                | asia\b
                | at\b
                | au\b
                | aw\b
                | ax\b
                | az\b
                | ba\b
                | bb\b
                | bd\b
                | be\b
                | bf\b
                | bg\b
                | bh\b
                | bi\b
                | biz\b
                | bj\b
                | bm\b
                | bn\b
                | bo\b
                | br\b
                | bs\b
                | bt\b
                | bv\b
                | bw\b
                | by\b
                | bz\b
                | ca\b
                | cat\b
                | cc\b
                | cd\b
                | cf\b
                | cg\b
                | ch\b
                | ci\b
                | ck\b
                | cl\b
                | cm\b
                | cn\b
                | co\b
                | com\b
                | coop\b
                | cr\b
                | cu\b
                | cv\b
                | cx\b
                | cy\b
                | cz\b
                | de\b
                | dj\b
                | dk\b
                | dm\b
                | do\b
                | dz\b
                | ec\b
                | edu\b
                | ee\b
                | eg\b
                | er\b
                | es\b
                | et\b
                | eu\b
                | fi\b
                | fj\b
                | fk\b
                | fm\b
                | fo\b
                | fr\b
                | ga\b
                | gb\b
                | gd\b
                | ge\b
                | gf\b
                | gg\b
                | gh\b
                | gi\b
                | gl\b
                | gm\b
                | gn\b
                | gov\b
                | gp\b
                | gq\b
                | gr\b
                | gs\b
                | gt\b
                | gu\b
                | gw\b
                | gy\b
                | hk\b
                | hm\b
                | hn\b
                | hr\b
                | ht\b
                | hu\b
                | id\b
                | ie\b
                | il\b
                | im\b
                | in\b
                | info\b
                | int\b
                | io\b
                | iq\b
                | ir\b
                | is\b
                | it\b
                | je\b
                | jm\b
                | jo\b
                | jobs\b
                | jp\b
                | ke\b
                | kg\b
                | kh\b
                | ki\b
                | km\b
                | kn\b
                | kp\b
                | kr\b
                | kw\b
                | ky\b
                | kz\b
                | la\b
                | lb\b
                | lc\b
                | li\b
                | lk\b
                | lr\b
                | ls\b
                | lt\b
                | lu\b
                | lv\b
                | ly\b
                | ma\b
                | mc\b
                | md\b
                | me\b
                | mg\b
                | mh\b
                | mil\b
                | mk\b
                | ml\b
                | mm\b
                | mn\b
                | mo\b
                | mobi\b
                | mp\b
                | mq\b
                | mr\b
                | ms\b
                | mt\b
                | mu\b
                | museum\b
                | mv\b
                | mw\b
                | mx\b
                | my\b
                | mz\b
                | na\b
                | name\b
                | nc\b
                | ne\b
                | net\b
                | nf\b
                | ng\b
                | ni\b
                | nl\b
                | no\b
                | np\b
                | nr\b
                | nu\b
                | nz\b
                | om\b
                | org\b
                | pa\b
                | pe\b
                | pf\b
                | pg\b
                | ph\b
                | pk\b
                | pl\b
                | pm\b
                | pn\b
                | pr\b
                | pro\b
                | ps\b
                | pt\b
                | pw\b
                | py\b
                | qa\b
                | re\b
                | ro\b
                | rs\b
                | ru\b
                | rw\b
                | sa\b
                | sb\b
                | sc\b
                | sd\b
                | se\b
                | sg\b
                | sh\b
                | si\b
                | sj\b
                | sk\b
                | sl\b
                | sm\b
                | sn\b
                | so\b
                | sr\b
                | st\b
                | su\b
                | sv\b
                | sy\b
                | sz\b
                | tc\b
                | td\b
                | tel\b
                | tf\b
                | tg\b
                | th\b
                | tj\b
                | tk\b
                | tl\b
                | tm\b
                | tn\b
                | to\b
                | tp\b
                | tr\b
                | travel\b
                | tt\b
                | tv\b
                | tw\b
                | tz\b
                | ua\b
                | ug\b
                | uk\b
                | us\b
                | uy\b
                | uz\b
                | va\b
                | vc\b
                | ve\b
                | vg\b
                | vi\b
                | vn\b
                | vu\b
                | wf\b
                | ws\b
                | xn\b
                | ye\b
                | yt\b
                | za\b
                | zm\b
                | zw\b
                | [a-z][a-z]\.[a-z][a-z]\b
            )
          )

          (?: : \d+ )?

          (?:
            \/
            [^?;\"<>\[\]\{\}\s\x7F-\xFF]*
            (?:
                  [\.!,?]+ [^?;\"<>\[\]\{\}\s\x7F-\xFF]+
            )*
          )?";

    /**
     * Returns the current, full url
     *
     * @return string the current url
     */
    public static function fullUrl()
    {
        $s        = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
        $protocol = StringUtils::strLeft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/").$s;
        $port     = ($_SERVER["SERVER_PORT"] == "80" || $_SERVER["SERVER_PORT"] == "443") ? "" : (":".$_SERVER["SERVER_PORT"]);
        $uri      = (isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:"");
        return $protocol."://".$_SERVER['SERVER_NAME'].$port.$uri;
    }

    /**
     * Returns a version of {@link $text} where all full
     * http strings are translated into hyperlinks
     *
     * @param string $text The text to process
     *
     * @return string The text with added hyperlinks
     */
    public static function autoLinkUrls($text)
    {
        if (strpos($text, '://') === false) {
            // our really quick heuristic failed, abort
            // this may not work so well if we want to match things like
            // "google.com", but then again, most people don't
            return $text;
        }

         $bits = preg_split("/(".self::URL_MATCH.")/ix", $text, -1, PREG_SPLIT_DELIM_CAPTURE);

         $newstring = '';

        // $i = index
        // $c = count
        // $l = is link
        for ($i = 0, $c = count($bits), $l = false; $i < $c; $i++, $l = !$l) {
            if (!$l) {
                if ($bits[$i] === '') continue;
                $newstring .= $bits[$i];
            } else {
                $newstring .= '<a href="'.$bits[$i].'">'.$bits[$i].'</a>';
            }
        }
        return $newstring;
    }

    /**
     * Determines if {@link $string} is a URL
     *
     * @param string $string The string to analyze
     *
     * @return boolean Returns TRUE if the string passed in is a url
     */
    public static function isUrl($string)
    {

        return preg_match('/'.self::URL_MATCH.'/ix', $string);
    }


    /**
     * Outputs a "safe" url, stripped of xss, autolink on domain, and stripped of invalid chars
     *
     * @param string $url The url to process
     *
     * @return string Safe and happy URL
     */
    public static function safeUrl($url)
    {
        // Strip invalid characters (anything but [a-zA-Z0-9=?&:;#_\/.-])
//        $url = preg_replace("/[^a-zA-Z0-9=?&:;#_\/.-]/", '', $url);

        if (empty($url))
            return '';

        // Prepend http:// if no scheme is specified
        if (!preg_match("/^(https?):\/\//", $url))
            $url = 'http://' . $url;

        if (!self::isURL($url))
            $url = '';

        return filter_var($url,  FILTER_SANITIZE_URL);

    }

    /**
     * Given a key=>value array {@link $data}, returns a url with all keys
     * in the form of $key replaced with the value from the array,
     * and any remaining key=>value pairs appended to the end of the url.
     *
     * @param string $url  The URL to process
     * @param string $data The key => value array to use as a source for data.
     *
     * @return string The processed URL
     */
    public static function resolveUrl($url, $data, $ignoreUnfound = false)
    {

        $foundNVPs = array();
        foreach ((array)$data as $n => $v) {
            if (strpos($url, '$'.$n) !== false) {
                $url = str_replace('$'.$n, $v, $url);
                $foundNVPs[] = $n;
            }
        }

        $unfoundNVPs = array_diff_key($data, array_flip($foundNVPs));
        if($ignoreUnfound)
            return $url;

        return self::appendQueryString($url, $unfoundNVPs);
    }

    /**
     * Appends all the key => value pairs in {@link $data} to the end
     * of the {@link $targetUrl}
     *
     * @param string $targetUrl The URL to append to
     * @param string $data      The array used to build the query additions
     *
     * @return string The appended URL
     */
    public static function appendQueryString($targetUrl, $data)
    {

        // Extract anchor fragment, if any.
        $fragment    = null;
        $anchorIndex = strpos($targetUrl, '#');
        if ($anchorIndex !== false) {
            $fragment  = substr($targetUrl, $anchorIndex);
            $targetUrl = substr($targetUrl, 0, $anchorIndex);
        }

        // If there aren't already some parameters, we need a "?".
        $existing = array();

        $first = (($pos = strpos($targetUrl, '?')) === false?true:false);
        if(!$first) {
            parse_str(substr($targetUrl, $pos+1), $existing);
            $targetUrl = substr($targetUrl, 0, $pos);
            $first = true;
        }

        $found = array();
        foreach ((array) $data as $name => $value) {
            $found[] = $name;
            if (!is_array($value)) {

                if ($first == true) {
                    $targetUrl .= '?';
                    $first = false;
                } else
                    $targetUrl .= '&';

                $encodedKey   = urlencode("{$name}");
                $encodedValue = urlencode("{$value}");
                $targetUrl   .= $encodedKey . '='.$encodedValue;
            }
        }
        foreach($existing as $name => $value)
        {
            if(!in_array($name, $found)) {
                if ($first == true) {
                    $targetUrl .= '?';
                    $first = false;
                } else
                    $targetUrl .= '&';

                $encodedKey   = urlencode("{$name}");
                $encodedValue = urlencode("{$value}");
                $targetUrl   .= $encodedKey . '='.$encodedValue;
            }
        }


        // Append anchor fragment, if any, to end of URL.
        if ($fragment != null)
            $targetUrl .= $fragment;

        return $targetUrl;
    }

    public static function normalizeURL($url)
    {
        if(!self::isURL($url))
            return false;

        $url = self::safeUrl($url);

        $parts = @parse_url($url);

        $domain = $parts['host'];
        $domain = str_replace('www.', '', strtolower($domain));

        $path = isset($parts['path'])?(trim(preg_replace('/index\.[a-z0-9]+/i', '', $parts['path']), '/')):'';
        $query = isset($parts['query'])?$parts['query']:'';

        return $domain .(!empty($path)?'/'.$path:'').( !empty($query)? '?'.$query : '');
    }


    public static function parseDomainFromURL($url)
    {
        $url = self::safeUrl($url);
        $urlParts = @parse_url($url);
        if($urlParts !== false && isset($urlParts['host']))
        {
            $host = $urlParts['host'];
            $host = str_replace('www.', '', $host); // remove generic www
            $host = strtolower($host);

            return $host;
        }

        return false;
    }

    public static function parseRootDomain($domain)
    {
        if(substr_count($domain, '.') > 1)
        {
            $lastPos = strrpos($domain, '.');
            $secLastPos = strrpos(substr($domain, 0, $lastPos), '.');
            return substr($domain, $secLastPos+1);
        }
        return $domain;
    }

    public static function resolveVariables($value, $key = null)
    {

        $value = str_replace(
                array('%PATH_BUILD%', '%CONTEXT%', '%REWRITE_BASE%', '%SERVER_NAME%', '%DEVICE_VIEW%', '%DESIGN%', '%DOMAIN%', '%DOMAIN_BASE_URI%', '%DEPLOYMENT_BASE_PATH%'),
                array(PATH_BUILD, $_SERVER['CONTEXT'], $_SERVER['REWRITE_BASE'], $_SERVER['SERVER_NAME'], $_SERVER['DEVICE_VIEW'], $_SERVER['DESIGN'], $_SERVER['DOMAIN'], $_SERVER['ROUTER_BASE'], isset($_SERVER['DEPLOYMENT_BASE_PATH'])?$_SERVER['DEPLOYMENT_BASE_PATH']:''),
                $value
                );

        if(is_null($key) || stripos($key, 'base') !== FALSE)
            $value = preg_replace("/\/[\/]+/", "/", $value);

        return $value;
    }

    /**
     * Replace variables inside a value using the object as an originating reference
     *
     * @static
     * @param  $value
     * @param  $object
     * @return mixed
     *
     */
    public static function resolveContextVariables($value, $object)
    {

        $value = str_replace(
                array('%PATH_BUILD%', '%CONTEXT%', '%REWRITE_BASE%', '%SERVER_NAME%', '%DEVICE_VIEW%', '%DESIGN%', '%DOMAIN%', '%DOMAIN_BASE_URI%', '%DEPLOYMENT_BASE_PATH%'),
                array(PATH_BUILD, ($object instanceof ContextObject?$object->Slug:$_SERVER['CONTEXT']), $_SERVER['REWRITE_BASE'], $_SERVER['SERVER_NAME'], $_SERVER['DEVICE_VIEW'], $_SERVER['DESIGN'], $object->Domain, $object->DomainBaseURI, isset($object->DeploymentBasePath)?$object->DeploymentBasePath:''),
                $value
                );

        $value = preg_replace("/\/[\/]+/", "/", $value);

        return $value;
    }

    /**
     * Creates a Site object from an array originating from the environments.xml
     *
     * @static
     * @param  $siteArray
     * @param bool $useDomainAlias
     * @return
     */
    public static function resolveSiteFromArray($siteArray, $useDomainAlias = false)
    {
        $site = new Site();

        return self::resolveSiteContextObject($site, $siteArray, $useDomainAlias);

    }

    /**
     * Creates a ContextObject object from an array originating from the environments.xml
     *
     * @static
     * @param  $contextArray
     * @param bool $useDomainAlias
     * @return
     */
    public static function resolveContextFromArray($contextArray, $useDomainAlias = false)
    {
        $context = new ContextObject();

        return self::resolveSiteContextObject($context, $contextArray, $useDomainAlias);
    }

    protected static function resolveSiteContextObject($site, $siteArray, $useDomainAlias)
    {

        foreach(array('slug', 'sSL', 'anchor', 'name', 'description', 'domain', 'domain_base_uRI', 'deployment_base_path', 'domain_redirects', 'domain_alias', 'exclude_final_slash') as $key)
        {
            $lower = strtolower($key);
            $camel = StringUtils::camelize($key);

            if(!empty($siteArray[$lower]))
                $site->$camel = $siteArray[$lower];

        }

        $site->Anchor = StringUtils::strToBool($site->Anchor);
        $site->ExcludeFinalSlash = StringUtils::strToBool($site->ExcludeFinalSlash);
        $domain = $site->Domain;

        if(isset($siteArray['domain_alias']) && $useDomainAlias)
           $domain = $siteArray['domain_alias'];

//        if($site instanceof ContextObject){
            $site->DomainBaseURI = URLUtils::resolveContextVariables($site->DomainBaseURI, $site);
            $site->DeploymentBasePath = URLUtils::resolveContextVariables($site->DeploymentBasePath, $site);

//        } else {
//            $site->DomainBaseURI = URLUtils::resolveVariables($site->DomainBaseURI);
//            $site->DeploymentBasePath = URLUtils::resolveVariables($site->DeploymentBasePath);
//        }

        $site->BaseURL = 'http'.($site->isSSL()?'s':'').'://'.rtrim($domain.$site->DomainBaseURI,'/');

        // non-aliased BaseURL
        $site->LiveBaseURL = 'http'.($site->isSSL()?'s':'').'://'.rtrim($site->Domain.$site->DomainBaseURI,'/');

        // Convert from string to bool.
        $site->Enabled = StringUtils::strToBool($siteArray['enabled']);

        if(empty($siteArray['name']))
            $site->Name = $site->Slug;

        if(!empty($siteArray['storagefacilities']))
        {
            $sfs = $siteArray['storagefacilities'];
            foreach($sfs as $key => $sfArray)
            {

                if(empty($sfArray['for']))
                    throw new Exception('storage_facility property "for" is missing');

                if(empty($sfArray['ref']))
                    throw new Exception('storage_facility property "ref" is missing');

                $info = new StorageFacilityInfo();
                $info->For = $sfArray['for'];
                $info->GenerateRewriteRules = isset($sfArray['generate_rewrite_rules'])?StringUtils::strToBool($sfArray['generate_rewrite_rules']):false;
                $info->ObjectRef = $sfArray['ref'];

                $params = new StorageFacilityParams();
                $params->BaseURI = $sfArray['base_uri'];
                $params->BaseStoragePath = $sfArray['base_storage_path'];
                $params->Domain = $sfArray['domain'];

                if(isset($sfArray['ssl']))
                    $params->SSL = StringUtils::strToBool($sfArray['ssl']);
                else
                    $params->SSL = false;

                $info->StorageFacilityParams = $params;

                $site->setStorageFacilityInfo($info->For, $info);
            }
        }

        return $site;
    }

}
