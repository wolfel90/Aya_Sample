<?php 

include_once("../quick_search.php");
include_once("../identify_user.php");
include_once("../whitelist_check.php");
$mapCount = 0;

function formatMatch($matches) : string {
    if($matches[1] == '{{') {
        if(strpos($matches[2], "float-right|") === 0) {
            $content = substr($matches[2], strlen("float-right|"));
            return "<div style=\"float:right; width:25%; margin:5px; padding:3px 3px 3px 3px; border:2px solid; background-color:#eeeeee; font-size:80%; \">" . $content . "</div>";
        } else if(strpos($matches[2], "float-left|") === 0) {
            $content = substr($matches[2], strlen("float-left|"));
            return "<div style=\"float:left; width:25%; margin:5px; padding:3px 3px 3px 3px; border:2px solid; background-color:#eeeeee; font-size:80%; \">" . $content . "</div>";
        } else if(strpos($matches[2], "span|") === 0) {
            $content = substr($matches[2], strlen("span|"));
            return "<div style=\"width:95%; margin:auto; padding:3px 3px 3px 3px; border:2px solid; background-color:#eeeeee; font-size:80%; \">" . $content . "</div>";
        } else if(strpos(strtolower($matches[2]), "category|") === 0) {
            return "";
        } else if(strpos(strtolower($matches[2]), "list|") === 0) {
            $content = substr($matches[2], strlen("list|"));
            $foundArts = performQuickSearch('vc_articles', ['id', 'title', 'creator', 'whitelist_protected', 'whitelist'], '`description` LIKE ',  '%{{category|' . $content . '}}%', 'ORDER BY `title` ASC');
            $foundTitles = '';
            
            if(count($foundArts) > 15) {
                $leadLetter = '';
                $lastLead = '';
                for($n = 0; $n < count($foundArts); ++$n) {
                    $acc_level = getAccessLevel($_SESSION["user"], $foundArts[$n]["whitelist"], $foundArts[$n]["creator"], $foundArts[$n]["whitelist_protected"], $_SESSION["access"]);
                    if($acc_level > 0) {
                        $leadLetter = strtoupper(substr($foundArts[$n]["title"], 0, 1));
                        if($leadLetter != $lastLead) {
                            if($foundTitles != '') $foundTitles .= '<br>';
                            $foundTitles .= '<b>' . $leadLetter . '</b><br>';
                        }
                        $foundTitles .= '&ensp;&#8226; <u><a href="http://ayaseye.com/articles/?id=' . $foundArts[$n]["id"] . '">' . $foundArts[$n]["title"] . '</a></u></br>';
                        $lastLead = $leadLetter;
                    }
                }
                unset($leadLetter);
                unset($lastLead);
            } else {
                for($n = 0; $n < count($foundArts); ++$n) {
                    $acc_level = getAccessLevel($_SESSION["user"], $foundArts[$n]["whitelist"], $foundArts[$n]["creator"], $foundArts[$n]["whitelist_protected"], $_SESSION["access"]);
                    if($acc_level > 0) {
                        $foundTitles .= '&ensp;&#8226; <u><a href="http://ayaseye.com/articles/?id=' . $foundArts[$n]["id"] . '">' . $foundArts[$n]["title"] . '</a></u></br>';
                    }
                }
            }
            
            return $foundTitles;
        } else if(strpos(strtolower($matches[2]), "recent|") === 0) {
            $recentCount = substr($matches[2], strlen("recent|"));
            if(is_numeric($recentCount)) {
                $foundArts = performQuickSearch('vc_articles', ['id', 'title', 'creator', 'edit_time', 'editor', 'whitelist_protected', 'whitelist'], '`whitelist_protected` = ',  '0', 'ORDER BY `edit_time` DESC LIMIT ' . $recentCount);
                $foundTitles = '';
                
                $id_users = [];
                for($n = 0; $n < count($foundArts); ++$n) {
                    if(!in_array($foundArts[$n]["editor"], $id_users)) {
                        array_push($id_users, $foundArts[$n]["editor"]);
                    }
                }
                
                if(count($id_users) > 0) {
                    $butts = identifyUsers($id_users);
                }
                
                for($n = 0; $n < count($foundArts); ++$n) {
                    $acc_level = getAccessLevel($_SESSION["user"], $foundArts[$n]["whitelist"], $foundArts[$n]["creator"], $foundArts[$n]["whitelist_protected"], $_SESSION["access"]);
                    if($acc_level > 0) {
                        $recentName = 'Unknown';
                        foreach($butts as $b) {
                            if($b[0] == $foundArts[$n]["editor"]) {
                                $recentName = $b[1];
                                break;
                            }
                        }
                        $dt = new DateTime($foundArts[$n]["edit_time"]);
                        $foundTitles .= '<u><a href="http://ayaseye.com/articles/?id=' . $foundArts[$n]["id"] . '">' . $foundArts[$n]["title"] . '</a></u> by ' 
                            . $recentName . ' on ' . date_format($dt, 'M d, Y h:ia') . '</br>';
                    }
                }
                return $foundTitles;
            } else {
                return $matches[2];
            }
        } else if(strpos(strtolower($matches[2]), "map|") === 0) {
            $resVal = $matches[2];
            $mapInfo = explode("|", substr($matches[2], strlen("map|")));
            if(count($mapInfo) == 3) {
                $foundMap = performQuickSearch('vc_atlas_maps', ['img'], '`id` = ',  $mapInfo[0], '' . $recentCount);
                if(count($foundMap) > 0) {
                    $resVal = '<div class="map_container" id="map' . $mapCount . '" style="width:240px; height:240px;"><img src="' . $foundMap[0]["img"] . '"><img id="blip' . $mapCount . '" src="http://ayaseye.com/map_blip.png"></div>';
                    $resVal .= '<script type="text/javascript">initiateMap("' . $mapCount . '", ' . $mapInfo[1] . ', ' . $mapInfo[2] . ');</script>';
                    $mapCount++;
                }
            }
            return $resVal;
        } else {
            return $matches[2];
        }
    } else if($matches[1] == '{|') {
        $htmlContent = '<div style="display:table;">';
        $tableContents = explode("|-", $matches[2]);
        foreach($tableContents as $tableRow) {
            $htmlContent .= '<div style="display:table-row;">';
            $rowContents = explode("|", $tableRow);
            foreach($rowContents as $tableCell) {
                $htmlContent .= '<div style="display:table-cell; padding:0.25em; margin:auto; border:1px solid #dddddd;"><center>' . $tableCell . '</center></div>';
            }
            $htmlContent .= '</div>';
        }
        $htmlContent .= '</div>';
        return $htmlContent;
    } else if($matches[1] == '[[') {
        if(strpos($matches[2], "|") !== false) {
            $parts = explode("|", $matches[2]);
            if(count($parts) >= 2) {
                return "<u><a href=\"?article=" . $parts[0] . "\">" . $parts[1] . "</a></u>";
            } else {
                return "";
            }
        } else {
            return "<u><a href=\"?article=" . $matches[2] . "\">" . $matches[2] . "</a></u>";
        }
    } else if($matches[1] == "```") {
        return "<b>" . $matches[2] . "</b>";
    } else if($matches[1] == "``") {
        return "<i>" . $matches[2] . "</i>";
    } else if($matches[1] == "===") {
        return "<h3>" . $matches[2] . "</h3>";
    } else if($matches[1] == "==") {
        return "<h2>" . $matches[2] . "</h2><hr>";
    } else {
        return $matches[2];
    }
}

