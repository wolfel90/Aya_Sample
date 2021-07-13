<?php
    $servername = "ayaseye.com";
    $username = "redacted";
    $password = "redacted";
    $dbname = "redacted";
    
    include_once("../whitelist_check.php");
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $art_title = "Search results";
    $art_content = "0 results found";
    if(isset($_GET["search"])) {
        $art_title .= " for \"" . $_GET["search"] . "\"";
        $search_term = $_GET["search"];
        if(strpos($search_term, " ") !== false) {
            $search_words = explode(" ", $search_term);
        } else {
            $search_words = array($search_term);
        }
        
        $sql = "SELECT `title`, `guidebook`, `guidebook2`, `lorebook`, `lorebook2`, `atlas`, `timeline`, `trait`, `talent`, `school`, `ability`, `system`, `redirect`, `description`, `properties`, `creator`, `whitelist_protected`, `whitelist` FROM vc_articles WHERE ";
        $c = 0;
        foreach($search_words as $s) {
            $s = str_replace("\\", "", $s);
            $s = str_replace("'", "''", $s);
            $s = str_replace("`", "``", $s);
            if($c > 0) $sql .= " OR ";
            $sql .= " `title` LIKE '%" . $s . "%' OR `description` LIKE '%" . $s . "%'";
            $c++;
            if($c > 30) break;
        }
        $sql .= " LIMIT 1000;";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $guidebook = array();
            $guidebook2 = array();
            $lorebook = array();
            $lorebook2 = array();
            $atlas = array();
            $timeline = array();
            $trait = array();
            $trait2 = array();
            $talent = array();
            $talent2 = array();
            $school = array();
            $school2 = array();
            $ability = array();
            $ability2 = array();
            $systemCat = array();
            $uncategorized = array();
            $categories = array();
            
            $count = 0;
            
            while ($row = $result->fetch_assoc()) {
                $countable = false;
                if(isset($_SESSION['user'])) {
                    $id = $_SESSION['user'];
                } else {
                    $id = 0;
                }
                $access_level = getAccessLevel($id, $row["whitelist"], $row["creator"], $row["whitelist_protected"], $_SESSION['access']);
                if($access_level < 1) continue;
                
                if($row['guidebook'] == 1) {
                    array_push($guidebook, $row["title"]);
                    $countable = true;
                }
                if($row['guidebook2'] == 1) {
                    array_push($guidebook2, $row["title"]);
                    $countable = true;
                }
                if($row['lorebook'] == 1) {
                    array_push($lorebook, $row["title"]);
                    $countable = true;
                }
                if($row['lorebook2'] == 1) {
                    array_push($lorebook2, $row["title"]);
                    $countable = true;
                }
                if($row['atlas'] == 1) {
                    array_push($atlas, $row["title"]);
                    $countable = true;
                }
                if($row['timeline'] == 1) {
                    array_push($timeline, $row["title"]);
                    $countable = true;
                }
                if($row['trait'] == 1) {
                    if($row['guidebook'] == 1) {
                        array_push($trait, $row["title"]);
                        $countable = true;
                    } elseif($row['guidebook2'] == 1) {
                        array_push($trait2, $row["title"]);
                        $countable = true;
                    }
                }
                if($row['talent'] == 1) {
                    if($row['guidebook'] == 1) {
                        array_push($talent, $row["title"]);
                        $countable = true;
                    } elseif($row['guidebook2'] == 1) {
                        array_push($talent2, $row["title"]);
                        $countable = true;
                    }
                }
                if($row['school'] == 1) {
                    if($row['guidebook'] == 1) {
                        array_push($school, $row["title"]);
                        $countable = true;
                    } elseif($row['guidebook2'] == 1) {
                        array_push($school2, $row["title"]);
                        $countable = true;
                    }
                }
                if($row['ability'] == 1) {
                    if($row['guidebook'] == 1) {
                        array_push($ability, $row["title"]);
                        $countable = true;
                    } elseif($row['guidebook2'] == 1) {
                        array_push($ability2, $row["title"]);
                        $countable = true;
                    }
                }
                if($row['system'] == 1) {
                    array_push($systemCat, $row["title"]);
                    $countable = true;
                }
                
                // Find Categories
                $matchcount = preg_match_all('/(\{\{[c|C]ategory\|)([^\[^\}^\}]*)(\}\})/', $row["description"], $cats, PREG_SET_ORDER);
                
                if($matchcount > 0) {
                    for($i = 0; $i < count($cats); $i++) {
                        $newcat = $cats[$i][2];
                        foreach($search_words as $s) {
                            if(stripos($newcat, $s) !== false) {
                                if(!in_array($newcat, $categories, false)) {
                                    array_push($categories, $newcat);
                                    $countable = true;
                                }
                            }
                        }
                    }
                }
                
                // End Find Categories
                
                if($row['guidebook'] == 0 && $row['guidebook2'] == 0 && $row['lorebook'] == 0 && $row['lorebook2'] == 0 && $row['atlas'] == 0 && $row['timeline'] == 0 && $row['trait'] == 0 && $row['talent'] == 0 && $row['school'] == 0 && $row['ability'] == 0 && $row['system'] == 0) {
                    array_push($uncategorized, $row["title"]);
                    $countable = true;
                }
                if($countable) {
                    $count++;
                }
            }
            
            if($count > 0) {
                $art_content = "";
                if(count($categories) > 0) {
                    $art_content .= "==Custom Categories (" . count($categories) . " Result" . (count($categories) == 1 ? "" : "s") . ")==";
                    $art_content .= "<ul>";
                    foreach($categories as $a) {
                        $art_content .= '<li><u><a href="' . 'http://ayaseye.com/articles/index.php?category=' . $a . '">' . $a . '</a></u></li>';
                    }
                    $art_content .= "</ul>";
                    $art_content .= "<br><br>";
                }
                if(count($guidebook) > 0) {
                    $art_content .= "==Guidebook 1.0 (" . count($guidebook) . " Result" . (count($guidebook) == 1 ? "" : "s") . ")==";
                    $art_content .= "<ul>";
                    foreach($guidebook as $a) {
                        $art_content .= "<li>[[" . $a . "]]</li>";
                    }
                    $art_content .= "</ul>";
                    $art_content .= "<br><br>";
                }
                if(count($guidebook2) > 0) {
                    $art_content .= "==Guidebook 2.0 (" . count($guidebook2) . " Result" . (count($guidebook2) == 1 ? "" : "s") . ")==";
                    $art_content .= "<ul>";
                    foreach($guidebook2 as $a) {
                        $art_content .= "<li>[[" . $a . "]]</li>";
                    }
                    $art_content .= "</ul>";
                    $art_content .= "<br><br>";
                }
                if(count($lorebook) > 0) {
                    $art_content .= "==Lore: Ayaseye (" . count($lorebook) . " Result" . (count($lorebook) == 1 ? "" : "s") . ")==";
                    $art_content .= "<ul>";
                    foreach($lorebook as $a) {
                        $art_content .= "<li>[[" . $a . "]]</li>";
                    }
                    $art_content .= "</ul>";
                    $art_content .= "<br><br>";
                }
                if(count($lorebook2) > 0) {
                    $art_content .= "==Lore: Eigolyn (" . count($lorebook2) . " Result" . (count($lorebook2) == 1 ? "" : "s") . ")==";
                    $art_content .= "<ul>";
                    foreach($lorebook2 as $a) {
                        $art_content .= "<li>[[" . $a . "]]</li>";
                    }
                    $art_content .= "</ul>";
                    $art_content .= "<br><br>";
                }
                if(count($atlas) > 0) {
                    $art_content .= "==Atlas (" . count($atlas) . " Result" . (count($atlas) == 1 ? "" : "s") . ")==";
                    $art_content .= "<ul>";
                    foreach($atlas as $a) {
                        $art_content .= "<li>[[" . $a . "]]</li>";
                    }
                    $art_content .= "</ul>";
                    $art_content .= "<br><br>";
                }
                if(count($timeline) > 0) {
                    $art_content .= "==Timeline (" . count($timeline) . " Result" . (count($timeline) == 1 ? "" : "s") . ")==";
                    $art_content .= "<ul>";
                    foreach($timeline as $a) {
                        $art_content .= "<li>[[" . $a . "]]</li>";
                    }
                    $art_content .= "</ul>";
                    $art_content .= "<br><br>";
                }
                if(count($trait) > 0) {
                    $art_content .= "==Traits 1.0 (" . count($trait) . " Result" . (count($trait) == 1 ? "" : "s") . ")==";
                    $art_content .= "<ul>";
                    foreach($trait as $a) {
                        $art_content .= "<li>[[" . $a . "]]</li>";
                    }
                    $art_content .= "</ul>";
                    $art_content .= "<br><br>";
                }
                if(count($trait2) > 0) {
                    $art_content .= "==Traits 2.0 (" . count($trait2) . " Result" . (count($trait2) == 1 ? "" : "s") . ")==";
                    $art_content .= "<ul>";
                    foreach($trait2 as $a) {
                        $art_content .= "<li>[[" . $a . "]]</li>";
                    }
                    $art_content .= "</ul>";
                    $art_content .= "<br><br>";
                }
                if(count($talent) > 0) {
                    $art_content .= "==Talents 1.0 (" . count($talent) . " Result" . (count($talent) == 1 ? "" : "s") . ")==";
                    $art_content .= "<ul>";
                    foreach($talent as $a) {
                        $art_content .= "<li>[[" . $a . "]]</li>";
                    }
                    $art_content .= "</ul>";
                    $art_content .= "<br><br>";
                }
                if(count($talent2) > 0) {
                    $art_content .= "==Talents 2.0 (" . count($talent2) . " Result" . (count($talent2) == 1 ? "" : "s") . ")==";
                    $art_content .= "<ul>";
                    foreach($talent2 as $a) {
                        $art_content .= "<li>[[" . $a . "]]</li>";
                    }
                    $art_content .= "</ul>";
                    $art_content .= "<br><br>";
                }
                if(count($school) > 0) {
                    $art_content .= "==Schools 1.0 (" . count($school) . " Result" . (count($school) == 1 ? "" : "s") . ")==";
                    $art_content .= "<ul>";
                    foreach($school as $a) {
                        $art_content .= "<li>[[" . $a . "]]</li>";
                    }
                    $art_content .= "</ul>";
                    $art_content .= "<br><br>";
                }
                if(count($school2) > 0) {
                    $art_content .= "==Schools 2.0 (" . count($school2) . " Result" . (count($school2) == 1 ? "" : "s") . ")==";
                    $art_content .= "<ul>";
                    foreach($school2 as $a) {
                        $art_content .= "<li>[[" . $a . "]]</li>";
                    }
                    $art_content .= "</ul>";
                    $art_content .= "<br><br>";
                }
                if(count($ability) > 0) {
                    $art_content .= "==Abilities 1.0 (" . count($ability) . " Result" . (count($ability) == 1 ? "" : "s") . ")==";
                    $art_content .= "<ul>";
                    foreach($ability as $a) {
                        $art_content .= "<li>[[" . $a . "]]</li>";
                    }
                    $art_content .= "</ul>";
                    $art_content .= "<br><br>";
                }
                if(count($ability2) > 0) {
                    $art_content .= "==Abilities 2.0 (" . count($ability2) . " Result" . (count($ability2) == 1 ? "" : "s") . ")==";
                    $art_content .= "<ul>";
                    foreach($ability2 as $a) {
                        $art_content .= "<li>[[" . $a . "]]</li>";
                    }
                    $art_content .= "</ul>";
                    $art_content .= "<br><br>";
                }
                if(count($systemCat) > 0) {
                    $art_content .= "==System Pages (" . count($systemCat) . " Result" . (count($systemCat) == 1 ? "" : "s") . ")==";
                    $art_content .= "<ul>";
                    foreach($systemCat as $a) {
                        $art_content .= "<li>[[" . $a . "]]</li>";
                    }
                    $art_content .= "</ul>";
                    $art_content .= "<br><br>";
                }
                if(count($uncategorized) > 0) {
                    $art_content .= "==Uncategorized (" . count($uncategorized) . " Result" . (count($uncategorized) == 1 ? "" : "s") . ")==";
                    $art_content .= "<ul>";
                    foreach($uncategorized as $a) {
                        $art_content .= "<li>[[" . $a . "]]</li>";
                    }
                    $art_content .= "</ul>";
                    $art_content .= "<br><br>";
                }
            }
            
        }
    }
    
    $conn->close();
?>
