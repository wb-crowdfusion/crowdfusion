<?php
/**
 * FileSystemUtils
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
 * @version     $Id: FileSystemUtils.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Utilities for files.
 * Provides:
 *  human-readable size details,
 *  mime-type lookups,
 *  directory utils,
 *  and temporary file utils
 *
 * @package     CrowdFusion
 */
class FileSystemUtils
{
    /**
     * Returns a human-readable filesize for the {@link $bytes} specified.
     *
     * For example: FileSystemUtils::humanFilesize(1024) => "1.0 KB"
     *
     * @param int $bytes   The number of bytes of the files
     * @param int $decimal The number of places after the decimal point to show in the result (default: 1)
     *
     * @return string The filesize in human format
     */
    public static function humanFilesize($bytes, $decimal = 1)
    {
        if (is_numeric($bytes)) {
            $position = 0;
            $units = array (
                " Bytes",
                " KB",
                " MB",
                " GB",
                " TB"
            );
            while ($bytes >= 1024 && ($bytes / 1024) >= 1) {
                $bytes /= 1024;
                $position++;
            }
            return round($bytes, $decimal) . $units[$position];
        } else
            return "0 Bytes";
    }

    /**
     * Returns number of bytes for PHP ini values ( memory_limit = 512M )
     *
     * Stas Trefilov, OpteamIS
     * http://us3.php.net/manual/en/function.ini-get.php#96996
     *
     * @static
     * @param  $iniValue
     * @return int
     */
    public static function iniValueInBytes($iniValue)
    {
        switch (substr ($iniValue, -1))
        {
            case 'M': case 'm': return (int)$iniValue * 1048576;
            case 'K': case 'k': return (int)$iniValue * 1024;
            case 'G': case 'g': return (int)$iniValue * 1073741824;
            default: return $iniValue;
        }
    }