function tagStripper($matches) : string {
    if($matches[1] == '{{') {
        return "";
    } else if($matches[1] == '[[') {
        if(strpos($matches[2], "|") !== false) {
            $parts = explode("|", $matches[2]);
            if(count($parts) >= 2) {
                return $parts[1];
            } else {
                return "";
            }
        } else {
            return $matches[2];
        }
    }
}

function formatArticle($artText) : string {
    $artText = preg_replace_callback(
        '/(\[\[)([^\[^\]]*)(\]\])/',
        'formatMatch',
        $artText
    );
    $artText = preg_replace_callback(
        '/(\{\{)([^\[^\}^\{]*)(\}\})/',
        'formatMatch',
        $artText
    );
    $artText = preg_replace_callback(
        '/(\{\|)([^\[^\}^\{]*)(\|\})/',
        'formatMatch',
        $artText
    );
    
    $artText = preg_replace_callback(
        '/(```)([^`]*)(```)/',
        'formatMatch',
        $artText
    );
    $artText = preg_replace_callback(
        '/(``)([^`]*)(``)/',
        'formatMatch',
        $artText
    );
    $artText = preg_replace_callback(
        '/(===)([^=]*)(===)/',
        'formatMatch',
        $artText
    );
    $artText = preg_replace_callback(
        '/(==)([^=]*)(==)/',
        'formatMatch',
        $artText
    );
    $artText = str_replace(">\r", ">", $artText);
    $artText = str_replace(">\n", ">", $artText);
    $artText = str_replace("\n", "<br>", $artText);
    return $artText;
}

function findCategories($artText) : string {
    $matchcount = preg_match_all('/(\{\{[c|C]ategory\|)([^\[^\}^\}]*)(\}\})/', $artText, $matches, PREG_SET_ORDER);
    
    if($matchcount > 0) {
        $result = 'Categories: ';
        for($i = 0; $i < count($matches); $i++) {
            if($i > 0) $result .= ', ';
            $result .= '<u><a href="' . 'http://ayaseye.com/articles/index.php?category=' . $matches[$i][2] . '">' . $matches[$i][2] . '</a></u>';
        }
        return $result;
    }
    return '';
}

function stripSystemTags($artText) : string {
    $artText = preg_replace_callback(
        '/(\[\[)([^\[^\]]*)(\]\])/',
        tagStripper,
        $artText
    );
    $artText = preg_replace_callback(
        '/(\{\{)([^\[^\}]*)(\}\})/',
        tagStripper,
        $artText
    );
    return $artText;
}

function findTags($input, &$tags) {
    $current = "";
    $tags = array();
    $c = 0;
    preg_match_all('/(\[)([^\[^\]]*)(\])/', $input, $matches);
    for($i = 0; $i < count($matches[2]); $i++) {
        if(strpos($matches[2][$i], "/") !== false) {
            $c--;
            if($c == 0) {
                if($matches[2][$i] == ("/" . $current)) {
                    if(!in_array($current, $tags)) {
                        array_push($tags, $current);
                    }
                }
            }
        } else {
            if($c == 0) {
                $current = $matches[2][$i];
            }
            $c++;
        }
    }
}

function formatProperties($input) : string {
    $result = "";
    $tags;
    findTags($input, $tags);
    foreach($tags as $ctag) {
        preg_match_all('/(\[' . $ctag . '\])(.*)(\[\/' . $ctag . '\])/', $input, $matches);
        for($i = 0; $i < count($matches[2]); $i++) {
            $result .= "[|" . $ctag . "==" . $matches[2][$i] . "|]";
        }
    }
    return $result;
}

?>
