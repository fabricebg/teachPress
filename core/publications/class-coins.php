<?php

/**
 * This file contains logic to export in Coins format.
 *
 * Adapted from Zotero's OpenURL library
 * https://github.com/zotero/utilities/blob/master/openurl.js
 *
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2 or later
 * @since 9.0.0
 */

/**
 * teachPress Coins class, used ot produce OpenURL ContextObject from items.
 *
 * @since 9.0.0
 */
class TP_Coins {
    
    /**
     * Produces a single publication in coins format, i.e.
     * Generates an OpenURL ContextObject from an item, version 1.0.
     *
     * Adapted from Zotero's OpenURL library:
     * https://github.com/zotero/utilities/blob/master/openurl.js
     * @param array $row    A row in raw format directly from the database
     * @return string   An HTML span microtagged with Coins info.
     * @since 9.0.0
    */
    public static function get_single_publication_coins($row) {
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
        // _mapTag("info:pmid/"+pmid, "rft_id", true); // not supported
        
        // encode genre and item-specific data
        $cur_type = trim(strtolower($row['type']));
        
        // TP: some teachPress types are not handled by coins and Zotero. The following
        // are necessarily imperfect mappings.
        if ($cur_type == "workshop" || $cur_type == "booklet") {
            $cur_type = "book";
        }
        
        // master switch
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
                   $cur_type == "inproceedings" ||
                   $cur_type == "techreport" ||
                   $cur_type == "proceedings") { // TP: add this type

            $tag_map->add("rft_val_fmt", "info:ofi/fmt:kev:mtx:book", true, null);
            
            if ($cur_type == "book") {
                $tag_map->add("rft.genre", "book", true, null);
                $tag_map->add("rft.btitle", "title", false, null);
            } else if ($cur_type == "inbook" || $cur_type == "incollection") {
                $tag_map->add("rft.genre", "bookitem", true, null);
                $tag_map->add("rft.atitle", "title", false, null);
                $tag_map->add("rft.btitle", "booktitle", false, null);
            } else if ($cur_type == "inproceedings") {
                $tag_map->add("rft.genre", "proceeding", true, null);
                $tag_map->add("rft.atitle", "title", false, null);
                $tag_map->add("rft.btitle", "booktitle", false, null);
            } else if ($cur_type == "techreport") {
                $tag_map->add("rft.genre", "report", true, null);
                $tag_map->add("rft.series", "series", false, null);
                $tag_map->add("rft.btitle", "title", false, null);
            } else if ($cur_type == "proceedings") {
                $tag_map->add("rft.genre", "proceedings", true, null);
                $tag_map->add("rft.title", "title", false, null);
            }

            $tag_map->add("rft.volume", "volume", false, null); // TP
            $tag_map->add("rft.number", "issue", false, null); // TP
            $tag_map->add("rft.edition", "edition", false, null); // TP
            $tag_map->add("rft.issue", "issue", false, null); // TP

            $tag_map->add("rft.place", "address", false, null);
            $tag_map->add("rft.publisher", "publisher", false, null);
            $tag_map->add("rft.editor", "editor", false, null);
            $tag_map->add("rft.series", "series", false, null);
            
        } else if ($cur_type == "mastersthesis" ||
                   $cur_type == "diplomathesis" ||
                   $cur_type == "phdthesis" ||
                   $cur_type == "bachelorthesis") {
            
            $degree = "Other";
            switch ($cur_type) {
                case "mastersthesis":
                    $degree = "M.Sc.";
                    break;
                case "diplomathesis":
                    $degree = "Diploma";
                    break;
                case "phdthesis":
                    $degree = "Ph.D.";
                    break;
                case "bachelorthesis":
                    $degree = "B.Sc.";
                    break;
            }
            
            $tag_map->add("rft_val_fmt", "info:ofi/fmt:kev:mtx:dissertation", true, null);
            $tag_map->add("rft.title", "title", false, null);
            $tag_map->add("rft.inst", "editor", false, null);
            $tag_map->add("rft.degree", $degree, false, null);
        
        } else if ($cur_type == "patent") {
            
            $tag_map->add("rft_val_fmt", "info:ofi/fmt:kev:mtx:patent", true, null);
            $tag_map->add("rft.title", "title", false, null);
            $tag_map->add("rft.assignee", "assignee", false, null); // unsupported
            $tag_map->add("rft.number", "issue", false, null);
            // $tag_map->add("rft.date", "date", false, null); // redundant with info below
            
        } else {
            // we map as much as possible to DC for all other types. This will export some info
            // and work very nicely on roundtrip. All of these fields legal for mtx:dc according to
            // http://alcme.oclc.org/openurl/servlet/OAIHandler/extension?verb=GetMetadata&metadataPrefix=mtx&identifier=info:ofi/fmt:kev:mtx:dc
            $tag_map->add("rft_val_fmt", "info:ofi/fmt:kev:mtx:dc", true, null);
            
            // lacking something better we use Zotero item types here; no clear alternative and this works for roundtrip
            $tag_map->add("rft.type", $cur_type, true, null);
            $tag_map->add("rft.title", "title", false, null);
            $tag_map->add("rft.source", "journal", false, null);
            $tag_map->add("rft.publisher", "publisher", false, null);
            // $tag_map->add("rft.description", "abstract", false, null); // TP: moved outside
            
            // TP: moved doi and url identifiers outside of the if
        }
        
