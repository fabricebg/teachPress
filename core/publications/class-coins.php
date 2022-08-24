<?php

/**
 * This file contains logic to export in Coins format.
 *
 * Adapted from the work of Alec Smecher at
 * https://github.com/pkp/coins/blob/main/CoinsPlugin.inc.php
 * and
 * https://github.com/zotero/utilities/blob/master/openurl.js
 *
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2 or later
 * @since 9.0.0
 */

/**
 * teachPress Coins class
 *
 * @package teachpress\core\bibtex
 * @since 9.0.0
 */
class TP_Coins {
    
    /**
     * Gets a single publication in coins format
     * Adapted from the work of Alec Smecher at
     * https://github.com/pkp/coins/blob/main/CoinsPlugin.inc.php
     * @param array $row
     * @return string
     * @since 9.0.0
    */
    public static function get_single_publication_coins($row) {
        $vars = array(
            array('ctx_ver', 'Z39.88-2004'),
//            array('rfr_id', $request->url(null, 'article', 'view', $article->getId())),
            array('rft_val_fmt', 'info:ofi/fmt:kev:mtx:journal'),
//            array('rft.language', $article->getLocale()),
//            array('rft.genre', 'article'),
//            array('rft.title', $journal->getLocalizedName()),
//            array('rft.jtitle', $journal->getLocalizedName()),
            array('rft.atitle', $row['title']),
//            array('rft.artnum', $article->getBestArticleId()),
//            array('rft.stitle', $journal->getLocalizedSetting('abbreviation')),
//            array('rft.volume', $issue->getVolume()),
//            array('rft.issue', $issue->getNumber()),
        );

//        $authors = $publication->getData('authors');
//        if ($firstAuthor = array_shift($authors)) {
//            $vars = array_merge($vars, array(
//                array('rft.aulast', $firstAuthor->getFamilyName($article->getLocale())),
//                array('rft.aufirst', $firstAuthor->getGivenName($article->getLocale())),
//            ));
//        }
//
//        $datePublished = $article->getDatePublished();
//        if (!$datePublished) {
//            $datePublished = $issue->getDatePublished();
//        }
//
//        if ($datePublished) {
//            $vars[] = array('rft.date', date('Y-m-d', strtotime($datePublished)));
//        }
//
//        foreach ($authors as $author) {
//            $vars[] = array('rft.au', $author->getFullName());
//        }
//
//        if ($doi = $article->getStoredPubId('doi')) {
//            $vars[] = array('rft_id', 'info:doi/' . $doi);
//        }
//        if ($article->getPages()) {
//            $vars[] = array('rft.pages', $article->getPages());
//        }
//        if ($journal->getSetting('printIssn')) {
//            $vars[] = array('rft.issn', $journal->getSetting('printIssn'));
//        }
//        if ($journal->getSetting('onlineIssn')) {
//            $vars[] = array('rft.eissn', $journal->getSetting('onlineIssn'));
//        }

        $title = '';
        foreach ($vars as $entries) {
            list($name, $value) = $entries;
            $title .= $name . '=' . urlencode($value) . '&';
        }
        $title = htmlentities(substr($title, 0, -1));

        $result = "<span class=\"Z3988\" title=\"$title\"></span>\n";
        
        return $result;
    }

