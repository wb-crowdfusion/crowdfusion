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
        /**
         * A map of mime types and their default extensions.
         *
         * This list has been placed under the public domain by the Apache HTTPD project.
         * This list has been updated from upstream on 2013-04-23.
         *
         * @see http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types
         *
         * @var array
         */
        static $mimetypes;

        if (empty($mimetypes)) {
            $mimetypes = array(
                '123' => 'application/vnd.lotus-1-2-3',
                '323' => 'text/h323',
                '3dml' => 'text/vnd.in3d.3dml',
                '3ds' => 'image/x-3ds',
                '3g2' => 'video/3gpp2',
                '3gp' => 'video/3gpp',
                '7z' => 'application/x-7z-compressed',
                'aab' => 'application/x-authorware-bin',
                'aac' => 'audio/x-aac',
                'aam' => 'application/x-authorware-map',
                'aas' => 'application/x-authorware-seg',
                'abw' => 'application/x-abiword',
                'ac' => 'application/pkix-attr-cert',
                'acc' => 'application/vnd.americandynamics.acc',
                'ace' => 'application/x-ace-compressed',
                'acu' => 'application/vnd.acucobol',
                'acx' => 'application/internet-property-stream',
                'adp' => 'audio/adpcm',
                'aep' => 'application/vnd.audiograph',
                'afp' => 'application/vnd.ibm.modcap',
                'ahead' => 'application/vnd.ahead.space',
                'ai' => 'application/postscript',
                'aif' => 'audio/x-aiff',
                'aifc' => 'audio/x-aiff',
                'aiff' => 'audio/x-aiff',
                'air' => 'application/vnd.adobe.air-application-installer-package+zip',
                'ait' => 'application/vnd.dvb.ait',
                'ami' => 'application/vnd.amiga.ami',
                'apk' => 'application/vnd.android.package-archive',
                'appcache' => 'text/cache-manifest',
                'application' => 'application/x-ms-application',
                'apr' => 'application/vnd.lotus-approach',
                'arc' => 'application/x-freearc',
                'asc' => 'application/pgp-signature',
                'asf' => 'video/x-ms-asf',
                'aso' => 'application/vnd.accpac.simply.aso',
                'asr' => 'video/x-ms-asf',
                'asx' => 'video/x-ms-asf',
                'atc' => 'application/vnd.acucorp',
                'atom' => 'application/atom+xml',
                'atomcat' => 'application/atomcat+xml',
                'atomsvc' => 'application/atomsvc+xml',
                'atx' => 'application/vnd.antix.game-component',
                'au' => 'audio/basic',
                'avi' => 'video/quicktime',
                'aw' => 'application/applixware',
                'axs' => 'application/olescript',
                'azf' => 'application/vnd.airzip.filesecure.azf',
                'azs' => 'application/vnd.airzip.filesecure.azs',
                'azw' => 'application/vnd.amazon.ebook',
                'bas' => 'text/plain',
                'bcpio' => 'application/x-bcpio',
                'bdf' => 'application/x-font-bdf',
                'bdm' => 'application/vnd.syncml.dm+wbxml',
                'bed' => 'application/vnd.realvnc.bed',
                'bh2' => 'application/vnd.fujitsu.oasysprs',
                'bin' => 'application/octet-stream',
                'blb' => 'application/x-blorb',
                'bmi' => 'application/vnd.bmi',
                'bmp' => 'image/bmp',
                'box' => 'application/vnd.previewsystems.box',
                'btif' => 'image/prs.btif',
                'bz' => 'application/x-bzip',
                'bz2' => 'application/x-bzip2',
                'c' => 'text/plain',
                'c11amc' => 'application/vnd.cluetrust.cartomobile-config',
                'c11amz' => 'application/vnd.cluetrust.cartomobile-config-pkg',
                'c4g' => 'application/vnd.clonk.c4group',
                'cab' => 'application/vnd.ms-cab-compressed',
                'caf' => 'audio/x-caf',
                'car' => 'application/vnd.curl.car',
                'cat' => 'application/vnd.ms-pkiseccat',
                'cbr' => 'application/x-cbr',
                'ccxml' => 'application/ccxml+xml',
                'cdbcmsg' => 'application/vnd.contact.cmsg',
                'cdf' => 'application/x-cdf',
                'cdkey' => 'application/vnd.mediastation.cdkey',
                'cdmia' => 'application/cdmi-capability',
                'cdmic' => 'application/cdmi-container',
                'cdmid' => 'application/cdmi-domain',
                'cdmio' => 'application/cdmi-object',
                'cdmiq' => 'application/cdmi-queue',
                'cdx' => 'chemical/x-cdx',
                'cdxml' => 'application/vnd.chemdraw+xml',
                'cdy' => 'application/vnd.cinderella',
                'cer' => 'application/x-x509-ca-cert',
                'cfs' => 'application/x-cfs-compressed',
                'cgm' => 'image/cgm',
                'chat' => 'application/x-chat',
                'chm' => 'application/vnd.ms-htmlhelp',
                'chrt' => 'application/vnd.kde.kchart',
                'cif' => 'chemical/x-cif',
                'cii' => 'application/vnd.anser-web-certificate-issue-initiation',
                'cil' => 'application/vnd.ms-artgalry',
                'cla' => 'application/vnd.claymore',
                'class' => 'application/octet-stream',
                'clkk' => 'application/vnd.crick.clicker.keyboard',
                'clkp' => 'application/vnd.crick.clicker.palette',
                'clkt' => 'application/vnd.crick.clicker.template',
                'clkw' => 'application/vnd.crick.clicker.wordbank',
                'clkx' => 'application/vnd.crick.clicker',
                'clp' => 'application/x-msclip',
                'cmc' => 'application/vnd.cosmocaller',
                'cmdf' => 'chemical/x-cmdf',
                'cml' => 'chemical/x-cml',
                'cmp' => 'application/vnd.yellowriver-custom-menu',
                'cmx' => 'image/x-cmx',
                'cod' => 'image/cis-cod',
                'cpio' => 'application/x-cpio',
                'cpt' => 'application/mac-compactpro',
                'crd' => 'application/x-mscardfile',
                'crl' => 'application/pkix-crl',
                'crt' => 'application/x-x509-ca-cert',
                'cryptonote' => 'application/vnd.rig.cryptonote',
                'csh' => 'application/x-csh',
                'csml' => 'chemical/x-csml',
                'csp' => 'application/vnd.commonspace',
                'css' => 'text/css',
                'csv' => 'text/comma-separated-values',
                'cu' => 'application/cu-seeme',
                'curl' => 'text/vnd.curl',
                'cww' => 'application/prs.cww',
                'dae' => 'model/vnd.collada+xml',
                'daf' => 'application/vnd.mobius.daf',
                'dart' => 'application/vnd.dart',
                'davmount' => 'application/davmount+xml',
                'dbk' => 'application/docbook+xml',
                'dcr' => 'application/x-director',
                'dcurl' => 'text/vnd.curl.dcurl',
                'dd2' => 'application/vnd.oma.dd2+xml',
                'ddd' => 'application/vnd.fujixerox.ddd',
                'deb' => 'application/x-debian-package',
                'der' => 'application/x-x509-ca-cert',
                'dfac' => 'application/vnd.dreamfactory',
                'dgc' => 'application/x-dgc-compressed',
                'dir' => 'application/x-director',
                'dis' => 'application/vnd.mobius.dis',
                'djvu' => 'image/vnd.djvu',
                'dll' => 'application/x-msdownload',
                'dmg' => 'application/x-apple-diskimage',
                'dms' => 'application/octet-stream',
                'dna' => 'application/vnd.dna',
                'doc' => 'application/msword',
                'docm' => 'application/vnd.ms-word.document.macroenabled.12',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'dot' => 'application/msword',
                'dotm' => 'application/vnd.ms-word.template.macroenabled.12',
                'dotx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
                'dp' => 'application/vnd.osgi.dp',
                'dpg' => 'application/vnd.dpgraph',
                'dra' => 'audio/vnd.dra',
                'dsc' => 'text/prs.lines.tag',
                'dssc' => 'application/dssc+der',
                'dtb' => 'application/x-dtbook+xml',
                'dtd' => 'application/xml-dtd',
                'dts' => 'audio/vnd.dts',
                'dtshd' => 'audio/vnd.dts.hd',
                'dvb' => 'video/vnd.dvb.file',
                'dvi' => 'application/x-dvi',
                'dwf' => 'model/vnd.dwf',
                'dwg' => 'image/vnd.dwg',
                'dxf' => 'image/vnd.dxf',
                'dxp' => 'application/vnd.spotfire.dxp',
                'dxr' => 'application/x-director',
                'ecelp4800' => 'audio/vnd.nuera.ecelp4800',
                'ecelp7470' => 'audio/vnd.nuera.ecelp7470',
                'ecelp9600' => 'audio/vnd.nuera.ecelp9600',
                'ecma' => 'application/ecmascript',
                'edm' => 'application/vnd.novadigm.edm',
                'edx' => 'application/vnd.novadigm.edx',
                'efif' => 'application/vnd.picsel',
                'ei6' => 'application/vnd.pg.osasli',
                'eml' => 'message/rfc822',
                'emma' => 'application/emma+xml',
                'eol' => 'audio/vnd.digital-winds',
                'eot' => 'application/vnd.ms-fontobject',
                'eps' => 'application/postscript',
                'epub' => 'application/epub+zip',
                'es3' => 'application/vnd.eszigno3+xml',
                'esa' => 'application/vnd.osgi.subsystem',
                'esf' => 'application/vnd.epson.esf',
                'etx' => 'text/x-setext',
                'eva' => 'application/x-eva',
                'evy' => 'application/envoy',
                'exe' => 'application/octet-stream',
                'exi' => 'application/exi',
                'ext' => 'application/vnd.novadigm.ext',
                'ez' => 'application/andrew-inset',
                'ez2' => 'application/vnd.ezpix-album',
                'ez3' => 'application/vnd.ezpix-package',
                'f' => 'text/x-fortran',
                'f4v' => 'video/x-f4v',
                'fbs' => 'image/vnd.fastbidsheet',
                'fcdt' => 'application/vnd.adobe.formscentral.fcdt',
                'fcs' => 'application/vnd.isac.fcs',
                'fdf' => 'application/vnd.fdf',
                'fe_launch' => 'application/vnd.denovo.fcselayout-link',
                'fg5' => 'application/vnd.fujitsu.oasysgp',
                'fh' => 'image/x-freehand',
                'fif' => 'application/fractals',
                'fig' => 'application/x-xfig',
                'flac' => 'audio/x-flac',
                'fli' => 'video/x-fli',
                'flo' => 'application/vnd.micrografx.flo',
                'flr' => 'x-world/x-vrml',
                'flv' => 'video/x-flv',
                'flw' => 'application/vnd.kde.kivio',
                'flx' => 'text/vnd.fmi.flexstor',
                'fly' => 'text/vnd.fly',
                'fm' => 'application/vnd.framemaker',
                'fnc' => 'application/vnd.frogans.fnc',
                'fpx' => 'image/vnd.fpx',
                'fsc' => 'application/vnd.fsc.weblaunch',
                'fst' => 'image/vnd.fst',
                'ftc' => 'application/vnd.fluxtime.clip',
                'fti' => 'application/vnd.anser-web-funds-transfer-initiation',
                'fvt' => 'video/vnd.fvt',
                'fxp' => 'application/vnd.adobe.fxp',
                'fzs' => 'application/vnd.fuzzysheet',
                'g2w' => 'application/vnd.geoplan',
                'g3' => 'image/g3fax',
                'g3w' => 'application/vnd.geospace',
                'gac' => 'application/vnd.groove-account',
                'gam' => 'application/x-tads',
                'gbr' => 'application/rpki-ghostbusters',
                'gca' => 'application/x-gca-compressed',
                'gdl' => 'model/vnd.gdl',
                'geo' => 'application/vnd.dynageo',
                'gex' => 'application/vnd.geometry-explorer',
                'ggb' => 'application/vnd.geogebra.file',
                'ggt' => 'application/vnd.geogebra.tool',
                'ghf' => 'application/vnd.groove-help',
                'gif' => 'image/gif',
                'gim' => 'application/vnd.groove-identity-message',
                'gml' => 'application/gml+xml',
                'gmx' => 'application/vnd.gmx',
                'gnumeric' => 'application/x-gnumeric',
                'gph' => 'application/vnd.flographit',
                'gpx' => 'application/gpx+xml',
                'gqf' => 'application/vnd.grafeq',
                'gram' => 'application/srgs',
                'gramps' => 'application/x-gramps-xml',
                'grv' => 'application/vnd.groove-injector',
                'grxml' => 'application/srgs+xml',
                'gsf' => 'application/x-font-ghostscript',
                'gtar' => 'application/x-gtar',
                'gtm' => 'application/vnd.groove-tool-message',
                'gtw' => 'model/vnd.gtw',
                'gv' => 'text/vnd.graphviz',
                'gxf' => 'application/gxf',
                'gxt' => 'application/vnd.geonext',
                'gz' => 'application/x-gzip',
                'h' => 'text/plain',
                'h261' => 'video/h261',
                'h263' => 'video/h263',
                'h264' => 'video/h264',
                'hal' => 'application/vnd.hal+xml',
                'hbci' => 'application/vnd.hbci',
                'hdf' => 'application/x-hdf',
                'hlp' => 'application/winhlp',
                'hpgl' => 'application/vnd.hp-hpgl',
                'hpid' => 'application/vnd.hp-hpid',
                'hps' => 'application/vnd.hp-hps',
                'hqx' => 'application/mac-binhex40',
                'hta' => 'application/hta',
                'htc' => 'text/x-component',
                'htke' => 'application/vnd.kenameaapp',
                'htm' => 'text/html',
                'html' => 'text/html',
                'htt' => 'text/webviewhtml',
                'hvd' => 'application/vnd.yamaha.hv-dic',
                'hvp' => 'application/vnd.yamaha.hv-voice',
                'hvs' => 'application/vnd.yamaha.hv-script',
                'i2g' => 'application/vnd.intergeo',
                'icc' => 'application/vnd.iccprofile',
                'ice' => 'x-conference/x-cooltalk',
                'ico' => 'image/x-icon',
                'ics' => 'text/calendar',
                'ief' => 'image/ief',
                'ifm' => 'application/vnd.shana.informed.formdata',
                'igl' => 'application/vnd.igloader',
                'igm' => 'application/vnd.insors.igm',
                'igs' => 'model/iges',
                'igx' => 'application/vnd.micrografx.igx',
                'iif' => 'application/vnd.shana.informed.interchange',
                'iii' => 'application/x-iphone',
                'imp' => 'application/vnd.accpac.simply.imp',
                'ims' => 'application/vnd.ms-ims',
                'ink' => 'application/inkml+xml',
                'ins' => 'application/x-internet-signup',
                'install' => 'application/x-install-instructions',
                'iota' => 'application/vnd.astraea-software.iota',
                'ipfix' => 'application/ipfix',
                'ipk' => 'application/vnd.shana.informed.package',
                'irm' => 'application/vnd.ibm.rights-management',
                'irp' => 'application/vnd.irepository.package+xml',
                'iso' => 'application/x-iso9660-image',
                'isp' => 'application/x-internet-signup',
                'itp' => 'application/vnd.shana.informed.formtemplate',
                'ivp' => 'application/vnd.immervision-ivp',
                'ivu' => 'application/vnd.immervision-ivu',
                'jad' => 'text/vnd.sun.j2me.app-descriptor',
                'jam' => 'application/vnd.jam',
                'jar' => 'application/java-archive',
                'java' => 'text/x-java-source',
                'jfif' => 'image/pipeg',
                'jisp' => 'application/vnd.jisp',
                'jlt' => 'application/vnd.hp-jlyt',
                'jnlp' => 'application/x-java-jnlp-file',
                'joda' => 'application/vnd.joost.joda-archive',
                'jpe' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'jpg' => 'image/jpeg',
                'jpgv' => 'video/jpeg',
                'jpm' => 'video/jpm',
                'js' => 'application/x-javascript',
                'json' => 'application/json',
                'jsonml' => 'application/jsonml+json',
                'karbon' => 'application/vnd.kde.karbon',
                'kfo' => 'application/vnd.kde.kformula',
                'kia' => 'application/vnd.kidspiration',
                'kml' => 'application/vnd.google-earth.kml+xml',
                'kmz' => 'application/vnd.google-earth.kmz',
                'kne' => 'application/vnd.kinar',
                'kon' => 'application/vnd.kde.kontour',
                'kpr' => 'application/vnd.kde.kpresenter',
                'kpxx' => 'application/vnd.ds-keypoint',
                'ksp' => 'application/vnd.kde.kspread',
                'ktx' => 'image/ktx',
                'ktz' => 'application/vnd.kahootz',
                'kwd' => 'application/vnd.kde.kword',
                'lasxml' => 'application/vnd.las.las+xml',
                'latex' => 'application/x-latex',
                'lbd' => 'application/vnd.llamagraphics.life-balance.desktop',
                'lbe' => 'application/vnd.llamagraphics.life-balance.exchange+xml',
                'les' => 'application/vnd.hhe.lesson-player',
                'lha' => 'application/octet-stream',
                'link66' => 'application/vnd.route66.link66+xml',
                'lnk' => 'application/x-ms-shortcut',
                'log' => 'text/plain',
                'lostxml' => 'application/lost+xml',
                'lrm' => 'application/vnd.ms-lrm',
                'lsf' => 'video/x-la-asf',
                'lsx' => 'video/x-la-asf',
                'ltf' => 'application/vnd.frogans.ltf',
                'lvp' => 'audio/vnd.lucent.voice',
                'lwp' => 'application/vnd.lotus-wordpro',
                'lzh' => 'application/octet-stream',
                'm13' => 'application/x-msmediaview',
                'm14' => 'application/x-msmediaview',
                'm21' => 'application/mp21',
                'm3u' => 'audio/x-mpegurl',
                'm3u8' => 'application/vnd.apple.mpegurl',
                'm4v' => 'video/x-m4v',
                'ma' => 'application/mathematica',
                'mads' => 'application/mads+xml',
                'mag' => 'application/vnd.ecowin.chart',
                'man' => 'application/x-troff-man',
                'mathml' => 'application/mathml+xml',
                'mbk' => 'application/vnd.mobius.mbk',
                'mbox' => 'application/mbox',
                'mc1' => 'application/vnd.medcalcdata',
                'mcd' => 'application/vnd.mcd',
                'mcurl' => 'text/vnd.curl.mcurl',
                'mdb' => 'application/x-msaccess',
                'mdi' => 'image/vnd.ms-modi',
                'me' => 'application/x-troff-me',
                'meta4' => 'application/metalink4+xml',
                'metalink' => 'application/metalink+xml',
                'mets' => 'application/mets+xml',
                'mfm' => 'application/vnd.mfmp',
                'mft' => 'application/rpki-manifest',
                'mgp' => 'application/vnd.osgeo.mapguide.package',
                'mgz' => 'application/vnd.proteus.magazine',
                'mht' => 'message/rfc822',
                'mhtml' => 'message/rfc822',
                'mid' => 'audio/mid',
                'mie' => 'application/x-mie',
                'mif' => 'application/vnd.mif',
                'mj2' => 'video/mj2',
                'mka' => 'audio/x-matroska',
                'mkv' => 'video/x-matroska',
                'mlp' => 'application/vnd.dolby.mlp',
                'mmd' => 'application/vnd.chipnuts.karaoke-mmd',
                'mmf' => 'application/vnd.smaf',
                'mmr' => 'image/vnd.fujixerox.edmics-mmr',
                'mng' => 'video/x-mng',
                'mny' => 'application/x-msmoney',
                'mods' => 'application/mods+xml',
                'mov' => 'video/quicktime',
                'movie' => 'video/x-sgi-movie',
                'mp2' => 'video/mpeg',
                'mp3' => 'audio/mpeg',
                'mp4' => 'video/mp4',
                'mp4a' => 'audio/mp4',
                'mp4s' => 'application/mp4',
                'mpa' => 'video/mpeg',
                'mpc' => 'application/vnd.mophun.certificate',
                'mpe' => 'video/mpeg',
                'mpeg' => 'video/mpeg',
                'mpg' => 'video/mpeg',
                'mpga' => 'audio/mpeg',
                'mpkg' => 'application/vnd.apple.installer+xml',
                'mpm' => 'application/vnd.blueice.multipass',
                'mpn' => 'application/vnd.mophun.application',
                'mpp' => 'application/vnd.ms-project',
                'mpv2' => 'video/mpeg',
                'mpy' => 'application/vnd.ibm.minipay',
                'mqy' => 'application/vnd.mobius.mqy',
                'mrc' => 'application/marc',
                'mrcx' => 'application/marcxml+xml',
                'ms' => 'application/x-troff-ms',
                'mscml' => 'application/mediaservercontrol+xml',
                'mseed' => 'application/vnd.fdsn.mseed',
                'mseq' => 'application/vnd.mseq',
                'msf' => 'application/vnd.epson.msf',
                'msh' => 'model/mesh',
                'msl' => 'application/vnd.mobius.msl',
                'msty' => 'application/vnd.muvee.style',
                'mts' => 'model/vnd.mts',
                'mus' => 'application/vnd.musician',
                'musicxml' => 'application/vnd.recordare.musicxml+xml',
                'mvb' => 'application/x-msmediaview',
                'mwf' => 'application/vnd.mfer',
                'mxf' => 'application/mxf',
                'mxl' => 'application/vnd.recordare.musicxml',
                'mxml' => 'application/xv+xml',
                'mxs' => 'application/vnd.triscape.mxs',
                'mxu' => 'video/vnd.mpegurl',
                'n-gage' => 'application/vnd.nokia.n-gage.symbian.install',
                'n3' => 'text/n3',
                'nbp' => 'application/vnd.wolfram.player',
                'nc' => 'application/x-netcdf',
                'ncx' => 'application/x-dtbncx+xml',
                'nfo' => 'text/x-nfo',
                'ngdat' => 'application/vnd.nokia.n-gage.data',
                'nlu' => 'application/vnd.neurolanguage.nlu',
                'nml' => 'application/vnd.enliven',
                'nnd' => 'application/vnd.noblenet-directory',
                'nns' => 'application/vnd.noblenet-sealer',
                'nnw' => 'application/vnd.noblenet-web',
                'npx' => 'image/vnd.net-fpx',
                'nsc' => 'application/x-conference',
                'nsf' => 'application/vnd.lotus-notes',
                'ntf' => 'application/vnd.nitf',
                'nws' => 'message/rfc822',
                'nzb' => 'application/x-nzb',
                'oa2' => 'application/vnd.fujitsu.oasys2',
                'oa3' => 'application/vnd.fujitsu.oasys3',
                'oas' => 'application/vnd.fujitsu.oasys',
                'obd' => 'application/x-msbinder',
                'obj' => 'application/x-tgif',
                'oda' => 'application/oda',
                'odb' => 'application/vnd.oasis.opendocument.database',
                'odc' => 'application/vnd.oasis.opendocument.chart',
                'odf' => 'application/vnd.oasis.opendocument.formula',
                'odft' => 'application/vnd.oasis.opendocument.formula-template',
                'odg' => 'application/vnd.oasis.opendocument.graphics',
                'odi' => 'application/vnd.oasis.opendocument.image',
                'odm' => 'application/vnd.oasis.opendocument.text-master',
                'odp' => 'application/vnd.oasis.opendocument.presentation',
                'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
                'odt' => 'application/vnd.oasis.opendocument.text',
                'oga' => 'audio/ogg',
                'ogv' => 'video/ogg',
                'ogx' => 'application/ogg',
                'omdoc' => 'application/omdoc+xml',
                'onetoc' => 'application/onenote',
                'opf' => 'application/oebps-package+xml',
                'opml' => 'text/x-opml',
                'org' => 'application/vnd.lotus-organizer',
                'osf' => 'application/vnd.yamaha.openscoreformat',
                'osfpvg' => 'application/vnd.yamaha.openscoreformat.osfpvg+xml',
                'otc' => 'application/vnd.oasis.opendocument.chart-template',
                'otf' => 'application/x-font-otf',
                'otg' => 'application/vnd.oasis.opendocument.graphics-template',
                'oth' => 'application/vnd.oasis.opendocument.text-web',
                'oti' => 'application/vnd.oasis.opendocument.image-template',
                'otp' => 'application/vnd.oasis.opendocument.presentation-template',
                'ots' => 'application/vnd.oasis.opendocument.spreadsheet-template',
                'ott' => 'application/vnd.oasis.opendocument.text-template',
                'oxps' => 'application/oxps',
                'oxt' => 'application/vnd.openofficeorg.extension',
                'p' => 'text/x-pascal',
                'p10' => 'application/pkcs10',
                'p12' => 'application/x-pkcs12',
                'p7b' => 'application/x-pkcs7-certificates',
                'p7c' => 'application/x-pkcs7-mime',
                'p7m' => 'application/x-pkcs7-mime',
                'p7r' => 'application/x-pkcs7-certreqresp',
                'p7s' => 'application/x-pkcs7-signature',
                'p8' => 'application/pkcs8',
                'paw' => 'application/vnd.pawaafile',
                'pbd' => 'application/vnd.powerbuilder6',
                'pbm' => 'image/x-portable-bitmap',
                'pcap' => 'application/vnd.tcpdump.pcap',
                'pcf' => 'application/x-font-pcf',
                'pcl' => 'application/vnd.hp-pcl',
                'pclxl' => 'application/vnd.hp-pclxl',
                'pcurl' => 'application/vnd.curl.pcurl',
                'pcx' => 'image/x-pcx',
                'pdb' => 'application/vnd.palm',
                'pdf' => 'application/pdf',
                'pfa' => 'application/x-font-type1',
                'pfr' => 'application/font-tdpfr',
                'pfx' => 'application/x-pkcs12',
                'pgm' => 'image/x-portable-graymap',
                'pgn' => 'application/x-chess-pgn',
                'pgp' => 'application/pgp-encrypted',
                'pic' => 'image/x-pict',
                'pki' => 'application/pkixcmp',
                'pkipath' => 'application/pkix-pkipath',
                'pko' => 'application/ynd.ms-pkipko',
                'plb' => 'application/vnd.3gpp.pic-bw-large',
                'plc' => 'application/vnd.mobius.plc',
                'plf' => 'application/vnd.pocketlearn',
                'pls' => 'application/pls+xml',
                'pma' => 'application/x-perfmon',
                'pmc' => 'application/x-perfmon',
                'pml' => 'application/x-perfmon',
                'pmr' => 'application/x-perfmon',
                'pmw' => 'application/x-perfmon',
                'png' => 'image/png',
                'pnm' => 'image/x-portable-anymap',
                'portpkg' => 'application/vnd.macports.portpkg',
                'pot' => 'application/vnd.ms-powerpoint',
                'potm' => 'application/vnd.ms-powerpoint.template.macroenabled.12',
                'potx' => 'application/vnd.openxmlformats-officedocument.presentationml.template',
                'ppam' => 'application/vnd.ms-powerpoint.addin.macroenabled.12',
                'ppd' => 'application/vnd.cups-ppd',
                'ppm' => 'image/x-portable-pixmap',
                'pps' => 'application/vnd.ms-powerpoint',
                'ppsm' => 'application/vnd.ms-powerpoint.slideshow.macroenabled.12',
                'ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
                'ppt' => 'application/vnd.ms-powerpoint',
                'pptm' => 'application/vnd.ms-powerpoint.presentation.macroenabled.12',
                'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'prc' => 'application/x-mobipocket-ebook',
                'pre' => 'application/vnd.lotus-freelance',
                'prf' => 'application/pics-rules',
                'ps' => 'application/postscript',
                'psb' => 'application/vnd.3gpp.pic-bw-small',
                'psd' => 'image/photoshop',
                'psf' => 'application/x-font-linux-psf',
                'pskcxml' => 'application/pskc+xml',
                'ptid' => 'application/vnd.pvi.ptid1',
                'pub' => 'application/x-mspublisher',
                'pvb' => 'application/vnd.3gpp.pic-bw-var',
                'pwn' => 'application/vnd.3m.post-it-notes',
                'pya' => 'audio/vnd.ms-playready.media.pya',
                'pyv' => 'video/vnd.ms-playready.media.pyv',
                'qam' => 'application/vnd.epson.quickanime',
                'qbo' => 'application/vnd.intu.qbo',
                'qfx' => 'application/vnd.intu.qfx',
                'qps' => 'application/vnd.publishare-delta-tree',
                'qt' => 'video/quicktime',
                'qxd' => 'application/vnd.quark.quarkxpress',
                'ra' => 'audio/x-pn-realaudio',
                'ram' => 'audio/x-pn-realaudio',
                'rar' => 'application/x-rar-compressed',
                'ras' => 'image/x-cmu-raster',
                'rcprofile' => 'application/vnd.ipunplugged.rcprofile',
                'rdf' => 'application/rdf+xml',
                'rdz' => 'application/vnd.data-vision.rdz',
                'rep' => 'application/vnd.businessobjects',
                'res' => 'application/x-dtbresource+xml',
                'rgb' => 'image/x-rgb',
                'rif' => 'application/reginfo+xml',
                'rip' => 'audio/vnd.rip',
                'ris' => 'application/x-research-info-systems',
                'rl' => 'application/resource-lists+xml',
                'rlc' => 'image/vnd.fujixerox.edmics-rlc',
                'rld' => 'application/resource-lists-diff+xml',
                'rm' => 'application/vnd.rn-realmedia',
                'rmi' => 'audio/mid',
                'rmp' => 'audio/x-pn-realaudio-plugin',
                'rms' => 'application/vnd.jcp.javame.midlet-rms',
                'rmvb' => 'application/vnd.rn-realmedia-vbr',
                'rnc' => 'application/relax-ng-compact-syntax',
                'roa' => 'application/rpki-roa',
                'roff' => 'application/x-troff',
                'rp9' => 'application/vnd.cloanto.rp9',
                'rpss' => 'application/vnd.nokia.radio-presets',
                'rpst' => 'application/vnd.nokia.radio-preset',
                'rq' => 'application/sparql-query',
                'rs' => 'application/rls-services+xml',
                'rsd' => 'application/rsd+xml',
                'rss' => 'application/rss+xml',
                'rtf' => 'application/rtf',
                'rtx' => 'text/richtext',
                's' => 'text/x-asm',
                's3m' => 'audio/s3m',
                'saf' => 'application/vnd.yamaha.smaf-audio',
                'sbml' => 'application/sbml+xml',
                'sc' => 'application/vnd.ibm.secure-container',
                'scd' => 'application/x-msschedule',
                'scm' => 'application/vnd.lotus-screencam',
                'scq' => 'application/scvp-cv-request',
                'scs' => 'application/scvp-cv-response',
                'sct' => 'text/scriptlet',
                'scurl' => 'text/vnd.curl.scurl',
                'sda' => 'application/vnd.stardivision.draw',
                'sdc' => 'application/vnd.stardivision.calc',
                'sdd' => 'application/vnd.stardivision.impress',
                'sdkm' => 'application/vnd.solent.sdkm+xml',
                'sdp' => 'application/sdp',
                'sdw' => 'application/vnd.stardivision.writer',
                'see' => 'application/vnd.seemail',
                'seed' => 'application/vnd.fdsn.seed',
                'sema' => 'application/vnd.sema',
                'semd' => 'application/vnd.semd',
                'semf' => 'application/vnd.semf',
                'ser' => 'application/java-serialized-object',
                'setpay' => 'application/set-payment-initiation',
                'setreg' => 'application/set-registration-initiation',
                'sfd-hdstx' => 'application/vnd.hydrostatix.sof-data',
                'sfs' => 'application/vnd.spotfire.sfs',
                'sfv' => 'text/x-sfv',
                'sgi' => 'image/sgi',
                'sgl' => 'application/vnd.stardivision.writer-global',
                'sgml' => 'text/sgml',
                'sh' => 'application/x-sh',
                'shar' => 'application/x-shar',
                'shf' => 'application/shf+xml',
                'sid' => 'image/x-mrsid-image',
                'sil' => 'audio/silk',
                'sis' => 'application/vnd.symbian.install',
                'sit' => 'application/x-stuffit',
                'sitx' => 'application/x-stuffitx',
                'skp' => 'application/vnd.koan',
                'sldm' => 'application/vnd.ms-powerpoint.slide.macroenabled.12',
                'sldx' => 'application/vnd.openxmlformats-officedocument.presentationml.slide',
                'slt' => 'application/vnd.epson.salt',
                'sm' => 'application/vnd.stepmania.stepchart',
                'smf' => 'application/vnd.stardivision.math',
                'smi' => 'application/smil+xml',
                'smv' => 'video/x-smv',
                'smzip' => 'application/vnd.stepmania.package',
                'snd' => 'audio/basic',
                'snf' => 'application/x-font-snf',
                'spc' => 'application/x-pkcs7-certificates',
                'spf' => 'application/vnd.yamaha.smaf-phrase',
                'spl' => 'application/futuresplash',
                'spot' => 'text/vnd.in3d.spot',
                'spp' => 'application/scvp-vp-response',
                'spq' => 'application/scvp-vp-request',
                'sql' => 'application/x-sql',
                'src' => 'application/x-wais-source',
                'srt' => 'application/x-subrip',
                'sru' => 'application/sru+xml',
                'srx' => 'application/sparql-results+xml',
                'ssdl' => 'application/ssdl+xml',
                'sse' => 'application/vnd.kodak-descriptor',
                'ssf' => 'application/vnd.epson.ssf',
                'ssml' => 'application/ssml+xml',
                'sst' => 'application/vnd.ms-pkicertstore',
                'st' => 'application/vnd.sailingtracker.track',
                'stc' => 'application/vnd.sun.xml.calc.template',
                'std' => 'application/vnd.sun.xml.draw.template',
                'stf' => 'application/vnd.wt.stf',
                'sti' => 'application/vnd.sun.xml.impress.template',
                'stk' => 'application/hyperstudio',
                'stl' => 'application/vnd.ms-pkistl',
                'stm' => 'text/html',
                'str' => 'application/vnd.pg.format',
                'stw' => 'application/vnd.sun.xml.writer.template',
                'sub' => 'image/vnd.dvb.subtitle',
                'sus' => 'application/vnd.sus-calendar',
                'sv4cpio' => 'application/x-sv4cpio',
                'sv4crc' => 'application/x-sv4crc',
                'svc' => 'application/vnd.dvb.service',
                'svd' => 'application/vnd.svd',
                'svg' => 'image/svg+xml',
                'swf' => 'application/x-shockwave-flash',
                'swi' => 'application/vnd.aristanetworks.swi',
                'sxc' => 'application/vnd.sun.xml.calc',
                'sxd' => 'application/vnd.sun.xml.draw',
                'sxg' => 'application/vnd.sun.xml.writer.global',
                'sxi' => 'application/vnd.sun.xml.impress',
                'sxm' => 'application/vnd.sun.xml.math',
                'sxw' => 'application/vnd.sun.xml.writer',
                't' => 'application/x-troff',
                't3' => 'application/x-t3vm-image',
                'taglet' => 'application/vnd.mynfc',
                'tao' => 'application/vnd.tao.intent-module-archive',
                'tar' => 'application/x-tar',
                'tcap' => 'application/vnd.3gpp2.tcap',
                'tcl' => 'application/x-tcl',
                'teacher' => 'application/vnd.smart.teacher',
                'tei' => 'application/tei+xml',
                'tex' => 'application/x-tex',
                'texi' => 'application/x-texinfo',
                'texinfo' => 'application/x-texinfo',
                'tfi' => 'application/thraud+xml',
                'tfm' => 'application/x-tex-tfm',
                'tga' => 'image/x-tga',
                'tgz' => 'application/x-compressed',
                'thmx' => 'application/vnd.ms-officetheme',
                'tif' => 'image/tiff',
                'tiff' => 'image/tiff',
                'tmo' => 'application/vnd.tmobile-livetv',
                'torrent' => 'application/x-bittorrent',
                'tpl' => 'application/vnd.groove-tool-template',
                'tpt' => 'application/vnd.trid.tpt',
                'tr' => 'application/x-troff',
                'tra' => 'application/vnd.trueapp',
                'trm' => 'application/x-msterminal',
                'tsd' => 'application/timestamped-data',
                'tsv' => 'text/tab-separated-values',
                'ttf' => 'application/x-font-ttf',
                'ttl' => 'text/turtle',
                'twd' => 'application/vnd.simtech-mindmapper',
                'txd' => 'application/vnd.genomatix.tuxedo',
                'txf' => 'application/vnd.mobius.txf',
                'txt' => 'text/plain',
                'ufd' => 'application/vnd.ufdl',
                'uls' => 'text/iuls',
                'ulx' => 'application/x-glulx',
                'umj' => 'application/vnd.umajin',
                'unityweb' => 'application/vnd.unity',
                'uoml' => 'application/vnd.uoml+xml',
                'uri' => 'text/uri-list',
                'ustar' => 'application/x-ustar',
                'utz' => 'application/vnd.uiq.theme',
                'uu' => 'text/x-uuencode',
                'uva' => 'audio/vnd.dece.audio',
                'uvf' => 'application/vnd.dece.data',
                'uvh' => 'video/vnd.dece.hd',
                'uvi' => 'image/vnd.dece.graphic',
                'uvm' => 'video/vnd.dece.mobile',
                'uvp' => 'video/vnd.dece.pd',
                'uvs' => 'video/vnd.dece.sd',
                'uvt' => 'application/vnd.dece.ttml+xml',
                'uvu' => 'video/vnd.uvvu.mp4',
                'uvv' => 'video/vnd.dece.video',
                'uvx' => 'application/vnd.dece.unspecified',
                'uvz' => 'application/vnd.dece.zip',
                'vcard' => 'text/vcard',
                'vcd' => 'application/x-cdlink',
                'vcf' => 'text/x-vcard',
                'vcg' => 'application/vnd.groove-vcard',
                'vcs' => 'text/x-vcalendar',
                'vcx' => 'application/vnd.vcx',
                'vis' => 'application/vnd.visionary',
                'viv' => 'video/vnd.vivo',
                'vob' => 'video/x-ms-vob',
                'vrml' => 'x-world/x-vrml',
                'vsd' => 'application/vnd.visio',
                'vsf' => 'application/vnd.vsf',
                'vtu' => 'model/vnd.vtu',
                'vxml' => 'application/voicexml+xml',
                'wad' => 'application/x-doom',
                'wav' => 'audio/x-wav',
                'wax' => 'audio/x-ms-wax',
                'wbmp' => 'image/vnd.wap.wbmp',
                'wbs' => 'application/vnd.criticaltools.wbs+xml',
                'wbxml' => 'application/vnd.wap.wbxml',
                'wcm' => 'application/vnd.ms-works',
                'wdb' => 'application/vnd.ms-works',
                'wdp' => 'image/vnd.ms-photo',
                'weba' => 'audio/webm',
                'webm' => 'video/webm',
                'webp' => 'image/webp',
                'wg' => 'application/vnd.pmi.widget',
                'wgt' => 'application/widget',
                'wks' => 'application/vnd.ms-works',
                'wm' => 'video/x-ms-wm',
                'wma' => 'audio/x-ms-wma',
                'wmd' => 'application/x-ms-wmd',
                'wmf' => 'application/x-msmetafile',
                'wml' => 'text/vnd.wap.wml',
                'wmlc' => 'application/vnd.wap.wmlc',
                'wmls' => 'text/vnd.wap.wmlscript',
                'wmlsc' => 'application/vnd.wap.wmlscriptc',
                'wmv' => 'video/x-ms-wmv',
                'wmx' => 'video/x-ms-wmx',
                'wmz' => 'application/x-ms-wmz',
                'woff' => 'application/x-font-woff',
                'wpd' => 'application/vnd.wordperfect',
                'wpl' => 'application/vnd.ms-wpl',
                'wps' => 'application/vnd.ms-works',
                'wqd' => 'application/vnd.wqd',
                'wri' => 'application/x-mswrite',
                'wrl' => 'x-world/x-vrml',
                'wrz' => 'x-world/x-vrml',
                'wsdl' => 'application/wsdl+xml',
                'wspolicy' => 'application/wspolicy+xml',
                'wtb' => 'application/vnd.webturbo',
                'wvx' => 'video/x-ms-wvx',
                'x3d' => 'model/x3d+xml',
                'x3db' => 'model/x3d+binary',
                'x3dv' => 'model/x3d+vrml',
                'xaf' => 'x-world/x-vrml',
                'xaml' => 'application/xaml+xml',
                'xap' => 'application/x-silverlight-app',
                'xar' => 'application/vnd.xara',
                'xbap' => 'application/x-ms-xbap',
                'xbd' => 'application/vnd.fujixerox.docuworks.binder',
                'xbm' => 'image/x-xbitmap',
                'xdf' => 'application/xcap-diff+xml',
                'xdm' => 'application/vnd.syncml.dm+xml',
                'xdp' => 'application/vnd.adobe.xdp+xml',
                'xdssc' => 'application/dssc+xml',
                'xdw' => 'application/vnd.fujixerox.docuworks',
                'xenc' => 'application/xenc+xml',
                'xer' => 'application/patch-ops-error+xml',
                'xfdf' => 'application/vnd.adobe.xfdf',
                'xfdl' => 'application/vnd.xfdl',
                'xhtml' => 'application/xhtml+xml',
                'xif' => 'image/vnd.xiff',
                'xla' => 'application/vnd.ms-excel',
                'xlam' => 'application/vnd.ms-excel.addin.macroenabled.12',
                'xlc' => 'application/vnd.ms-excel',
                'xlf' => 'application/x-xliff+xml',
                'xlm' => 'application/vnd.ms-excel',
                'xls' => 'application/vnd.ms-excel',
                'xlsb' => 'application/vnd.ms-excel.sheet.binary.macroenabled.12',
                'xlsm' => 'application/vnd.ms-excel.sheet.macroenabled.12',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'xlt' => 'application/vnd.ms-excel',
                'xltm' => 'application/vnd.ms-excel.template.macroenabled.12',
                'xltx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
                'xlw' => 'application/vnd.ms-excel',
                'xm' => 'audio/xm',
                'xml' => 'text/xml',
                'xo' => 'application/vnd.olpc-sugar',
                'xof' => 'x-world/x-vrml',
                'xop' => 'application/xop+xml',
                'xpi' => 'application/x-xpinstall',
                'xpl' => 'application/xproc+xml',
                'xpm' => 'image/x-xpixmap',
                'xpr' => 'application/vnd.is-xpr',
                'xps' => 'application/vnd.ms-xpsdocument',
                'xpw' => 'application/vnd.intercon.formnet',
                'xslt' => 'application/xslt+xml',
                'xsm' => 'application/vnd.syncml+xml',
                'xspf' => 'application/xspf+xml',
                'xul' => 'application/vnd.mozilla.xul+xml',
                'xwd' => 'image/x-xwindowdump',
                'xyz' => 'chemical/x-xyz',
                'xz' => 'application/x-xz',
                'yang' => 'application/yang',
                'yin' => 'application/yin+xml',
                'z' => 'application/x-compress',
                'z1' => 'application/x-zmachine',
                'zaz' => 'application/vnd.zzazz.deck+xml',
                'zip' => 'application/zip',
                'zir' => 'application/vnd.zul',
                'zmm' => 'application/vnd.handheld-entertainment+xml',
            );
        }

        $mimetype = 'application/octet-stream';

        if (isset($mimetypes[strtolower($extension)])) {
            $mimetype = $mimetypes[strtolower($extension)];
        }

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