        // TP: better position for doi and url, common to most types above
        if (array_key_exists("doi", $row) === true && strlen($row["doi"]) > 4) {
            $tag_map->add("rft.identifier", "doi", false, "urn:doi:%s");
        } else {
            $tag_map->add("rft.identifier", "url", false, null);
        }

        // TP: better position for abstract even if not supported for some types (e.g., patent)
        $tag_map->add("rft.description", "abstract", false, null);
        
        // authors and creators
        $authors = array();
        if (array_key_exists("author", $row) && strlen($row["author"]) > 2) {
            $authors = TP_Coins::parse_human_names($row["author"]);
        }
        
        if (count($authors) > 0) {
            // encode first author as first and last
            if ($cur_type == "patent") {
                $tag_map->add_human_creator("rft.inv", $authors[0]);
            } else {
                $tag_map->add_human_creator("rft.au", $authors[0]);
            }

            // encode subsequent creators as au
            for ($i = 1; $i < count($authors); $i += 1) {
                $author_name = implode(' ', $authors[$i]);
                if ($cur_type == "patent") {
                    $tag_map->add_human_creator("rft.inventor", $author_name);
                } else {
                    $tag_map->add_human_creator("rft.au", $author_name);
                }
            }
        }
        
        // dates
        $tag_map->add($cur_type == "patent" ? "rft.appldate" : "rft.date", "date", false, null);
        
        // pages
        if (array_key_exists("pages", $row) && strlen(trim($row["pages"])) > 0) {
            $parts = preg_split("/[-–—]/", trim($row["pages"]));
            $parts = array_filter($parts, function($item) { return strlen($item) > 0; });
            $parts = array_values($parts); // restart numbering from 0
            if (count($parts) == 1) {
                $tag_map->add("rft.tpages", $parts[0], true, null); // total nb of pages
            } else if (count($parts) >= 2) {
                $tag_map->add("rft.pages", $parts[0] . "–" . $parts[1], true, null);
                $tag_map->add("rft.spage", $parts[0], true, null);
                $tag_map->add("rft.epage", $parts[1], true, null);
            }
        }
        
        //  _mapTag(item.numPages, "tpages"); // not supported
        if (array_key_exists("is_isbn", $row) && $row['is_isbn'] == 1) {
            $tag_map->add("rft.isbn", "isbn", false, null);
        } else {
            $tag_map->add("rft.issn", "issn", false, null);
        }
        // _mapTag(item.language, "language"); // not supported

