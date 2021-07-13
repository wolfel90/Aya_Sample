<?php
    $cp = "";
    if($access_level >= 2) {
        $cp = "<input type = \"button\" id=\"editButton\" onclick=\"editPage()\" value=\"Edit\" />";
    }
?>

<div style="display:table; width:100%">
    <div style="display:table-row;">
        <div style="display:table-cell; margin:auto; width:33.3%;">
            <center><input type = "button" id="basicsButton" onclick="showBasics();" value="Basics" style="width:100%" /></center>
        </div>
        <div style="display:table-cell; margin:auto; width:33.3%;">
            <center><input type = "button" id="profsButton" onclick="showProficiencies();" value="Proficiencies" style="width:100%" /></center>
        </div>
        <div style="display:table-cell; margin:auto; width:33.3%;">
            <center><input type = "button" id="invButton" onclick="showInventory();" value="Inventory" style="width:100%" /></center>
        </div>
    </div>
    <div style="display:table-row;">
        <div style="display:table-cell; margin:auto; width:33.3%;">
            <center><input type = "button" id="ttButton" onclick="showTraitsTalents();" value="Traits & Talents" style="width:100%" /></center>
        </div>
        <div style="display:table-cell; margin:auto; width:33.3%;">
            <center><input type = "button" id="schoolsButton" onclick="showSchools();" value="Abilities" style="width:100%" /></center>
        </div>
        <div style="display:table-cell; margin:auto; width:33.3%;">
            <center><input type = "button" id="notesButton" onclick="showNotes();" value="Notes" style="width:100%" /></center>
        </div>
    </div>
</div>
</br>
<div id="display_area" style="width:100%; overflow:auto;"></div>

<div id="control_panel"><?php echo $cp; ?></div>
<div id="debug_area"></div>