    /**
     * Returns a mime-type based on the specified {@link $extension}
     *
     * @param string $extension The extension to lookup
     *
     * @return string The mime-type of the extension specified or 'application/octet-stream'
     */
    public static function getMimetype($extension)
    {
        static $mimetypes;

        if (empty($mimetypes)) {
            $mimetypes = array(
                        "323"     => "text/h323",
                        "acx"     => "application/internet-property-stream",
                        "ai"      => "application/postscript",
                        "aif"     => "audio/x-aiff",
                        "aifc"    => "audio/x-aiff",
                        "aiff"    => "audio/x-aiff",
                        "asf"     => "video/x-ms-asf",
                        "asr"     => "video/x-ms-asf",
                        "asx"     => "video/x-ms-asf",
                        "au"      => "audio/basic",
                        "avi"     => "video/quicktime",
                        "axs"     => "application/olescript",
                        "bas"     => "text/plain",
                        "bcpio"   => "application/x-bcpio",
                        "bin"     => "application/octet-stream",
                        "bmp"     => "image/bmp",
                        "c"       => "text/plain",
                        "cat"     => "application/vnd.ms-pkiseccat",
                        "cdf"     => "application/x-cdf",
                        "cer"     => "application/x-x509-ca-cert",
                        "class"   => "application/octet-stream",
                        "clp"     => "application/x-msclip",
                        "cmx"     => "image/x-cmx",
                        "cod"     => "image/cis-cod",
                        "cpio"    => "application/x-cpio",
                        "crd"     => "application/x-mscardfile",
                        "crl"     => "application/pkix-crl",
                        "crt"     => "application/x-x509-ca-cert",
                        "csh"     => "application/x-csh",
                        "css"     => "text/css",
                        "csv"     => "text/comma-separated-values",
                        "dcr"     => "application/x-director",
                        "der"     => "application/x-x509-ca-cert",
                        "dir"     => "application/x-director",
                        "dll"     => "application/x-msdownload",
                        "dms"     => "application/octet-stream",
                        "doc"     => "application/msword",
                        "docx"    => "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                        "dot"     => "application/msword",
                        "dvi"     => "application/x-dvi",
                        "dxr"     => "application/x-director",
                        "eps"     => "application/postscript",
                        "etx"     => "text/x-setext",
                        "evy"     => "application/envoy",
                        "exe"     => "application/octet-stream",
                        "fif"     => "application/fractals",
                        "flr"     => "x-world/x-vrml",
                        "flv"     => "video/x-flv",
                        "gif"     => "image/gif",
                        "gtar"    => "application/x-gtar",
                        "gz"      => "application/x-gzip",
                        "h"       => "text/plain",
                        "hdf"     => "application/x-hdf",
                        "hlp"     => "application/winhlp",
                        "hqx"     => "application/mac-binhex40",
                        "hta"     => "application/hta",
                        "htc"     => "text/x-component",
                        "htm"     => "text/html",
                        "html"    => "text/html",
                        "htt"     => "text/webviewhtml",
                        "ico"     => "image/x-icon",
                        "ics"     => "text/calendar",
                        "ief"     => "image/ief",
                        "iii"     => "application/x-iphone",
                        "ins"     => "application/x-internet-signup",
                        "isp"     => "application/x-internet-signup",
                        "jfif"    => "image/pipeg",
                        "jpe"     => "image/jpeg",
                        "jpeg"    => "image/jpeg",
                        "jpg"     => "image/jpeg",
                        "js"      => "application/x-javascript",
                        "latex"   => "application/x-latex",
                        "lha"     => "application/octet-stream",
                        "log"     => "text/plain",
                        "lsf"     => "video/x-la-asf",
                        "lsx"     => "video/x-la-asf",
                        "lzh"     => "application/octet-stream",
                        "m13"     => "application/x-msmediaview",
                        "m14"     => "application/x-msmediaview",
                        "m3u"     => "audio/x-mpegurl",
                        "man"     => "application/x-troff-man",
                        "mdb"     => "application/x-msaccess",
                        "me"      => "application/x-troff-me",
                        "mht"     => "message/rfc822",
                        "mhtml"   => "message/rfc822",
                        "mid"     => "audio/mid",
                        "mny"     => "application/x-msmoney",
                        "mov"     => "video/quicktime",
                        "movie"   => "video/x-sgi-movie",
                        "mp2"     => "video/mpeg",
                        "mp3"     => "audio/mpeg",
                        "mp4"     => "video/mp4",
                        "mpa"     => "video/mpeg",
                        "mpe"     => "video/mpeg",
                        "mpeg"    => "video/mpeg",
                        "mpg"     => "video/mpeg",
                        "mpp"     => "application/vnd.ms-project",
                        "mpv2"    => "video/mpeg",
                        "ms"      => "application/x-troff-ms",
                        "mvb"     => "application/x-msmediaview",
                        "nws"     => "message/rfc822",
                        "oda"     => "application/oda",
                        "p10"     => "application/pkcs10",
                        "p12"     => "application/x-pkcs12",
                        "p7b"     => "application/x-pkcs7-certificates",
                        "p7c"     => "application/x-pkcs7-mime",
                        "p7m"     => "application/x-pkcs7-mime",
                        "p7r"     => "application/x-pkcs7-certreqresp",
                        "p7s"     => "application/x-pkcs7-signature",
                        "pbm"     => "image/x-portable-bitmap",
                        "pdf"     => "application/pdf",
                        "pfx"     => "application/x-pkcs12",
                        "pgm"     => "image/x-portable-graymap",
                        "pko"     => "application/ynd.ms-pkipko",
                        "pma"     => "application/x-perfmon",
                        "pmc"     => "application/x-perfmon",
                        "pml"     => "application/x-perfmon",
                        "pmr"     => "application/x-perfmon",
                        "pmw"     => "application/x-perfmon",
                        "png"     => "image/png",
                        "pnm"     => "image/x-portable-anymap",
                        "pot"     => "application/vnd.ms-powerpoint",
                        "ppm"     => "image/x-portable-pixmap",
                        "pps"     => "application/vnd.ms-powerpoint",
                        "ppt"     => "application/vnd.ms-powerpoint",
                        "pptx"    => "application/vnd.openxmlformats-officedocument.presentationml.presentation",
                        "prf"     => "application/pics-rules",
                        "ps"      => "application/postscript",
                        "psd"     => "image/photoshop",
                        "pub"     => "application/x-mspublisher",
                        "qt"      => "video/quicktime",
                        "ra"      => "audio/x-pn-realaudio",
                        "ram"     => "audio/x-pn-realaudio",
                        "ras"     => "image/x-cmu-raster",
                        "rgb"     => "image/x-rgb",
                        "rm"      => "application/vnd.rn-realmedia",
                        "rmi"     => "audio/mid",
                        "roff"    => "application/x-troff",
                        "rtf"     => "application/rtf",
                        "rtx"     => "text/richtext",
                        "scd"     => "application/x-msschedule",
                        "sct"     => "text/scriptlet",
                        "setpay"  => "application/set-payment-initiation",
                        "setreg"  => "application/set-registration-initiation",
                        "sh"      => "application/x-sh",
                        "shar"    => "application/x-shar",
                        "sit"     => "application/x-stuffit",
                        "snd"     => "audio/basic",
                        "spc"     => "application/x-pkcs7-certificates",
                        "spl"     => "application/futuresplash",
                        "src"     => "application/x-wais-source",
                        "sst"     => "application/vnd.ms-pkicertstore",
                        "stl"     => "application/vnd.ms-pkistl",
                        "stm"     => "text/html",
                        "svg"     => "image/svg+xml",
                        "sv4cpio" => "application/x-sv4cpio",
                        "sv4crc"  => "application/x-sv4crc",
                        "swf"     => "application/x-shockwave-flash",
                        "t"       => "application/x-troff",
                        "tar"     => "application/x-tar",
                        "tcl"     => "application/x-tcl",
                        "tex"     => "application/x-tex",
                        "texi"    => "application/x-texinfo",
                        "texinfo" => "application/x-texinfo",
                        "tgz"     => "application/x-compressed",
                        "tif"     => "image/tiff",
                        "tiff"    => "image/tiff",
                        "tr"      => "application/x-troff",
                        "trm"     => "application/x-msterminal",
                        "tsv"     => "text/tab-separated-values",
                        "txt"     => "text/plain",
                        "uls"     => "text/iuls",
                        "ustar"   => "application/x-ustar",
                        "vcf"     => "text/x-vcard",
                        "vrml"    => "x-world/x-vrml",
                        "wav"     => "audio/x-wav",
                        "wcm"     => "application/vnd.ms-works",
                        "wdb"     => "application/vnd.ms-works",
                        "wks"     => "application/vnd.ms-works",
                        "wmf"     => "application/x-msmetafile",
                        "wps"     => "application/vnd.ms-works",
                        "wri"     => "application/x-mswrite",
                        "wrl"     => "x-world/x-vrml",
                        "wrz"     => "x-world/x-vrml",
                        "xaf"     => "x-world/x-vrml",
                        "xbm"     => "image/x-xbitmap",
                        "xla"     => "application/vnd.ms-excel",
                        "xlc"     => "application/vnd.ms-excel",
                        "xlm"     => "application/vnd.ms-excel",
                        "xls"     => "application/vnd.ms-excel",
                        "xlsx"    => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                        "xlt"     => "application/vnd.ms-excel",
                        "xlw"     => "application/vnd.ms-excel",
                        "xml"     => "text/xml",
                        "xof"     => "x-world/x-vrml",
                        "xpm"     => "image/x-xpixmap",
                        "xwd"     => "image/x-xwindowdump",
                        "z"       => "application/x-compress",
                        "zip"     => "application/zip");
        }

        $mimetype = "application/octet-stream";

        if (array_key_exists(strtolower($extension), $mimetypes))
            $mimetype = $mimetypes[strtolower($extension)];

        return $mimetype;
    }

