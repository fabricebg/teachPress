<?php

/**
 * This file contains logic to export in Coins format.
 *
 * Adapted from the work of Alec Smecher at
 * https://github.com/pkp/coins/blob/main/CoinsPlugin.inc.php
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
     * @param array $row
     * @return string
     * @since 9.0.0
    */
    public static function get_single_publication_coins($row) {
        $vars = array(
            array('ctx_ver', 'Z39.88-2004'),
//            array('rft_id', $request->url(null, 'article', 'view', $article->getId())),
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
    
}