<script src="Attributes.js"></script>
<script src="Vitality.js"></script>
<script type="text/javascript">
    var page = "basics";
    var itemTypes = ['Generic', 'Weapon', 'Armor', 'Accessory', 'Consumable', 'Ammunition', 'Component'];
    var itemBacks = ['back01', 'back02', 'back03', 'back04'];
    var itemIcons = ["shirt01", "cuirass01", "brigandine01", "robe01", "chainmail01", "helmet01", "gloves01", "pants01", "boots01", "belt01", "ring01", "ring02", "amulet01", "sword01", "sword02", "sword03", 
        "axe01", "axe02", "club01", "hammer01", "hammer02", "spear01", "quarterstaff01", "knuckles01", "bow01", "crossbow01", "pistol01", "rifle01", "staff01", "wand01", "book01", "book02", "glove01", "gem01", 
        "arrow01", "arrow02", "arrow03", "bullet01", "dice01", "cards01", "food01", "food02", "bag01", "chest01", "coin01", "note01", "scroll01", "orb01", "rope01", "component01", "component02", "component03", 
        "component04"];

    function retrieveData() {
        try {
            var dat = <?php global $chara; echo json_encode($chara); ?>;
            return dat;
        } catch(err) {
            document.getElementById("debug_area").innerText = err.message;
            return "";
        }
    }
    
    function retrieveOptions() {
        try {
            var dat = <?php if(isset($_SESSION['sheet_options'])) { echo json_encode($_SESSION['sheet_options']); } else { echo '""'; } ?>;
            return dat;
        } catch(err) {
            document.getElementById("debug_area").innerText = err.message;
            return "";
        }
    }
    
    function retrieveUser() {
        try {
            var dat = <?php if(isset($_SESSION['user'])) { echo json_encode($_SESSION['user']); } else { echo '-1'; } ?>;
            return dat;
        } catch(err) {
            document.getElementById("debug_area").innerText = err.message;
            return -1;
        }
    }
    
    function readTag(input, tag) {
        var count = 0;
        var dex = 0;
        var result = "";
        var patt = new RegExp("(\\[" + tag + "\\]|\\[/" + tag + "\\])", "g");
        var test;
        while((test = patt.exec(input)) !== null) {
            if(test[0] == "[" + tag + "]") {
                if(count == 0) {
                    dex = patt.lastIndex;
                }
                ++count;
            } else if(test[0] == "[/" + tag + "]") {
                --count;
                if(count == 0) {
                    result = input.substring(dex, test.index);
                    break;
                }
            }
        }
        return result;
    }
    
    function setTag(input, tag, val) {
        var count = 0;
        var dex = 0;
        var result = "";
        var patt = new RegExp("(\\[" + tag + "\\]|\\[/" + tag + "\\])", "g");
        var test;
        while((test = patt.exec(input)) !== null) {
            if(test[0] == "[" + tag + "]") {
                if(count == 0) {
                    dex = patt.lastIndex;
                }
                ++count;
            } else if(test[0] == "[/" + tag + "]") {
                --count;
                if(count == 0) {
                    result = input.substring(0, dex) + val + input.substring(test.index);
                    break;
                }
            }
        }
        return result;
    }
    
    function readTagMulti(input, tag) {
        var count = 0;
        var dex = 0;
        var result = [];
        var patt = new RegExp("(\\[" + tag + "\\]|\\[/" + tag + "\\])", "g");
        var test;
        while((test = patt.exec(input)) !== null) {
            if(test[0] == "[" + tag + "]") {
                if(count == 0) {
                    dex = patt.lastIndex;;
                }
                ++count;
            } else if(test[0] == "[/" + tag + "]") {
                --count;
                if(count == 0) {
                    result.push(input.substring(dex, test.index));
                }
            }
        }
        return result;
    }
    
    function filterHTML(text) {
        var map = { 
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    function sqlify(textIn) {
        var map = {
            "'": "''",
            "`": "``",
            "\\": ""
        };
        
        return textIn.replace(/['`\\]/g, function(m) { return map[m]; })
    }
    
    function toColor(argb) {
        return ("#" + (argb & 0x00FFFFFF).toString(16).padStart(6, '0'));
    }
    
    function editPage() {
        var content = "";
        switch(page) {
            case "basics": // Edit Basics ====================================
                var ayaModule = charaData.modules.includes("Ayaseye");
                var eigModule = charaData.modules.includes("Eigolyn");
                content += '<form name="basics_form" class="form-space" method="post" action="update_character.php?id=' + charaData.id + '&page=basics">';
                content += '<label for="nameField">Name: </label><input type = "text" id="nameField" name="nameField" value="' + charaData.name + '" /></br>';
                content += '<label for="raceField">Race: </label><input type = "text" id="raceField" name="raceField" value="' + charaData.race + '" /></br>';
                content += '<label for="genderField">Gender: </label><input type = "text" id="genderField" name="genderField" value="' + charaData.gender + '" /></br>';
                content += '<label for="heightField">Height: </label><input type = "text" id="heightField" name="heightField" value="' + charaData.height + '" /></br>';
                content += '<label for="weightField">Weight: </label><input type = "text" id="weightField"  name="weightField"value="' + charaData.weight + '" /></br>';
                content += '<label for="ageField">Age: </label><input type = "text" id="ageField"  name="ageField"value="' + charaData.age + '" /></br>';
                content += '<label for="birthField">Birth: </label><input type = "text" id="birthField"  name="birthField"value="' + charaData.birth + '" /></br>';
                content += '<label for="hairField">Hair: </label><input type = "text" id="hairField" name="hairField" value="' + charaData.hair + '" /></br>';
                content += '<label for="eyesField">Eyes: </label><input type = "text" id="eyesField" name="eyesField" value="' + charaData.eyes + '" /></br>';
                content += '<label for="homeField">Homeland: </label><input type = "text" id="homeField" name="homeField" value="' + charaData.home + '" /></br>';
                content += '<label for="appearField">Appearance: </label><textarea id="appearField" name="appearField">' + charaData.appearance + '</textarea></br>';
                content += '<label for="bioField">Biography: </label><textarea id="bioField" name="bioField">' + charaData.biography + '</textarea></br>';
                content += '<label for="langField">Languages: </label><textarea id="langField" name="langField">' + charaData.languages + '</textarea></br>';
                content += '<label for="conductField">Conduct: </label><input type = "number" id="conductField" name="conductField" value="' + charaData.conduct + '" /></br>';
                content += '<label for="moralityField">Morality: </label><input type = "number" id="moralityField" name="moralityField" value="' + charaData.morality + '" /></br>';
                content += '<label for="fameField">Fame: </label><input type = "number" id="fameField" name="fameField" value="' + charaData.fame + '" /></br>';
                content += '<label for="infamyField">Infamy: </label><input type = "number" id="infamyField" name="infamyField" value="' + charaData.infamy + '" /></br>';
                content += '<label>Modules: </label>';
                content += '<input type="hidden" id="coreModuleCheck" name="coreModuleCheck" value="on" />';
                content += '<input type="checkbox" id="ayaseyeModuleCheck" name="ayaseyeModuleCheck" ' + (ayaModule ? 'checked' : '') + ' /><label for="ayaseyeModuleCheck">Ayaseye</label>';
                content += '<input type="checkbox" id="eigolynModuleCheck" name="eigolynModuleCheck" ' + (eigModule ? 'checked' : '') + ' /><label for="eigolynModuleCheck">Eigolyn</label>';
                content += '</br><center><input type="submit" value="Submit" /> <input type = \"button\" id=\"cancelButton\" onclick=\"showBasics()\" value=\"Cancel\" /></center>';
                content += '</form>';
                document.getElementById("control_panel").innerHTML = '';
                break;
            case "proficiencies":
                content += '<form name="prof_form" class="form-space" method="post" action="update_character.php?id=' + charaData.id + '&page=proficiencies">';
                content += '<label for="levelField">Level: </label><input type = "number" id="levelField" name="levelField" value="' + charaData.level + '" /></br>';
                content += '<label for="curXPField">Current XP: </label><input type = "number" id="curXPField" name="curXPField" value="' + charaData.xp_current + '" /></br>';
                content += '<label for="nextXPField">Next Level: </label><input type = "number" id="nextXPField" name="nextXPField" value="' + charaData.xp_next + '" /></br>';
                content += '<label for="strField">Strength: </label><input type = "number" id="strField" name="strField" value="' + charaData.strength + '" /></br>';
                content += '<label for="agiField">Agility: </label><input type = "number" id="agiField" name="agiField" value="' + charaData.agility + '" /></br>';
                content += '<label for="intField">Intelligence: </label><input type = "number" id="intField" name="intField" value="' + charaData.intelligence + '" /></br>';
                content += '<label for="forField">Fortitude: </label><input type = "number" id="forField" name="forField" value="' + charaData.fortitude + '" /></br>';
                content += '<label for="chaField">Charisma: </label><input type = "number" id="chaField" name="chaField" value="' + charaData.charisma + '" /></br>';
                content += '<label for="curHPField">Current HP: </label><input type = "number" id="curHPField" name="curHPField" value="' + charaData.hp_current + '" /></br>';
                content += '<label for="EPAdjField">EP Adjust: </label><input type = "number" id="EPAdjField" name="EPAdjField" value="' + charaData.stamina_adj + '" /></br>';
                content += '<label for="MPAdjField">MP Adjust: </label><input type = "number" id="MPAdjField" name="MPAdjField" value="' + charaData.channel_adj + '" /></br>';
                content += '<label for="exhaustField">Exhaustion: </label><input type = "number" id="exhaustField" name="exhaustField" value="' + charaData.exhaust + '" /></br>';
                content += '</br><center><input type="submit" value="Submit" /> <input type = \"button\" id=\"cancelButton\" onclick=\"showProficiencies()\" value=\"Cancel\" /></center>';
                content += '</form>';
                document.getElementById("control_panel").innerHTML = '';
                break;
            case "inventory": // Edit Inventory ===============================
                content += '<h2><center><b>Wallet</b></center></h2>';
                content += 'Allervians: <input type="number" id="allervianField' + i + '" value="' + filterHTML(readTag(walletList, "allervian")) + '" onchange="setCoinCount(\'allervian\', this.value);" /></br>';
                content += 'Florins: <input type="number" id="florinField' + i + '" value="' + filterHTML(readTag(walletList, "florin")) + '" onchange="setCoinCount(\'florin\', this.value);" /></br>';
                content += 'Drakes: <input type="number" id="drakeField' + i + '" value="' + filterHTML(readTag(walletList, "drake")) + '" onchange="setCoinCount(\'drake\', this.value);" /></br>';
                content += 'Denarii: <input type="number" id="denariusField' + i + '" value="' + filterHTML(readTag(walletList, "denarius")) + '" onchange="setCoinCount(\'denarius\', this.value);" /></br>';
                content += 'Marks: <input type="number" id="markField' + i + '" value="' + filterHTML(readTag(walletList, "mark")) + '" onchange="setCoinCount(\'mark\', this.value);" /></br>';
                content += '<h2><center><b>Items</b></center></h2>';
                var wl = '';
                var auth = 0;
                var it = '';
                for(var i = 0; i < itemList.length; ++i) {
                    auth = 0;
                    wl = readTag(itemList[i], 'whitelist').split(',');
                    for(var n = 0; n < wl.length; ++n) {
                        if(wl[n] == user) {
                            auth = 1;
                        }
                    }
                    it = readTag(itemList[i], "type");
                    if(i > 0) content += '</br>';
                    content += '<div style="border:1px solid">';
                    content += 'Basic Title: <input type="text" id="btitleField' + i + '" value="' + filterHTML(readTag(itemList[i], "btitle")) + '" onchange="setItemValue(' + i + ', \'btitle\', this.value);" />';
                    if(auth) {
                        content += 'Full Title: <input type="text" id="titleField' + i + '" value="' + filterHTML(readTag(itemList[i], "title")) + '" onchange="setItemValue(' + i + ', \'title\', this.value);" />';
                    }
                    content += '</br>';
                    content += 'Type: <select onchange="setItemValue(' + i + ', \'type\', this.value);">';
                    content += '<option value="-1">Select...</option>';
                    for(var n = 0; n < itemTypes.length; ++n) {
                        content += '<option value="' + n + '" ';
                        if(n == it) {
                            content += 'selected';
                        }
                        content += '>' + itemTypes[n] + '</option>';
                    }
                    content += '</select>';
                    content += ' Long Type: <input type="text" id="longTypeField' + i + '" value="' + filterHTML(readTag(itemList[i], "long-type")) + '" onchange="setItemValue(' + i + ', \'long-type\', this.value);" /></br>';
                    content += 'Modifier Proficiency: <input type="text" id="modProfField' + i + '" value="' + filterHTML(readTag(itemList[i], "mod-prof")) + '" onchange="setItemValue(' + i + ', \'mod-prof\', this.value);" /></br>';
                    content += 'Weight: <input type="number" id="weightField' + i + '" value="' + filterHTML(readTag(itemList[i], "weight")) + '" onchange="setItemValue(' + i + ', \'weight\', this.value);" />';
                    content += 'Value: <input type="number" id="valueField' + i + '" value="' + filterHTML(readTag(itemList[i], "value")) + '" onchange="setItemValue(' + i + ', \'value\', this.value);" /></br>';
                    content += 'DMG: <input type="text" id="dmgField' + i + '" value="' + filterHTML(readTag(itemList[i], "dmg")) + '" onchange="setItemValue(' + i + ', \'dmg\', this.value);" />';
                    content += 'POW: <input type="number" id="powField' + i + '" value="' + filterHTML(readTag(itemList[i], "pow")) + '" onchange="setItemValue(' + i + ', \'pow\', this.value);" />';
                    content += 'RNG: <input type="number" id="rngField' + i + '" value="' + filterHTML(readTag(itemList[i], "rng")) + '" onchange="setItemValue(' + i + ', \'rng\', this.value);" /></br>';
                    content += 'MDMG: <input type="text" id="mdmgField' + i + '" value="' + filterHTML(readTag(itemList[i], "mdmg")) + '" onchange="setItemValue(' + i + ', \'mdmg\', this.value);" />';
                    content += 'MPOW: <input type="number" id="mpowField' + i + '" value="' + filterHTML(readTag(itemList[i], "mpow")) + '" onchange="setItemValue(' + i + ', \'mpow\', this.value);" />';
                    content += 'MRNG: <input type="number" id="mrngField' + i + '" value="' + filterHTML(readTag(itemList[i], "mrng")) + '" onchange="setItemValue(' + i + ', \'mrng\', this.value);" /></br>';
                    content += 'DEF: <input type="number" id="defField' + i + '" value="' + filterHTML(readTag(itemList[i], "def")) + '" onchange="setItemValue(' + i + ', \'def\', this.value);" />';
                    content += 'MDEF: <input type="number" id="mdefField' + i + '" value="' + filterHTML(readTag(itemList[i], "mdef")) + '" onchange="setItemValue(' + i + ', \'mdef\', this.value);" /></br>';
                    content += 'Unit (Singular): <input type="text" id="unitsField' + i + '" value="' + filterHTML(readTag(itemList[i], "unit-s")) + '" onchange="setItemValue(' + i + ', \'unit-s\', this.value);" />';
                    content += 'Unit (Plural): <input type="text" id="unitpField' + i + '" value="' + filterHTML(readTag(itemList[i], "unit-p")) + '" onchange="setItemValue(' + i + ', \'unit-p\', this.value);" />';
                    var stackable = '';
                    if(readTag(itemList[i], 'stackable') == '1') {
                        stackable = 'checked';
                    }
                    content += 'Stackable?: <input type="checkbox" id="stackField' + i + '" value="' + filterHTML(readTag(itemList[i], "stackable")) + '" onchange="setItemValue(' + i + ', \'stackable\', (this.checked ? 1:0));" ' + stackable + ' /></br>';
                    content += 'Basic Description: <input type="text" id="bdescField' + i + '" value="' + filterHTML(readTag(itemList[i], "bdescription")) + '" onchange="setItemValue(' + i + ', \'bdescription\', this.value);" /></br>';
                    if(auth) {
                        content += 'Full Description: <input type="text" id="descField' + i + '" value="' + filterHTML(readTag(itemList[i], "description")) + '" onchange="setItemValue(' + i + ', \'description\', this.value);" /></br>';
                    }
                    content += 'Mods: <input type="text" id="modsField' + i + '" value="' + filterHTML(readTag(itemList[i], "mods")) + '" onchange="setItemValue(' + i + ', \'mods\', this.value);" /></br>';
                    
                    content += 'BG Img: <select onchange="setItemValue(' + i + ', \'bg-src\', this.value);">';
                    content += '<option value="">Select...</option>';
                    for(var n = 0; n < itemBacks.length; ++n) {
                        content += '<option value="' + itemBacks[n] + '" ';
                        if(itemBacks[n] == readTag(itemList[i], "bg-src")) {
                            content += 'selected';
                        }
                        content += '>' + itemBacks[n] + '</option>';
                    }
                    content += '</select>';
                    
                    content += 'BG Color: <input type="color" id="bgcField' + i + '" value="' + toColor(readTag(itemList[i], "bg-color")) + '" onchange="setItemValue(' + i + ', \'bg-color\', parseInt(this.value.substring(1), 16));" /></br>';
                    
                    content += 'Icon Img: <select onchange="setItemValue(' + i + ', \'icon-src\', this.value);">';
                    content += '<option value="">Select...</option>';
                    for(var n = 0; n < itemIcons.length; ++n) {
                        content += '<option value="' + itemIcons[n] + '" ';
                        if(itemIcons[n] == readTag(itemList[i], "icon-src")) {
                            content += 'selected';
                        }
                        content += '>' + itemIcons[n] + '</option>';
                    }
                    content += '</select>';
                    
                    content += 'Icon Color: <input type="color" id="iconcField' + i + '" value="' + toColor(readTag(itemList[i], "icon-color")) + '" onchange="setItemValue(' + i + ', \'icon-color\', parseInt(this.value.substring(1), 16));" /></br>';
                    content += '</div>';
                }
                content += '</br><input type="button" value="Add Item" onclick="addInventoryItem()" />';
                content += '</br><center><input type="button" id="submitButton" onclick="uploadInventory()" value="Submit" /> <input type = \"button\" id=\"cancelButton\" onclick=\"showInventory()\" value=\"Cancel\" /></center>';
                document.getElementById("control_panel").innerHTML = '';
                break;
            case "tt": // Edit Traits Talents =================================
                content += '</br><h2><center>Traits</center></h2>';
                for(var i = 0; i < traitList.length; ++i) {
                    content += '<input type="button" value="X" onclick="removeTrait(' + i + ')" /> ' + filterHTML(readTag(traitList[i], "title")) + '</br>';
                }
                content += '</br>';
                content += '<label for=\"trait\">Add Trait:  </label>';
                content += '<select name="trait" id="trait" onchange="addTrait(this.value)">';
                content += '<option value="-1">Select...</option>';
                for(var i = 0; i < charaOptions.length; ++i) {
                    if(charaOptions[i].trait == '1') {
                        content += '<option value="' + charaOptions[i].id + '">' + charaOptions[i].title + '</option>';
                    }
                }
                content += '</select>';
                content += "</br></br></br>";
                
                content += '</br><h2><center>Talents</center></h2>';
                for(var i = 0; i < talentList.length; ++i) {
                    var r = readTag(talentList[i], "rank");
                    content += '<input type="button" value="X" onclick="removeTalent(' + i + ')" /> ' + filterHTML(readTag(talentList[i], "title"));
                    content += ' <input type = "number" id="tr' + i + 'Field" name="tr' + i + 'Field" value="' + r + '" onchange="setTalentRank(' + i + ', this.value)" />' + '</br>';
                }
                content += '</br>';
                content += '<label for=\"talent\">Add Talent:  </label>';
                content += '<select name="talent" id="talent" onchange="addTalent(this.value)">';
                content += '<option value="-1">Select...</option>';
                for(var i = 0; i < charaOptions.length; ++i) {
                    if(charaOptions[i].talent == '1') {
                        content += '<option value="' + charaOptions[i].id + '">' + charaOptions[i].title + '</option>';
                    }
                }
                content += '</select></br>';
                content += '</br><center><input type="button" id="submitButton" onclick="uploadTT()" value="Submit" /> <input type = \"button\" id=\"cancelButton\" onclick=\"showTraitsTalents()\" value=\"Cancel\" /></center>';
                document.getElementById("control_panel").innerHTML = '';
                break;
            case "schools": // Edit School ====================================
                content += '<h2><center>Schools</center></h2>';
                // Populate existing schools
                for(var i = 0; i < schoolList.length; ++i) {
                    var r = readTag(schoolList[i], "level");
                    content += '<input type="button" value="X" onclick="removeSchool(' + i + ')" /> ' + filterHTML(readTag(schoolList[i], "title"));
                    content += ' <input type = "number" id="scl' + i + 'Field" name="tr' + i + 'Field" value="' + r + '" onchange="setSchoolLevel(' + i + ', this.value)" />' + '</br>';
                }
                
                // Populate school options
                content += '<select name="school" id="school" onchange="addSchool(this.value)">';
                content += '<option value="-1">Select...</option>';
                for(var i = 0; i < charaOptions.length; ++i) {
                    if(charaOptions[i].school == '1') {
                        content += '<option value="' + charaOptions[i].id + '">' + charaOptions[i].title + '</option>';
                    }
                }
                content += '</select></br>';
                
                content += '</br><h2><center>Vocations</center></h2>';
                // Populate existing vocations
                for(var i = 0; i < vocationList.length; ++i) {
                    content += '<input type="button" value="X" onclick="removeVocation(' + i + ')" /> ' + filterHTML(vocationList[i]) + '</br>';
                }
                
                content += '<input type = "text" id="newVocationField" /> <input type="button" id="addVocationButton" onClick="addVocation(newVocationField.value)" value="Add" /></br>';
                
                content += '</br><h2><center>Specialized Abilities</center></h2>';
                // Populate existing specialized abilities
                for(var i = 0; i < specializedAbilityList.length; ++i) {
                    content += '<input type="button" value="X" onclick="removeSpecializedAbility(' + i + ')" /> ' + filterHTML(readTag(specializedAbilityList[i], "title")) + '</br>';
                }
                
                // Populate specialized ability options
                content += '<select name="sability" id="sability" onchange="addSpecializedAbility(this.value)">';
                content += '<option value="-1">Select...</option>';
                for(var i = 0; i < charaOptions.length; ++i) {
                    if(charaOptions[i].ability == '1') {
                        content += '<option value="' + charaOptions[i].id + '">' + charaOptions[i].title + '</option>';
                    }
                }
                content += '</select></br>';
                content += '</br><h2><center>Auxiliary Abilities</center></h2>';
                // Populate existing auxiliary abilities
                for(var i = 0; i < auxiliaryAbilityList.length; ++i) {
                    content += '<input type="button" value="X" onclick="removeAuxiliaryAbility(' + i + ')" /> ' + filterHTML(readTag(auxiliaryAbilityList[i], "title")) + '</br>';
                }
                
                // Populate auxiliary ability options
                content += '<select name="aability" id="aability" onchange="addAuxiliaryAbility(this.value)">';
                content += '<option value="-1">Select...</option>';
                for(var i = 0; i < charaOptions.length; ++i) {
                    if(charaOptions[i].ability == '1') {
                        content += '<option value="' + charaOptions[i].id + '">' + charaOptions[i].title + '</option>';
                    }
                }
                content += '</select></br>';
                content += '</br><center><input type="button" id="submitButton" onclick="uploadSchools()" value="Submit" /> <input type = \"button\" id=\"cancelButton\" onclick=\"showSchools()\" value=\"Cancel\" /></center>';
                document.getElementById("control_panel").innerHTML = '';
                break;
            case "notes": // Edit Notes =======================================
                content += '<form name="notes_form" class="form-space" method="post" action="update_character.php?id=' + charaData.id + '&page=notes">';
                content += '<label for="notes0Field">Notes 0: </label><textarea id="notes0Field" name="notes0Field">' + charaData.notes_0 + '</textarea></br>';
                content += '<label for="notes1Field">Notes 1: </label><textarea id="notes1Field" name="notes1Field">' + charaData.notes_1 + '</textarea></br>';
                content += '<label for="notes2Field">Notes 2: </label><textarea id="notes2Field" name="notes2Field">' + charaData.notes_2 + '</textarea></br>';
                content += '<label for="notes3Field">Notes 3: </label><textarea id="notes3Field" name="notes3Field">' + charaData.notes_3 + '</textarea></br>';
                content += '</br><center><input type="submit" value="Submit" /> <input type = "button" id="cancelButton" onclick="showNotes()" value="Cancel" /></center>';
                content += '</form>';
                document.getElementById('control_panel').innerHTML = '';
                break;
        }
        document.getElementById("display_area").innerHTML = content;
    }
    
    function showBasics() {
        page = "basics";
        var text = "";
        try {
            var conductString = "";
            var moralityString = "";
            if(charaData.conduct < -40) {
                conductString = "Chaotic"
            } else if(charaData.conduct <= 40) {
                conductString = "Neutral";
            } else {
                conductString = "Lawful";
            }
            
            if(charaData.morality < -40) {
                moralityString = "Evil"
            } else if(charaData.morality <= 40) {
                moralityString = "Neutral";
            } else {
                moralityString = "Good";
            }
            text += "Name: " + filterHTML(charaData.name) + "</br>";
            text += "Race: " + filterHTML(charaData.race) + "</br>";
            text += "Gender: " + filterHTML(charaData.gender) + "</br>";
            text += "Height: " + filterHTML(charaData.height) + "</br>";
            text += "Weight: " + filterHTML(charaData.weight) + "</br>";
            text += "Age: " + filterHTML(charaData.age) + "</br>";
            text += "Birth: " + filterHTML(charaData.birth) + "</br>";
            text += "Hair: " + filterHTML(charaData.hair) + "</br>";
            text += "Eyes: " + filterHTML(charaData.eyes) + "</br>";
            text += "Homeland: " + filterHTML(charaData.home) + "</br>";
            text += "Appearance: " + filterHTML(charaData.appearance) + "</br>";
            text += "Biography: " + filterHTML(charaData.biography) + "</br>";
            text += "Languages: " + filterHTML(charaData.languages) + "</br>";
            text += "Conduct: " + filterHTML(conductString) + "  (" + filterHTML(charaData.conduct) + ")</br>";
            text += "Morality: " + filterHTML(moralityString) + "  (" + filterHTML(charaData.morality) + ")</br>";
            text += "Fame: " + filterHTML(charaData.fame) + "</br>";
            text += "Infamy: " + filterHTML(charaData.infamy) + "</br>";
            text += "</br>Modules: " + filterHTML(charaData.modules.replace("|", ", ")) + "</br>";
            document.getElementById("display_area").innerHTML = text;
        } catch(err) {
            document.getElementById("display_area").innerHTML = err.message;
        }
        document.getElementById("control_panel").innerHTML = '</br><center><?php echo $cp; ?></center>';
    }
    
    function showProficiencies() {
        page = "proficiencies";
        var text = "";
        try {
            var cRace = charaData.race.toLowerCase();
            var cMaxHP;
            var cMaxEP;
            var cMaxMP;
            var cCurEP;
            var cCurMP;
            
            switch(cRace) {
                case 'daea':
                    cMaxHP = vitalityLookup('standardhp', charaData.level);
                    cMaxEP = vitalityLookup('standardepmp', charaData.level);
                    cMaxMP = vitalityLookup('standardepmp', charaData.level);
                    break;
                case 'darkan':
                    cMaxHP = vitalityLookup('lowhp', charaData.level);
                    cMaxEP = vitalityLookup('highepmp', charaData.level);
                    cMaxMP = vitalityLookup('standardepmp', charaData.level);
                    break;
                case 'dehnamyn':
                    cMaxHP = vitalityLookup('lowesthp', charaData.level);
                    cMaxEP = vitalityLookup('highepmp', charaData.level);
                    cMaxMP = vitalityLookup('highepmp', charaData.level);
                    break;
                case 'dwarf':
                    cMaxHP = vitalityLookup('highhp', charaData.level);
                    cMaxEP = vitalityLookup('highepmp', charaData.level);
                    cMaxMP = vitalityLookup('lowestepmp', charaData.level);
                    break;
                case 'elf':
                    cMaxHP = vitalityLookup('lowhp', charaData.level);
                    cMaxEP = vitalityLookup('standardepmp', charaData.level);
                    cMaxMP = vitalityLookup('highepmp', charaData.level);
                    break;
                case 'gnome':
                    cMaxHP = vitalityLookup('lowesthp', charaData.level);
                    cMaxEP = vitalityLookup('highepmp', charaData.level);
                    cMaxMP = vitalityLookup('highepmp', charaData.level);
                    break;
                case 'human':
                    cMaxHP = vitalityLookup('standardhp', charaData.level);
                    cMaxEP = vitalityLookup('standardepmp', charaData.level);
                    cMaxMP = vitalityLookup('standardepmp', charaData.level);
                    break;
                case 'illinsern':
                    cMaxHP = vitalityLookup('lowhp', charaData.level);
                    cMaxEP = vitalityLookup('lowepmp', charaData.level);
                    cMaxMP = vitalityLookup('highestepmp', charaData.level);
                    break;
                case 'koahdu':
                    cMaxHP = vitalityLookup('highesthp', charaData.level);
                    cMaxEP = vitalityLookup('standardepmp', charaData.level);
                    cMaxMP = vitalityLookup('lowestepmp', charaData.level);
                    break;
                case 'lyca':
                    cMaxHP = vitalityLookup('standardhp', charaData.level);
                    cMaxEP = vitalityLookup('standardepmp', charaData.level);
                    cMaxMP = vitalityLookup('standardepmp', charaData.level);
                    break;
                case 'nautaia':
                    cMaxHP = vitalityLookup('lowhp', charaData.level);
                    cMaxEP = vitalityLookup('standardepmp', charaData.level);
                    cMaxMP = vitalityLookup('highepmp', charaData.level);
                    break;
                case 'nehma':
                    cMaxHP = vitalityLookup('lowhp', charaData.level);
                    cMaxEP = vitalityLookup('lowepmp', charaData.level);
                    cMaxMP = vitalityLookup('highestepmp', charaData.level);
                    break;
                case 'viyr':
                    cMaxHP = vitalityLookup('standardhp', charaData.level);
                    cMaxEP = vitalityLookup('standardepmp', charaData.level);
                    cMaxMP = vitalityLookup('standardepmp', charaData.level);
                    break;
                default:
                    cMaxHP = vitalityLookup('standardhp', charaData.level);
                    cMaxEP = vitalityLookup('standardepmp', charaData.level);
                    cMaxMP = vitalityLookup('standardepmp', charaData.level);
                    break;
            }
            
            
            cMaxEP += Math.floor((attributeLookup('stamina', charaData.strength) + attributeLookup('stamina', charaData.fortitude)) / 2);
            cMaxMP += Math.floor((attributeLookup('channeling', charaData.intelligence) + attributeLookup('channeling', charaData.charisma)) / 2);
            
            cCurEP = parseInt(cMaxEP) + parseInt(charaData.stamina_adj) - parseInt(charaData.exhaust);
            cCurMP = parseInt(cMaxMP) + parseInt(charaData.channel_adj) - parseInt(charaData.exhaust);
            
            var cPower = Math.floor((attributeLookup("power", charaData.strength) + attributeLookup("power", charaData.agility)) / 2);
            var cMagicPower = Math.floor((attributeLookup('magicpower', charaData.intelligence) + attributeLookup('magicpower', charaData.agility)) / 2);
            var cDamage;
            var CMDamage;
            var cRange;
            var cMRange;
            var hand = '';
            if(charaData.rh != '') {
                hand = charaData.rh;
            } else {
                hand = charaData.lh;
            }
            if(hand == '') {
                if(parseInt(charaData.strength) > parseInt(charaData.agility)) {
                    cDamage = attributeLookup('basedamage', charaData.strength);
                } else {
                    cDamage = attributeLookup('basedamage', charaData.agility);
                }
                cMDamage = attributeLookup('basemagicdamage', charaData.intelligence);
                cRange = 1;
                cMRange = 10;
            } else {
                var mod = readTag(hand, 'mod-prof');
                cDamage = readTag(hand, 'dmg');
                cMDamage = readTag(hand, 'mdmg');
                cPower = parseInt(cPower) + parseInt(readTag(hand, 'pow'));
                cMagicPower = parseInt(cMagicPower) + parseInt(readTag(hand, 'mpow'));
                if(mod.includes('STR') && mod.includes('AGI')) {
                    if(parseInt(charaData.strength) > parseInt(charaData.agility)) {
                        cDamage += ' + ' + attributeLookup('basedamage', charaData.strength);
                    } else {
                        cDamage += ' + ' + attributeLookup('basedamage', charaData.agility);
                    }
                } else if(mod.includes('STR')) {
                    cDamage += ' + ' + attributeLookup('basedamage', charaData.strength);
                } else if(mod.includes('AGI')) {
                    cDamage += ' + ' + attributeLookup('basedamage', charaData.agility);
                }
                if(mod.includes('INT')) {
                    cMDamage += ' + ' + attributeLookup('basemagicdamage', charaData.intelligence);
                }
                cRange = readTag(hand, 'rng');
                cMRange = readTag(hand, 'mrng');
            }
            
            var cDefense = Math.floor((attributeLookup('basedefense', charaData.strength) + attributeLookup('basedefense', charaData.fortitude)) / 2);
            if(charaData.torso != '') {
                cDefense = parseInt(cDefense) + parseInt(readTag(charaData.torso, "def"));
            }
            var cMDefense = Math.floor((attributeLookup('basemagicdefense', charaData.intelligence) + attributeLookup('basemagicdefense', charaData.charisma)) / 2);
            if(charaData.torso != '') {
                cMDefense = parseInt(cMDefense) + parseInt(readTag(charaData.torso, "mdef"));
            }
            var cAccuracy = attributeLookup('accuracy', charaData.agility);
            var cDodge = attributeLookup('dodge', charaData.agility);
            var cSpeed = attributeLookup('speed', charaData.agility);
            var cAwareness = attributeLookup('awareness', charaData.intelligence);
            var cWeight = attributeLookup('weightallowance', charaData.strength);
            var cRegen = Math.floor((attributeLookup('regen', charaData.fortitude) + attributeLookup('regen', charaData.charisma)) / 2);
            var cSurvival = attributeLookup('survival', charaData.fortitude);
            var cWillpower = Math.floor((attributeLookup('willpower', charaData.charisma) + attributeLookup('willpower', charaData.intelligence)) / 2);
            var cStealth = Math.floor((attributeLookup('stealth', charaData.agility) + attributeLookup('stealth', charaData.intelligence)) / 2);
            
            text += "Level: " + charaData.level + "</br>";
            text += "XP: " + charaData.xp_current + " / " + charaData.xp_next + "</br>";
            text += "</br>";
            text += '<center><div style="display:table; width:95%; table-layout:fixed;">';
            text += '<div style="display:table-row;">';
            text += '<div style="display:table-cell; width:20%; padding: 5px 5px 5px 5px; border:1px solid; background-color:#dddddd;">';
            text += '<center><b>STR</b></br><div style="font-size:1.5em; border:1px solid; background-color:#ffffff; border-radius:25%;">' + charaData.strength + '</div>(' + (charaData.strength > 0 ? '+' : '') + Math.floor(charaData.strength / 2) + ')</center>';
            text += '</div>';
            text += '<div style="display:table-cell; width:20%; padding: 5px 5px 5px 5px; border:1px solid; background-color:#dddddd;">';
            text += '<center><b>AGI</b></br><div style="font-size:1.5em; border:1px solid; background-color:#ffffff; border-radius:25%;">' + charaData.agility + '</div>(' + (charaData.agility > 0 ? '+' : '') + Math.floor(charaData.agility / 2) + ')</center>';
            text += '</div>';
            text += '<div style="display:table-cell; width:20%; padding: 5px 5px 5px 5px; border:1px solid; background-color:#dddddd;">';
            text += '<center><b>INT</b></br><div style="font-size:1.5em; border:1px solid; background-color:#ffffff; border-radius:25%;">' + charaData.intelligence + '</div>(' + (charaData.intelligence > 0 ? '+' : '') + Math.floor(charaData.intelligence / 2) + ')</center>';
            text += '</div>';
            text += '<div style="display:table-cell; width:20%; padding: 5px 5px 5px 5px; border:1px solid; background-color:#dddddd;">';
            text += '<center><b>FOR</b></br><div style="font-size:1.5em; border:1px solid; background-color:#ffffff; border-radius:25%;">' + charaData.fortitude + '</div>(' + (charaData.fortitude > 0 ? '+' : '') + Math.floor(charaData.fortitude / 2) + ')</center>';
            text += '</div>';
            text += '<div style="display:table-cell; width:20%; padding: 5px 5px 5px 5px; border:1px solid; background-color:#dddddd;">';
            text += '<center><b>CHA</b></br><div style="font-size:1.5em; border:1px solid; background-color:#ffffff; border-radius:25%;">' + charaData.charisma + '</div>(' + (charaData.charisma > 0 ? '+' : '') + Math.floor(charaData.charisma / 2) + ')</center>';
            text += '</div>';
            text += '</div>';
            text += '</div></center>';
            text += "</br>";
            text += '<center><b>HP</b></center>';
            text += '<div id="hp_bar_container"><canvas id="hp_bar" width="200" height="24" /></div>';
            text += '<div style="display:table; width:100%;"><div style="display:table-row; table-layout:fixed;">'
            text += '<div id="ep_bar_container" style="display:table-cell; width:49%"><center><b>EP</b><canvas id="ep_bar" width="100" height="24" /></center></div>';
            text += '<div id="mp_bar_container" style="display:table-cell; width:49%"><center><b>MP</b><canvas id="mp_bar" width="100" height="24" /></center></div>';
            text += '</div></div>';
            text += "Exhaustion: " + charaData.exhaust + "</br>";
            text += "</br>";
            text += "Defense: " + cDefense + "</br>";
            text += "Magic Defense: " + cMDefense + "</br>";
            text += "Accuracy: " + cAccuracy + "</br>";
            text += "Dodge/Block: " + cDodge + "</br>";
            text += "Speed: " + cSpeed + "</br>";
            text += "Power: " + cPower + "</br>";
            text += "Damage: " + cDamage + "</br>";
            text += "Range: " + cRange + "</br>";
            text += "Magic Power: " + cMagicPower + "</br>";
            text += "Magic Damage: " + cMDamage + "</br>";
            text += "Magic Range: " + cMRange + "</br>";
            text += "</br>";
            text += "Awareness: " + cAwareness + "</br>";
            text += "Weight Allowance: " + cWeight + "</br>";
            text += "Regen: " + cRegen + "</br>";
            text += "Survival: " + cSurvival + "</br>";
            text += "Willpower: " + cWillpower + "</br>";
            text += "Stealth: " + cStealth + "</br>";
            
            document.getElementById("display_area").innerHTML = text;
            
            
            // Draw Meters ====================================================
            var fill = 0;
            if(parseInt(charaData.hp_max) != 0) {
                if(parseInt(charaData.hp_current) > 0) {
                    fill = parseInt(charaData.hp_current) / parseInt(charaData.hp_max);
                    if(fill > 1) fill = 1;
                }
            }
            drawMeter("hp_bar", "hp_bar_container", charaData.hp_current + ' / ' + charaData.hp_max, "red", fill);
            
            fill = 0;
            if(parseInt(cCurEP) != 0) {
                if(parseInt(cCurEP) > 0) {
                    fill = parseInt(cCurEP) / parseInt(cMaxEP);
                    if(fill > 1) fill = 1;
                }
            }
            drawMeter("ep_bar", "ep_bar_container", cCurEP + ' / ' + cMaxEP, "yellow", fill);
            
            fill = 0;
            if(parseInt(cCurMP) != 0) {
                if(parseInt(cCurMP) > 0) {
                    fill = parseInt(cCurMP) / parseInt(cMaxMP);
                    if(fill > 1) fill = 1;
                }
            }
            drawMeter("mp_bar", "mp_bar_container", cCurMP + ' / ' + cMaxMP, "blue", fill);
            // ===============================================================
        } catch(err) {
            document.getElementById("display_area").innerHTML = err.message;
        }
        document.getElementById("control_panel").innerHTML = '</br><center><?php echo $cp; ?></center>';
    }
    
    function drawMeter(meter, meterContainer, meterText, meterColor, fillPercent) {
        var can = document.getElementById(meter);
        var ctx = can.getContext("2d");
        ctx.canvas.width = document.getElementById(meterContainer).clientWidth;
        
        var grd = ctx.createLinearGradient(0, 0, 0, 30);
        grd.addColorStop(0, "black");
        grd.addColorStop(1, "grey");
        
        ctx.beginPath();
        ctx.fillStyle = grd;
        ctx.fillRect(0, 0, can.width, 24);
        
        if(fillPercent > 0 && fillPercent <= 1) {
            grd = ctx.createLinearGradient(0, 0, 0, 30);
            grd.addColorStop(0, meterColor);
            grd.addColorStop(1, "black");
            
            ctx.beginPath();
            ctx.fillStyle = grd;
            ctx.fillRect(0, 0, can.width * fillPercent, 24);
        }
        
        ctx.beginPath();
        ctx.fillStyle = "white";
        ctx.font = "bold 20px Arial";
        ctx.fillText(meterText, (ctx.canvas.width / 2) - ctx.measureText(meterText).width / 2, 18);
        ctx.strokeText(meterText, (ctx.canvas.width / 2) - ctx.measureText(meterText).width / 2, 18);
    }
    
    function showInventory() {
        page = "inventory";
        var text = "";
        
        text += '<div style="border:1px solid; padding: 5px 5px 5px 5px;">'
        text += '<h2><center><b>Wallet</b></center></h2>';
        text += '<center>';
        var ccount = readTag(charaData.wallet, "allervian");
        var tcount = 0;
        if(ccount == 1) {
            text += ccount + ' Allervian  ';
            tcount += ccount * 100000;
        } else if(ccount > 1) {
            text += ccount + ' Allervians  ';
            tcount += ccount * 100000;
        }
        ccount = readTag(charaData.wallet, 'florin');
        if(ccount == 1) {
            text += ccount + ' Florin  ';
            tcount += ccount * 10000;
        } else if(ccount > 1) {
            text += ccount + ' Florins  ';
            tcount += ccount * 10000;
        }
        ccount = readTag(charaData.wallet, 'drake');
        if(ccount == 1) {
            text += ccount + ' Drake  ';
            tcount += ccount * 1000;
        } else if(ccount > 1) {
            text += ccount + ' Drakes  ';
            tcount += ccount * 1000;
        }
        ccount = readTag(charaData.wallet, 'denarius');
        if(ccount == 1) {
            text += ccount + ' Denarius  ';
            tcount += ccount * 100;
        } else if(ccount > 1) {
            text += ccount + ' Denarii  ';
            tcount += ccount * 100;
        }
        ccount = readTag(charaData.wallet, 'mark');
        if(ccount == 1) {
            text += ccount + ' Mark  ';
            tcount += ccount * 1;
        } else if(ccount > 1) {
            text += ccount + ' Marks  ';
            tcount += ccount * 1;
        }
        text += '</br><b>Total</b>: ' + tcount;
        text += '</center>';
        text += '</div>';
        
        itemList = readTagMulti(charaData.inventory, "item");
        text += '<div style="display:table; width:100%; table-layout:fixed;">';
        text += '<div style="display:table-row;">';
        text += '<div style="display:table-cell; width:50%; padding: 5px 5px 5px 5px;">';
        text += '<h2><center><b>Items</b></center></h2>';
        if(itemList.length > 0) {
            for(var i = 0; i < itemList.length; ++i) {
                text += formatItemData(itemList[i], 0, i);
            }
        } else {
            text += "Inventory Empty";
        }
        text += '</div>';
        text += '<div style="display:table-cell; width:50%; padding: 5px 5px 5px 5px;">';
        text += '<h2><center><b>Equipment</b></center></h2>';
        if(charaData.ammo != '') {
            text += formatItemData(charaData.ammo, 1, 'ammo');
        }
        if(charaData.head != '') {
            text += formatItemData(charaData.head, 1, 'head');
        }
        if(charaData.neck != '') {
            text += formatItemData(charaData.neck, 1, 'neck');
        }
        if(charaData.torso != '') {
            text += formatItemData(charaData.torso, 1, 'torso');
        }
        if(charaData.legs != '') {
            text += formatItemData(charaData.legs, 1, 'legs');
        }
        if(charaData.feet != '') {
            text += formatItemData(charaData.feet, 1, 'feet');
        }
        if(charaData.waist != '') {
            text += formatItemData(charaData.waist, 1, 'waist');
        }
        if(charaData.hands != '') {
            text += formatItemData(charaData.hands, 1, 'hands');
        }
        if(charaData.lh != '') {
            text += formatItemData(charaData.lh, 1, 'lh');
        }
        if(charaData.rh != '') {
            text += formatItemData(charaData.rh, 1, 'rh');
        }
        if(charaData.ring1 != '') {
            text += formatItemData(charaData.ring1, 1, 'ring1');
        }
        if(charaData.ring2 != '') {
            text += formatItemData(charaData.ring2, 1, 'ring2');
        }
        if(charaData.ring3 != '') {
            text += formatItemData(charaData.ring3, 1, 'ring3');
        }
        if(charaData.ring4 != '') {
            text += formatItemData(charaData.ring4, 1, 'ring4');
        }
        if(charaData.ring5 != '') {
            text += formatItemData(charaData.ring5, 1, 'ring5');
        }
        if(charaData.ring6 != '') {
            text += formatItemData(charaData.ring6, 1, 'ring6');
        }
        text += '</div>';
        text += '</div>';
        text += '</div>';
        
        document.getElementById("display_area").innerHTML = text;
        document.getElementById("control_panel").innerHTML = '</br><center><?php echo $cp; ?></center>';
    }
    
    function showTraitsTalents() {
        page = "tt";
        var text = "";
        traitList = readTagMulti(charaData.traits, "trait");
        talentList = readTagMulti(charaData.talents, "talent");
        var traits = readTagMulti(charaData.traits, "trait");
        var talents = readTagMulti(charaData.talents, "talent");
        
        text += "<center><h2>Traits</h2></center>";
        if(traits.length > 0) {
            for(var i = 0; i < traits.length; ++i) {
                text += "<b>" + filterHTML(readTag(traits[i], "title")) + "</b></br>" + filterHTML(readTag(traits[i], "description"));
                text += "</br></br>";
            }
        } else {
            text += "No Traits";
        }
        
        text += "</br></br>";
        text += "<center><h2>Talents</h2></center>";
        if(talents.length > 0) {
            for(var i = 0; i < talents.length; ++i) {
                text += "<b>" + filterHTML(readTag(talents[i], "title")) + "</b> - Rank " + filterHTML(readTag(talents[i], "rank")) + "</br>";
                text += filterHTML(readTag(talents[i], "description"));
                text += "</br></br>";
            }
        } else {
            text += "No Talents";
        }
        document.getElementById("display_area").innerHTML = text;
        document.getElementById("control_panel").innerHTML = '</br><center><?php echo $cp; ?></center>';
    }
    
    function showSchools() {
        page = "schools";
        var text = "";
        text += '<div style="display:table; width:100%; table-layout:fixed;">';
        text += '<div style="display:table-row;">';
        text += '<div style="display:table-cell; width:65%; padding: 5px 5px 5px 5px;">';
        text += '<h2><center><b>Schools</b></center></h2>';
        var schools = readTagMulti(charaData.schools, "school");
        if(schools.length > 0) {
            for(var i = 0; i < schools.length; ++i) {
                text += '<b>' + filterHTML(readTag(schools[i], 'title')) + '</b> - Level ' + filterHTML(readTag(schools[i], 'level')) + '</br>';
                text += filterHTML(readTag(schools[i], 'description'));
                text += '</br></br>';
            }
        } else {
            text += "No Schools";
        }
        text += '</div>';
        
        text += '<div style="display:table-cell; width:35%; padding: 5px 5px 5px 5px;">';
        text += '<h2><center><b>Vocations</b></center></h2>';
        vocationList = readTagMulti(charaData.vocations, "vocation");
        text += '<center>';
        if(vocationList.length > 0) {
            for(var i = 0; i < vocationList.length; ++i) {
                text += vocationList[i] + '</br>';
            }
        } else {
            text += "No Vocations";
        }
        text += '</center>';
        text += '</div></div></div>';
        text += '<h2><center><b>Specialized Abilities</b></center></h2>';
        text += '<div style="display:table; width:100%; table-layout:fixed;"><div style="display:table-row;">';
        for(var i = 0; i <= 10; ++i) {
            if(i == 5) {
                text += '</div>';
                text += '<div style="display:table-row;">';
            }
            if(i < specializedAbilityList.length) {
                text += '<div style="display:table-cell; cursor:pointer; width:20%;" onclick="showSpecializedAbility(' + i + ');">';
                text += '<center><img src="../book01.png" /></center>';
                text += '</div>';
            } else {
                text += '<div style="display:table-cell;">';
                text += '</div>';
            }
        }
        text += '</div></div>';
        text += '<div id="ability_display"></div>';
        
        text += '<h2><center><b>Auxiliary Abilities</b></center></h2>';
        text += '<div style="display:table; width:100%; table-layout:fixed;">';
        var n = 0;
        while(n < auxiliaryAbilityList.Length || n <= 5) {
            if(n % 5 == 0) {
                if(n >= 5) {
                    text += '</div>';
                }
                text += '<div style="display:table-row;">';
            }
            
            if(n < auxiliaryAbilityList.length) {
                text += '<div style="display:table-cell; cursor:pointer; width:20%;" onclick="showAuxiliaryAbility(' + n + ');">';
                text += '<center><img src="../book01.png" /></center>';
                text += '</div>';
            } else {
                text += '<div style="display:table-cell; width:20%;">';
                text += '</div>';
            }
            ++n;
        }
        text += '</div></div>';
        
        text += '<div id="aux_display"></div>';
        
        document.getElementById("display_area").innerHTML = text;
        document.getElementById("control_panel").innerHTML = '</br><center><?php echo $cp; ?></center>';
    }
    
    function showNotes() {
        page = "notes";
        var text = "";
        try {
            text += "Notes 0: " + filterHTML(charaData.notes_0) + "</br>";
            text += "</br>";
            text += "Notes 1: " + filterHTML(charaData.notes_1) + "</br>";
            text += "</br>";
            text += "Notes 2: " + filterHTML(charaData.notes_2) + "</br>";
            text += "</br>";
            text += "Notes 3: " + filterHTML(charaData.notes_3) + "</br>";
            document.getElementById("display_area").innerHTML = text;
        } catch(err) {
            document.getElementById("display_area").innerHTML = err.message;
        }
        document.getElementById("control_panel").innerHTML = '</br><center><?php echo $cp; ?></center>';
    }
    
    function showSpecializedAbility(index) {
        if(index < specializedAbilityList.length) {
            var text = '';
            text += '<b>' + readTag(specializedAbilityList[index], 'title') + '</b></br>';
            text += 'Level: ' + readTag(specializedAbilityList[index], 'level') + ' ' + readTag(specializedAbilityList[index], 'school') + ' ' + readTag(specializedAbilityList[index], 'type') + ' - ' + readTag(specializedAbilityList[index], 'vocation') + '</br>';
            text += 'Cost: ' + readTag(specializedAbilityList[index], 'cost') + '</br>';
            text += 'Exhaustion: ' + readTag(specializedAbilityList[index], 'exhaust') + '</br>';
            text += 'Range: ' + readTag(specializedAbilityList[index], 'range') + '</br>';
            text += 'Duration: ' + readTag(specializedAbilityList[index], 'duration') + '</br>';
            var acc = readTag(specializedAbilityList[index], 'accuracy_check');
            if(acc == '1' || acc.toLowerCase() == 'y') {
                acc = 'Yes';
            } else {
                acc = 'No';
            }
            text += 'Acc. Check: ' + acc + '</br>';
            text += '</br>' + readTag(specializedAbilityList[index], 'description');
            document.getElementById("ability_display").innerHTML = text;
        } else {
            document.getElementById("ability_display").innerHTML = "";
        }
    }
    
    function showAuxiliaryAbility(index) {
        if(index < auxiliaryAbilityList.length) {
            var text = '';
            text += '<b>' + readTag(auxiliaryAbilityList[index], 'title') + '</b></br>';
            text += 'Level: ' + readTag(auxiliaryAbilityList[index], 'level') + ' ' + readTag(auxiliaryAbilityList[index], 'school') + ' ' + readTag(auxiliaryAbilityList[index], 'type') + ' - ' + readTag(auxiliaryAbilityList[index], 'vocation') + '</br>';
            text += 'Cost: ' + readTag(auxiliaryAbilityList[index], 'cost') + '</br>';
            text += 'Exhaustion: ' + readTag(auxiliaryAbilityList[index], 'exhaust') + '</br>';
            text += 'Range: ' + readTag(auxiliaryAbilityList[index], 'range') + '</br>';
            text += 'Duration: ' + readTag(auxiliaryAbilityList[index], 'duration') + '</br>';
            var acc = readTag(auxiliaryAbilityList[index], 'accuracy_check');
            if(acc == '1' || acc.toLowerCase() == 'y') {
                acc = 'Yes';
            } else {
                acc = 'No';
            }
            text += 'Acc. Check: ' + acc + '</br>';
            text += '</br>' + readTag(auxiliaryAbilityList[index], 'description');
            document.getElementById("aux_display").innerHTML = text;
        } else {
            document.getElementById("aux_display").innerHTML = "";
        }
    }
    
    function formatItemData(itemString, equipped, tag) {
        var wl = '';
        var auth = 0;
        var it = '';
        var t, bt, d, bd;
        var text = '';
        text += '<div style="border:1px solid">';
        var bg = readTag(itemString, 'bg-src');
        var ico = readTag(itemString, 'icon-src');
        var clickReaction = (equipped ? 'onclick="unequipItem(' + tag + ')"' : 'onclick="equipItem(' + tag + ')"');
        if(equipped) {
            clickReaction = 'onclick="unequipItem(\'' + tag + '\')"';
        } else {
            clickReaction = 'onclick="equipItem(' + tag + ')"';
        }
        text += '<div class="item_icon" ' + (bg == 'style="cursor:pointer"' ? '' : 'style="background-image:url(\'../' + bg + '.png\'); cursor:pointer"') + ' ' + clickReaction + '>';
        text += ico == '' ? '' : '<img src="../icons/' + ico + '.png" />';
        text += '</div>';
        auth = 0;
        wl = readTag(itemString, 'whitelist').split(',');
        for(var n = 0; n < wl.length; ++n) {
            if(wl[n] == user) {
                auth = 1;
            }
        }
        it = readTag(itemString, "type");
        
        t = readTag(itemString, 'title');
        bt = readTag(itemString, 'btitle');
        d = readTag(itemString, 'description');
        bd = readTag(itemString, 'bdescription');
        text += '<b>';
        
        if(auth) {
            if(t == '') {
                text += filterHTML(bt);
            } else {
                text += filterHTML(t);
            }
        } else {
            text += filterHTML(bt);
        }
        text += '</b></br>';
        
        if(it < itemTypes.length) {
            text += itemTypes[it];
        }
        text += ' - ' + readTag(itemString, 'long-type') + '</br>';
        text += readTag(itemString, 'weight') + ' lb(s). - ' + readTag(itemString, 'value') + ' Coin</br>';
        
        switch(itemTypes[it]) {
            case 'Generic':
                
                break;
            case 'Weapon':
                text += 'DMG: ' + readTag(itemString, 'dmg') + '  POW: ' + readTag(itemString, 'pow') + '  Rng: ' + readTag(itemString, 'rng') + '</br>';
                text += 'MDMG: ' + readTag(itemString, 'mdmg') + '  MPOW: ' + readTag(itemString, 'mpow') + '  M.Rng: ' + readTag(itemString, 'mrng') + '</br>';
                break;
            case 'Armor':
                text += 'DEF: ' + readTag(itemString, 'def') + '  MDEF: ' + readTag(itemString, 'mdef') + '</br>';
                break;
            case 'Accessory':
                
                break;
            case 'Consumable':
                
                break;
            case 'Ammunition':
                text += 'DMG: ' + readTag(itemString, 'dmg') + '</br>';
                break;
            case 'Component':
                
                break;
            default:
            
                break;
        }
        
        if(auth) {
            if(t == '') {
                text += filterHTML(bd);
            } else {
                text += filterHTML(d);
            }
        } else {
            text += filterHTML(bd);
        }
        text += '</br>';
        text += '</div>';
        
        return text;
    }
    
    function setCoinCount(tag, val) {
        walletList = setTag(walletList, tag, val);
    }
    
    function setTalentRank(index, rank) {
        if(index < talentList.length) {
            talentList[index] = setTag(talentList[index], "rank", rank);
        }
    }
    
    function setSchoolLevel(index, level) {
        if(index < schoolList.length) {
            schoolList[index] = setTag(schoolList[index], "level", level);
        }
    }
    
    function setItemValue(index, tag, val) {
        if(index < itemList.length) {
            itemList[index] = setTag(itemList[index], tag, val);
        }
    }
    
    function uploadTT() {
        try {
            var xhr = new XMLHttpRequest();
            var traitText = '';
            for(var i = 0; i < traitList.length; ++i) {
                traitText += '[trait]' + traitList[i] + '[/trait]';
            }
            var talentText = '';
            for(var i = 0; i < talentList.length; ++i) {
                talentText += '[talent]' + talentList[i] + '[/talent]';
            }
            xhr.open("POST", 'http://ayaseye.com/characters/update_character.php', false);
            xhr.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
            xhr.send(JSON.stringify({ "id":charaData.id, "traits":traitText, "talents":talentText}));
            
            charaData.traits = traitText;
            charaData.talents = talentText;
            showTraitsTalents();
        } catch(err) {
            document.getElementById("debug_area").innerText = err.message;
        }
    }
    
    function uploadSchools() {
        try {
            var xhr = new XMLHttpRequest();
            var schoolText = '';
            for(var i = 0; i < schoolList.length; ++i) {
                schoolText += '[school]' + schoolList[i] + '[/school]';
            }
            var vocationText = '';
            for(var i = 0; i < vocationList.length; ++i) {
                vocationText += '[vocation]' + vocationList[i] + '[/vocation]';
            }
            var sabilityText = '';
            for(var i = 0; i < specializedAbilityList.length; ++i) {
                sabilityText += '[ability]' + specializedAbilityList[i] + '[/ability]';
            }
            var aabilityText = '';
            for(var i = 0; i < auxiliaryAbilityList.length; ++i) {
                aabilityText += '[ability]' + auxiliaryAbilityList[i] + '[/ability]';
            }
            xhr.open("POST", 'http://ayaseye.com/characters/update_character.php', false);
            xhr.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
            xhr.send(JSON.stringify({ "id":charaData.id, "schools":schoolText, "vocations":vocationText, "abilities":sabilityText, "aux_abilities":aabilityText }));
            
            charaData.schools = schoolText;
            charaData.vocations = vocationText;
            charaData.abilities = sabilityText;
            charaData.aux_abilities = aabilityText;
            showSchools();
        } catch(err) {
            document.getElementById("debug_area").innerText = err.message;
        }
    }
    
    function uploadInventory() {
        try {
            var xhr = new XMLHttpRequest();
            var invText = '';
            for(var i = 0; i < itemList.length; ++i) {
                invText += '[item]' + itemList[i] + '[/item]';
            }
            xhr.open("POST", 'http://ayaseye.com/characters/update_character.php', false);
            xhr.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
            xhr.send(JSON.stringify({ "id":charaData.id, 
                "inventory":invText, "wallet":walletList, "ammo":charaData.ammo, "head":charaData.head, "neck":charaData.neck, "torso":charaData.torso, "legs":charaData.legs,
                "feet":charaData.feet, "waist":charaData.waist, "hands":charaData.hands, "lh":charaData.lh, "rh":charaData.rh, "ring1":charaData.ring1, "ring2":charaData.ring2,
                "ring3":charaData.ring3, "ring4":charaData.ring4, "ring5":charaData.ring5, "ring6":charaData.ring6
            }));
            
            charaData.inventory = invText;
            charaData.wallet = walletList;
            showInventory();
        } catch(err) {
            document.getElementById("debug_area").innerText = err.message;
        }
    }
    
    function equipItem(index) {
        if(index >= 0 && index < itemList.length) {
            var target = itemList[index];
            var type = itemTypes[readTag(target, 'type')];
            var longtype = readTag(target, 'long-type');
            switch(type) {
                case 'Weapon':
                    if(charaData.rh == '') {
                        removeInventoryItem(index);
                        charaData.rh = target;
                    } else if(charaData.lh == '') {
                        removeInventoryItem(index);
                        charaData.lh = target;
                    } else {
                        removeInventoryItem(index);
                        addInventoryItem(charaData.rh);
                        charaData.rh = target;
                    }
                    break;
                case 'Armor':
                    if(charaData.torso == '') {
                        removeInventoryItem(index);
                        charaData.torso = target;
                    } else {
                        removeInventoryItem(index);
                        addInventoryItem(charaData.torso);
                        charaData.torso = target;
                    }
                    break;
                case 'Accessory':
                    switch(longtype.toLowerCase()) {
                        case 'head':
                        case 'helmet':
                        case 'hat':
                        case 'cap':
                            if(charaData.head == '') {
                                removeInventoryItem(index);
                                charaData.head = target;
                            } else {
                                removeInventoryItem(index);
                                addInventoryItem(charaData.head);
                                charaData.head = target;
                            }
                            break;
                        case 'neck':
                        case 'amulet':
                        case 'pendant':
                        case 'necklace':
                            if(charaData.neck == '') {
                                removeInventoryItem(index);
                                charaData.neck = target;
                            } else {
                                removeInventoryItem(index);
                                addInventoryItem(charaData.neck);
                                charaData.neck = target;
                            }
                            break;
                        case 'legs':
                        case 'leggings':
                        case 'pants':
                        case 'slops':
                            if(charaData.legs == '') {
                                removeInventoryItem(index);
                                charaData.legs = target;
                            } else {
                                removeInventoryItem(index);
                                addInventoryItem(charaData.legs);
                                charaData.legs = target;
                            }
                            break;
                        case 'waist':
                        case 'belt':
                            if(charaData.waist == '') {
                                removeInventoryItem(index);
                                charaData.waist = target;
                            } else {
                                removeInventoryItem(index);
                                addInventoryItem(charaData.waist);
                                charaData.waist = target;
                            }
                            break;
                        case 'feet':
                        case 'shoes':
                        case 'boots':
                            if(charaData.feet == '') {
                                removeInventoryItem(index);
                                charaData.feet = target;
                            } else {
                                removeInventoryItem(index);
                                addInventoryItem(charaData.feet);
                                charaData.feet = target;
                            }
                            break;
                        case 'hands':
                        case 'gloves':
                            if(charaData.hands == '') {
                                removeInventoryItem(index);
                                charaData.hands = target;
                            } else {
                                removeInventoryItem(index);
                                addInventoryItem(charaData.hands);
                                charaData.hands = target;
                            }
                            break;
                        case 'ring':
                        case 'finger':
                            if(charaData.ring1 == '') {
                                removeInventoryItem(index);
                                charaData.ring1 = target;
                            } else if(charaData.ring2 == '') {
                                removeInventoryItem(index);
                                charaData.ring2 = target;
                            } else if(charaData.ring3 == '') {
                                removeInventoryItem(index);
                                charaData.ring3 = target;
                            } else if(charaData.ring4 == '') {
                                removeInventoryItem(index);
                                charaData.ring4 = target;
                            } else if(charaData.ring5 == '') {
                                removeInventoryItem(index);
                                charaData.ring5 = target;
                            } else if(charaData.ring6 == '') {
                                removeInventoryItem(index);
                                charaData.ring6 = target;
                            } else {
                                removeInventoryItem(index);
                                addInventoryItem(charaData.ring1);
                                charaData.ring1 = target;
                            }
                            break
                        default:
                            return;
                            break;
                    }
                    break;
                case 'Ammunition':
                    if(charaData.ammo == '') {
                        removeInventoryItem(index);
                        charaData.ammo = target;
                    } else {
                        removeInventoryItem(index);
                        addInventoryItem(charaData.ammo);
                        charaData.ammo = target;
                    }
                    break;
                default:
                    return;
                    break;
            }
            uploadInventory();
        }
    }
    
    function unequipItem(slot) {
        switch(slot) {
            case 'ammo':
                addInventoryItem(charaData.ammo);
                charaData.ammo = '';
                break;
            case 'head':
                addInventoryItem(charaData.head);
                charaData.head = '';
                break;
            case 'neck':
                addInventoryItem(charaData.neck);
                charaData.neck = '';
                break;
            case 'torso':
                addInventoryItem(charaData.torso);
                charaData.torso = '';
                break;
            case 'legs':
                addInventoryItem(charaData.legs);
                charaData.legs = '';
                break;
            case 'feet':
                addInventoryItem(charaData.feet);
                charaData.feet = '';
                break;
            case 'waist':
                addInventoryItem(charaData.waist);
                charaData.waist = '';
                break;
            case 'hands':
                addInventoryItem(charaData.hands);
                charaData.hands = '';
                break;
            case 'lh':
                addInventoryItem(charaData.lh);
                charaData.lh = '';
                break;
            case 'rh':
                addInventoryItem(charaData.rh);
                charaData.rh = '';
                break;
            case 'ring1':
                addInventoryItem(charaData.ring1);
                charaData.ring1 = '';
                break;
            case 'ring2':
                addInventoryItem(charaData.ring2);
                charaData.ring2 = '';
                break;
            case 'ring3':
                addInventoryItem(charaData.ring3);
                charaData.ring3 = '';
                break;
            case 'ring4':
                addInventoryItem(charaData.ring4);
                charaData.ring4 = '';
                break;
            case 'ring5':
                addInventoryItem(charaData.ring5);
                charaData.ring5 = '';
                break;
            case 'ring6':
                addInventoryItem(charaData.ring6);
                charaData.ring6 = '';
                break;
            default:
                return;
                break;
        }
        uploadInventory();
    }
    
    function removeTrait(index) {
        traitList.splice(index, 1);
        editPage();
    }
    
    function addTrait(index) {
        if(index >= 0) {
            var newTrait = '';
            for(var i = 0; i < charaOptions.length; ++i) {
                if(charaOptions[i].id == index) {
                    newTrait += '[net-id]' + charaOptions[i].id + '[/net-id]';
                    newTrait += '[title]' + charaOptions[i].title + '[/title]';
                    newTrait += '[description]' + charaOptions[i].description + '[/description]';
                    newTrait += '[active]1[/active]';
                    newTrait += '[type]' + readTag(charaOptions[i].properties, "type") + '[/type]';
                    break;
                }
            }
            traitList.splice(traitList.length, 0, newTrait);
            editPage();
        }
    }
    
    function removeTalent(index) {
        talentList.splice(index, 1);
        editPage();
    }
    
    function addTalent(index) {
        if(index >= 0) {
            var newTalent = '';
            for(var i = 0; i < charaOptions.length; ++i) {
                if(charaOptions[i].id == index) {
                    newTalent += '[net-id]' + charaOptions[i].id + '[/net-id]';
                    newTalent += '[title]' + charaOptions[i].title + '[/title]';
                    newTalent += '[description]' + charaOptions[i].description + '[/description]';
                    newTalent += '[rank]0[/rank]';
                    newTalent += '[type]' + readTag(charaOptions[i].properties, "type") + '[/type]';
                    break;
                }
            }
            talentList.splice(talentList.length, 0, newTalent);
            editPage();
        }
    }
    
    function removeSchool(index) {
        schoolList.splice(index, 1);
        editPage();
    }
    
    function addSchool(index) {
        if(index >= 0) {
            var newSchool = '';
            for(var i = 0; i < charaOptions.length; ++i) {
                if(charaOptions[i].id == index) {
                    newSchool += '[net-id]' + charaOptions[i].id + '[/net-id]';
                    newSchool += '[title]' + charaOptions[i].title + '[/title]';
                    newSchool += '[description]' + charaOptions[i].description + '[/description]';
                    newSchool += '[level]0[/level]';
                    newSchool += '[special]0[/special]';
                    break;
                }
            }
            schoolList.splice(schoolList.length, 0, newSchool);
            editPage();
        }
    }
    
    function removeVocation(index) {
        vocationList.splice(index, 1);
        editPage();
    }
    
    function addVocation(voc) {
        vocationList.splice(vocationList.length, 0, voc);
        editPage();
    }
    
    function removeSpecializedAbility(index) {
        specializedAbilityList.splice(index, 1);
        editPage();
    }
    
    function addSpecializedAbility(index) {
        if(index >= 0) {
            var newAbility = '';
            for(var i = 0; i < charaOptions.length; ++i) {
                if(charaOptions[i].id == index) {
                    newAbility += '[net-id]' + charaOptions[i].id + '[/net-id]';
                    newAbility += '[title]' + charaOptions[i].title + '[/title]';
                    newAbility += '[description]' + charaOptions[i].description + '[/description]';
                    newAbility += '[type]' + readTag(charaOptions[i].properties, 'type') + '[/type]';
                    newAbility += charaOptions[i].properties;
                    break;
                }
            }
            specializedAbilityList.splice(specializedAbilityList.length, 0, newAbility);
            editPage();
        }
    }
    
    function removeAuxiliaryAbility(index) {
        auxiliaryAbilityList.splice(index, 1);
        editPage();
    }
    
    function addAuxiliaryAbility(index) {
        if(index >= 0) {
            var newAbility = '';
            for(var i = 0; i < charaOptions.length; ++i) {
                if(charaOptions[i].id == index) {
                    newAbility += '[net-id]' + charaOptions[i].id + '[/net-id]';
                    newAbility += '[title]' + charaOptions[i].title + '[/title]';
                    newAbility += '[description]' + charaOptions[i].description + '[/description]';
                    newAbility += '[type]' + charaOptions[i].type + '[/type]';
                    newAbility += charaOptions[i].properties;
                    break;
                }
            }
            auxiliaryAbilityList.splice(auxiliaryAbilityList.length, 0, newAbility);
            editPage();
        }
    }
    
    function removeInventoryItem(index) {
        itemList.splice(index, 1);
        editPage();
    }
    
    function addInventoryItem() {
        addInventoryItem('[net-id]-1[/net-id][title][/title][btitle][/btitle][type]0[/type][long-type][/long-type][mod-prof][/mod-prof][weight]1[/weight][value]0[/value][dmg][/dmg][mdmg][/mdmg][pow]0[/pow][mpow]0[/mpow]' +
            '[rng]0[/rng][mrng]0[/mrng][def]0[/def][mdef]0[/mdef][unit-s][/unit-s][unit-p][/unit-p][description][/description][bdescription][/bdescription][mods][/mods][stackable]0[/stackable][bg-src][/bg-src]' +
            '[icon-src][/icon-src][bg-color]0[/bg-color][icon-color]0[/icon-color][equipped]0[/equipped][starred]0[/starred][hidden]0[/hidden][whitelist]' + user + '[/whitelist]');
    }
    
    function addInventoryItem(itemString) {
        itemList.splice(itemList.length, 0, itemString);
        editPage();
    }
    
    
    // ========================================================================
    // Begin Main Code ========================================================
    var charaData = retrieveData();
    var charaOptions = retrieveOptions();
    var user = retrieveUser();
    
    var traitList = readTagMulti(charaData.traits, "trait");
    var talentList = readTagMulti(charaData.talents, "talent");
    var schoolList = readTagMulti(charaData.schools, "school");
    var vocationList = readTagMulti(charaData.vocations, "vocation");
    var specializedAbilityList = readTagMulti(charaData.abilities, "ability");
    var auxiliaryAbilityList = readTagMulti(charaData.aux_abilities, "ability");
    var itemList = readTagMulti(charaData.inventory, "item");
    var walletList = charaData.wallet;
    <?php
        if(isset($_GET['page'])) {
            switch($_GET['page']) {
                case 'basics':
                    echo "showBasics();";
                    break;
                case 'proficiencies':
                    echo "showProficiencies();";
                    break;
                case "inventory":
                    echo "showInventory();";
                    break;
                case "tt":
                    echo "showTraitsTalents();";
                    break;
                case "schools":
                    echo "showSchools();";
                    break;
                case "notes":
                    echo "showNotes();";
                    break;
                default:
                    echo "showBasics();";
                    break;
            }
        } else {
            echo "showBasics();";
        }
    ?>
</script>