    /**
     * Creates a unique temporary directory and returns the absolute path
     *
     * @return string the absolute path to the temporary directory
     */
    public static function createWorkingDirectory()
    {
        $workdir = sys_get_temp_dir() . '/tmp-' . session_id() . '/';

        self::recursiveMkdir($workdir, 0777);
        //@chmod($workdir,0777);

        if (!is_dir($workdir)) {
            throw new Exception("cannot create directory: " . $workdir);
        }

        return $workdir;
    }

    /**
     * Recursively creates the specified {@link $path} with the mode {@link $mode}
     *
     * @param string  $path The path to create
     * @param integer $mode The mode to use on new directories
     *
     * @return boolean true on success
     */
    public static function recursiveMkdir($path, $mode = 0755)
    {
        if (is_dir($path)) {
            self::safeChown($path);
            return @chmod($path, $mode);
        }

        if(!is_dir(dirname($path)))
        {
            if($path == dirname($path))
                throw new Exception('Directory recursion error'.(ini_get('safe_mode') == true?', SAFE MODE restrictions in effect':''));
            if(!self::recursiveMkdir(dirname($path), $mode))
                return false;

        }

        $return_value = @mkdir($path, $mode);
        @chmod($path, $mode);
        self::safeChown($path);
        return $return_value;
    }

    /**
     * Puts contents into the target file safely and chowns to the
     * current user.  If this is overwriting the target then this
     * first writes the contents to a temp file (same file name with
     * ".tmp.[randomChars]" added at the end) and then renames it to
     * the target.  This prevents the concurrency issue where other
     * requests are reading from a file that is currently being
     * written to.
     *
     * @param $filename
     * @param $contents
     * @param int $flags
     * @return bool
     * @throws Exception
     */
    public static function safeFilePutContents($filename, $contents, $flags = LOCK_EX)
    {
        if (!self::recursiveMkdir(dirname($filename))) {
            throw new Exception('Unable to create directory: ' . dirname($filename));
        }

        // if we're appending go ahead and write directly to the target
        if ($flags >= FILE_APPEND) {
            if (!@file_put_contents($filename, $contents, $flags)) {
                throw new Exception('Unable to write file: ' . $filename);
            }
        } else {
            $tmpFilename = tempnam(dirname($filename), basename($filename) . '.tmp.');
            if (!@file_put_contents($tmpFilename, $contents, $flags)) {
                @unlink($tmpFilename);
                throw new Exception('Unable to write file: ' . $tmpFilename);
            }

            if (!@rename($tmpFilename, $filename)) {
                @unlink($tmpFilename);
                throw new Exception('Unable to write file: ' . $filename);
            }
        }

        self::safeChown($filename);
        return true;
    }