        return $tag_map->to_coins($row);
    }

    /**
     * Parses authors in the database format and returns data structure. This compensates for the fact
     * that some authors may be specified either as lastname, firstname or firstname lastname.
     * @param string $names    Names from the DB, e.g. Drouin, P. and
                               Francoeur, Annie and Madonna and John K. Smith.
     * @return array   An array of names, separated in first and last names, e.g.
     *                 array(array("P.", "Drouin"), array("Annie", "Francoeur"), array("Madonna"),
     *                 array("John", "K. Smith"))
     * @since 9.0.0
     */
    static function parse_human_names($names) {
        $names = trim($names);
        $result = array();
        foreach(explode(" and ", $names) as $author) {
            if (gettype($author) == "string") {
                $parts = explode(", ", $author);
                if (count($parts) == 1) {
                    $author = trim($parts[0]);
                    $first_space_index = strpos($author, " ");
                    
                    if ($first_space_index === false) { // no space, hence mononym like Cher
                        $result[] = array($author);
                    } else { // space, will split there
                        $result[] = array(substr($author, 0, $first_space_index),
                                          trim(substr($author, $first_space_index)));
                    }
                } else {
                    $result[] = array_merge(array($parts[count($parts) - 1]),
                                            array_slice($parts, 0, count($parts) - 1));
                }
            }
        }
        
        return $result;
    }
    
}

/**
 * teachPress TP_Coins_Tags, used as a helper to manage the tags produced
 * in Coins format.
 *
 * @since 9.0.0
 */
class TP_Coins_Tags {
    
    function __construct() {
        $this->tag_map = array();
    }
    
    /**
     * Adds a key, value pair in the Coins tags.
     * @param string $key    The key name in Coins, usually prefixed with rft.
     * @param string $value  The key value. Can be either a constant, like a format name, e.g.
     *                       add("rft_val_fmt", "info:ofi/fmt:kev:mtx:dissertation"
     *                       or a key in the item, e.g. $row[value] that is going to be looked up
     *                       when function to_coins is called.
     * @param bool $is_constant True iff $value is a constant.
     * @param string $pattern Will be used to format the value with sprintf iff not null.
     *
     */
    function add($key, $value, $is_constant, $pattern) {
        $this->tag_map[] = array($key, $value, $is_constant, $pattern);
    }

    /**
     * Adds a creator to an item.
     * @param string $key_prefix    The key name in Coins, usually prefixed with rft. The strings
     *                              "first" and "last" are appended.
     * @param mixed $creator  A creator person, in format array(firstname, lastname) or array(name)
     *                        for mononyms. If a string, will be used as such, e.g. "John Smith".
     *
     */
    function add_human_creator($key_prefix, $creator) {
        if (gettype($creator) == "array") {
            if (count($creator) >= 2) { // larger than 2 should not happen
                $this->add($key_prefix . "first", $creator[0], true, null);
                $this->add($key_prefix . "last", $creator[1], true, null);
            } else if (count($creator) == 1) {
                $this->add($key_prefix . "last", $creator[0], true, null);
            }
        } else if (gettype($creator) == "string") {
            $this->add($key_prefix, $creator, true, null);
        }
    }
    
    /**
     * Creates the span microtagged for a given item.
     * @param $row The item.
     * @return The HTML span, ready to be used.
     */
    function to_coins($row) {
        $fragments = array();
        
        foreach ($this->tag_map as $tag_info) {
            $key = $tag_info[0];
            $value = $tag_info[1];
            $is_constant = $tag_info[2];
            $pattern = $tag_info[3];
            
            if ($is_constant) {
                $fragments[] = sprintf("%s=%s", $key, urlencode($value));
            } else {
                if (array_key_exists($value, $row) &&
                    $row[$value] != "0000-00-00") { // empty date
                    
                    $value = trim($row[$value]);
                    
                    if (strlen($value) > 0) {
                        if (preg_match("/\d\d\d\d-01-01/", $value)) { // imperfect, but good heuristic
                            $value = substr($value, 0, 4); // keep only the year
                        }
                        
                        if ($pattern !== null) {
                            $value = sprintf($pattern, $value);
                        }
                        
                        $fragments[] = sprintf("%s=%s", $key, urlencode($value));
                    }
                } // otherwise, mute value
            }
        }
        
        $title = implode("&", $fragments);
        return sprintf('<span class="Z3988" title="%s"></span>', htmlentities($title));
    }
        
}