    public static function get_single_publication_coins2($item, $version="1.0") {
        $my_entries = array();

        $_mapTag = function($data, $tag, $dontAddPrefix, &$entries) {
            global $version;
            if($version === "1.0" && !$dontAddPrefix) $tag = "rft.".$tag;
            
            $entries[] = $tag."=".urlencode($data);
        };
        
        if($version == "1.0") {
            $_mapTag("Z39.88-2004", "url_ver", true, $my_entries);
            $_mapTag("Z39.88-2004", "ctx_ver", true, $my_entries);
            $_mapTag("info:sid/zotero.org:2", "rfr_id", true, $my_entries);
            if(array_key_exists("doi", $item)) $_mapTag("info:doi/".$item['doi'], "rft_id", true, $my_entries);
//            if(item.ISBN) _mapTag("urn:isbn:"+item.ISBN, "rft_id", true);
//            if(pmid) _mapTag("info:pmid/"+pmid, "rft_id", true);
        }
        
        if($item['type'] == "article") {
            if($version === "1.0") {
                $_mapTag("info:ofi/fmt:kev:mtx:journal", "rft_val_fmt", true, $my_entries);
            }
            $_mapTag("article", "genre", false, $my_entries);
            
            $_mapTag($item["title"], "atitle", false, $my_entries);
            $_mapTag($item["journal"], ($version == "0.1" ? "title" : "jtitle"), false, $my_entries);
            //$_mapTag(item.journalAbbreviation, "stitle");
            $_mapTag($item["volume"], "volume", false, $my_entries);
            $_mapTag($item["issue"], "issue", false, $my_entries);
            
        }
        // var_dump($my_entries);
        return sprintf('<span class="Z3988" title="%s"></span>', implode("&", $my_entries));
    }
    
    public static function get_single_publication_coins3($row) {
        global $wp;
        
        // prepare data structures
        $tag_map = new TP_Coins_Tags();
        
        // adapted logic from Zotero OpenUrl module (JavaScript)
        // encode ctx_ver and encode identifiers
        $tag_map->add("url_ver", "Z39.88-2004", true, null);
        $tag_map->add("ctx_ver", "Z39.88-2004", true, null);
        $tag_map->add("rfr_id", home_url( $wp->request ), true, null);
        $tag_map->add("rft_id", "doi", false, "info:doi/%s");
        
        if ($row['is_isbn'] == 1) {
            $tag_map->add("rft_id", "isbn", false, "urn:isbn:%s");
        }
        
        // encode genre and item-specific data
        $cur_type = trim(strtolower($row['type']));
        
        if ($cur_type == 'article') {
            $tag_map->add("rft_val_fmt", "info:ofi/fmt:kev:mtx:journal", true, null);
            $tag_map->add("rft.genre", "article", true, null);
            $tag_map->add("rft.atitle", "title", false, null);
            $tag_map->add("rft.jtitle", "journal", false, null);
            $tag_map->add("rft.volume", "volume", false, null);
            $tag_map->add("rft.issue", "issue", false, null);
        } else if ($cur_type == "book" ||
                   $cur_type == "inbook" ||
                   $cur_type == "incollection" ||
                   $cur_type == "techreport") {

            if ($cur_type == "book") {
                $tag_map->add("rft.genre", "book", true, null);
                $tag_map->add("rft.btitle", "title", true, null);
            } else if ($cur_type == "inbook" || $cur_type == "incollection") {
                $tag_map->add("rft.genre", "bookitem", true, null);
                $tag_map->add("rft.atitle", "title", true, null);
            }
        }
        
        
        
        // conditional
        $tag_map->add("rft_val_fmt", "info:ofi/fmt:kev:mtx:journal", true, null);
        

        return $tag_map->to_coins($row);
                
    }

}

class TP_Coins_Tags {
    
    function __construct() {
        $this->tag_map = array();
    }
    
    function add($key, $value, $is_constant, $pattern) {
        $this->tag_map[$key] = array($value, $is_constant, $pattern);
    }
    
    function to_coins($row) {
        $fragments = array();
        
        foreach ($this->tag_map as $key => $tag_info) {
            $value = $tag_info[0];
            $is_constant = $tag_info[1];
            $pattern = $tag_info[2];
            
            if ($is_constant) {
                $fragments[] = sprintf("%s=%s", $key, urlencode($value));
            } else {
                if (array_key_exists($value, $row)) {
                    $value = $row[$value];
                    
                    if ($pattern !== null) {
                        $value = sprintf($pattern, $value);
                    }
                    
                    $fragments[] = sprintf("%s=%s", $key, urlencode($value));
                } // otherwise, mute value
            }
        }
        
        $title = implode("&", $fragments);
        return sprintf('<span class="Z3988" title="%s"></span>', htmlentities($title));
    }
        
}