    public static function safeCopy($filename, $target)
    {
        if(!self::recursiveMkdir(dirname($target)))
            throw new Exception('Unable to create directory: '.dirname($target));

        if(!@copy($filename, $target))
            throw new Exception('Unable to copy file: '.$filename);

        self::safeChown($target);
        return true;
    }

    public static function safeChown($filename)
    {
        $buildStat = stat(PATH_BUILD);
        @chown($filename, intval($buildStat['uid']));
        @chgrp($filename, intval($buildStat['gid']));
        return true;
    }

    /**
     * Recursively remove a directory and its contents.
     *
     * @param string  $path         The directory to remove
     * @param boolean $removeParent If false, then remove only the contents of the directory at $path.
     *                                  Default: true
     *
     * @return boolean true on success
     */
    public static function recursiveRmdir($path, $removeParent = true)
    {
        if (!file_exists($path))
            return true;

        if (!is_dir($path))
            return @unlink($path);

        $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($objects as $item) {
            if ($item == '.' || $item == '..')
                continue;

            if(!is_dir($item))
            {
                if (!@unlink($item))
                    return false;
            } else {
                if(!@rmdir($item))
                    return false;
            }
        }
        return $removeParent ? rmdir($path) : true;
    }

    /**
     * Creates a temp file with a specific extension.
     *
     * Creating a temporary file with a specific extension is a common
     * requirement on dynamic websites. Largely this need arises from
     * Microsoft browsers that identify a downloaded file's mimetype based
     * on the file's extension.
     *
     * No single PHP function creates a temporary filename with a specific
     * extension, and, as has been shown, there are race conditions involved
     * unless you use the PHP atomic primitives.
     *
     * I use only primitives below and exploit OS dependent behaviour to
     * securely create a file with a specific postfix, prefix, and directory.
     *
     * @param string $dir     Optional. The directory in which to create the new file
     * @param string $prefix  Default 'tmp'. The prefix of the generated temporary filename.
     * @param string $postfix Default '.tmp'. The extension or postfix for the generated filename.
     *
     * @see    http://us.php.net/manual/en/function.tempnam.php#42052
     * @author bishop
     *
     * @return string the filename of the new file
     */
    public static function secureTmpname($dir = null, $prefix = 'tmp', $postfix = '.tmp')
    {
        // validate arguments
        if (!(isset ($postfix) && is_string($postfix)))
            return false;

        if (!(isset ($prefix) && is_string($prefix)))
            return false;

        if (!isset ($dir))
            $dir = getcwd();

        // find a temporary name
        $tries = 1;
        do {
            // get a known, unique temporary file name
            $sysFileName = tempnam($dir, $prefix);
            if ($sysFileName === false)
                return false;

            // tack on the extension
            $newFileName = $sysFileName . $postfix;
            if ($sysFileName == $newFileName)
                return $sysFileName;

            // move or point the created temporary file to the new filename
            // NOTE: these fail if the new file name exist
            $newFileCreated = @rename($sysFileName, $newFileName);
            if ($newFileCreated)
                return $newFileName;

            unlink($sysFileName);
            $tries++;
        } while ($tries <= 5);

        return false;
    }

    /**
     * Sanitizes a file name.
     *
     * Removes invalid characters from a file name.  Since the file name is sent to the server from the browser
     * it may contain invalid characters, and if a local copy of the file is written to disk with the un-sanitized file
     * name, a file system error would occur or worse.
     *
     * @param string $filename A string representing a file name
     *
     * @return string A sanitized version of the $filename parameter
     */
    public static function sanitizeFilename($filename)
    {
        $chars = array(
            "../", "./", "<!--", "-->", "<", ">", "'", '"', '&', '$', '#', '{', '}', '[', ']', '=', ';', '?', "%20", "%22",
            "%3c",		// <
            "%253c", 	// <
            "%3e", 		// >
            "%0e", 		// >
            "%28", 		// (
            "%29", 		// )
            "%2528", 	// (
            "%26", 		// &
            "%24", 		// $
            "%3f", 		// ?
            "%3b", 		// ;
            "%3d"		// =
        );

		return stripslashes(str_replace($chars, '', $filename));
    }
}
