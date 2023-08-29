<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * My Moodle -- a user's personal dashboard
 *
 * - each user can currently have their own page (cloned from system and then customised)
 * - only the user can see their own dashboard
 * - users can add any blocks they want
 * - the administrators can define a default site dashboard for users who have
 *   not created their own dashboard
 *
 * This script implements the user's view of the dashboard, and allows editing
 * of the dashboard.
 *
 * @package    moodlecore
 * @subpackage my
 * @copyright  2010 Remote-Learner.net
 * @author     Hubert Chathi <hubert@remote-learner.net>
 * @author     Olav Jordan <olav.jordan@remote-learner.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../config.php');
require_once($CFG->dirroot . '/my/lib.php');

redirect_if_major_upgrade_required();

// TODO Add sesskey check to edit
$edit   = optional_param('edit', null, PARAM_BOOL);    // Turn editing on and off
$reset  = optional_param('reset', null, PARAM_BOOL);

require_login();

$hassiteconfig = has_capability('moodle/site:config', context_system::instance());
if ($hassiteconfig && moodle_needs_upgrading()) {
    redirect(new moodle_url('/admin/index.php'));
}

$strmymoodle = get_string('myhome');

if (isguestuser()) {  // Force them to see system default, no editing allowed
    // If guests are not allowed my moodle, send them to front page.
    if (empty($CFG->allowguestmymoodle)) {
        redirect(new moodle_url('/', array('redirect' => 0)));
    }

    $userid = null;
    $USER->editing = $edit = 0;  // Just in case
    $context = context_system::instance();
    $PAGE->set_blocks_editing_capability('moodle/my:configsyspages');  // unlikely :)
    $header = "$SITE->shortname: $strmymoodle (GUEST)";
    $pagetitle = $header;

} else {        // We are trying to view or edit our own My Moodle page
    $userid = $USER->id;  // Owner of the page
    $context = context_user::instance($USER->id);
    $PAGE->set_blocks_editing_capability('moodle/my:manageblocks');
    $header = fullname($USER);
    $pagetitle = $strmymoodle;
}

// Get the My Moodle page info.  Should always return something unless the database is broken.
if (!$currentpage = my_get_page($userid, MY_PAGE_PRIVATE)) {
    print_error('mymoodlesetup');
}

// Start setting up the page
$params = array();
$PAGE->set_context($context);
$PAGE->set_url('/my/index.php', $params);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('my-index');
$PAGE->blocks->add_region('content');
$PAGE->set_subpage($currentpage->id);
$PAGE->set_title($pagetitle);
$PAGE->set_heading($header);

if (!isguestuser()) {   // Skip default home page for guests
    if (get_home_page() != HOMEPAGE_MY) {
        if (optional_param('setdefaulthome', false, PARAM_BOOL)) {
            set_user_preference('user_home_page_preference', HOMEPAGE_MY);
        } else if (!empty($CFG->defaulthomepage) && $CFG->defaulthomepage == HOMEPAGE_USER) {
            $frontpagenode = $PAGE->settingsnav->add(get_string('frontpagesettings'), null, navigation_node::TYPE_SETTING, null);
            $frontpagenode->force_open();
            $frontpagenode->add(get_string('makethismyhome'), new moodle_url('/my/', array('setdefaulthome' => true)),
                    navigation_node::TYPE_SETTING);
        }
    }
}

// Toggle the editing state and switches
if (empty($CFG->forcedefaultmymoodle) && $PAGE->user_allowed_editing()) {
    if ($reset !== null) {
        if (!is_null($userid)) {
            require_sesskey();
            if (!$currentpage = my_reset_page($userid, MY_PAGE_PRIVATE)) {
                print_error('reseterror', 'my');
            }
          //  redirect(new moodle_url('/my'));
        }
    } else if ($edit !== null) {             // Editing state was specified
        $USER->editing = $edit;       // Change editing state
    } else {                          // Editing state is in session
        if ($currentpage->userid) {   // It's a page we can edit, so load from session
            if (!empty($USER->editing)) {
                $edit = 1;
            } else {
                $edit = 0;
            }
        } else {
            // For the page to display properly with the user context header the page blocks need to
            // be copied over to the user context.
            if (!$currentpage = my_copy_page($USER->id, MY_PAGE_PRIVATE)) {
                print_error('mymoodlesetup');
            }
            $context = context_user::instance($USER->id);
            $PAGE->set_context($context);
            $PAGE->set_subpage($currentpage->id);
            // It's a system page and they are not allowed to edit system pages
            $USER->editing = $edit = 0;          // Disable editing completely, just to be safe
        }
    }

    // Add button for editing page
    $params = array('edit' => !$edit);

    $resetbutton = '';
    $resetstring = get_string('resetpage', 'my');
    $reseturl = new moodle_url("$CFG->wwwroot/my/index.php", array('edit' => 1, 'reset' => 1));

    if (!$currentpage->userid) {
        // viewing a system page -- let the user customise it
        $editstring = get_string('updatemymoodleon');
        $params['edit'] = 1;
    } else if (empty($edit)) {
        $editstring = get_string('updatemymoodleon');
    } else {
        $editstring = get_string('updatemymoodleoff');
        $resetbutton = $OUTPUT->single_button($reseturl, $resetstring);
    }

    $url = new moodle_url("$CFG->wwwroot/my/index.php", $params);
    $button = $OUTPUT->single_button($url, $editstring);
    $PAGE->set_button($resetbutton . $button);

} else {
    $USER->editing = $edit = 0;
}

//echo $OUTPUT->header();

if (core_userfeedback::should_display_reminder()) {
    core_userfeedback::print_reminder_block();
}

//echo $OUTPUT->custom_block_region('content');

//echo $OUTPUT->footer();

// Trigger dashboard has been viewed event.
//$eventparams = array('context' => $context);
//$event = \core\event\dashboard_viewed::create($eventparams);
//$event->trigger();
$coord= $_POST["coord"];
$valeurs = explode(',',$coord);
$lat=$valeurs[0];
$long=$valeurs[1];

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"></meta>
<!--    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">-->
    <title>Street View</title>
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        #street-view {
            height: 100%;
        }

        #map {
            height: 100%;
        }

        #draggable-map { width: 500px; height: 500px; }
        #draggable-infos{ width: 15%; height: 20%; }
        #draggable-elevation { width: 15%; height: 10%; }

        .circleBase {
            border-radius: 50%;
        }

        .type1 {
            width: 500px;
            height: 500px;
            border: 1px solid blue;
        }




    </style>
    <link rel="shortcut icon" href="#" />
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script>
        $(function () {
            $("#draggable-map").draggable();
            $("#draggable-infos").draggable();
            $("#draggable-elevation").draggable();
        });
    </script>
</head>
<body>


<div id="draggable-map" class="circleBase type1 ui-widget-content"></div>
<div>
    Latitude = <input type="text" id="lat" value="<?= $lat ?>"> Longitude = <input type = "text" id="long" value="<?= $long ?>"><br>
    <br> fichier kml : <input type='file' accept=".kml,.kmz" id = "fileInput">
</div>


<button onclick="go()">GO</button><br>
nom du point: <input type ="text"  id="nomreset"><button onclick="reset()">RESET</button><br>
nom du point: <input type ="text"  id="nomsave"><button onclick="save()">SAVE</button>
<div id="draggable-infos" class="ui-widget-content"></div>
<div id="draggable-elevation"></div>
<div id="street-view"></div>


<script>

    var file;
    document.addEventListener('DOMContentLoaded', function () {

       // $.ajaxSetup({ cache: false });
       lecture();

    }, false);
    var pano;
    var url_string = window.location.href;
    var url = new URL(url_string);
    var rot = 0;
    var long = 4.3983;
    var lat = 44.40821;
    var vit = 0.0;
    var vreelle = 0.0;
    var dist;
    var droite;
    var gauche;
    var p = 0;


    var avance = false;
    var oldlat = 0.0, oldlong = 0.0;
    var oldelev = 0.0;



    var reader;
    var datas;
    var deltah;
    var dur;
    var tabdur = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    var postab = 0;
    var adur;
    var audio = new Audio('durete.mp3');
    var appelPente = 0;
    var inf = document.getElementById('draggable-infos');
    var el = document.getElementById('draggable-elevation');
    var carte = document.getElementById('draggable-map');
    var totdist = 0;
    var prem = true;
    var date = new Date();
    var deniv = 0;
    var nbrMesuresPente = 10;
    var d = 0;
    var chgtok = false;
    var pegman_position;
    var ctaLayer;
    var map;

    var nomreset = document.getElementById("nomreset");
    var nomsave = document.getElementById("nomsave");
    var filedata;
    var server=  document.getElementById('server');
    var fileInput = document.getElementById("fileInput");
    var kmlfile;




   function previewFile() {

        const [file] = document.querySelector("input[type=file]").files;
        const reader = new FileReader();

        reader.addEventListener(
            "load",
            () => {

                this.extractGoogleCoords(reader.result)

            },
            false
        );

        if (file) {

            reader.readAsText(file);
            this.kmlfile=file;
            uploadFile();
        }
    }



    fileInput.addEventListener("change", function() {
      previewFile();

    });

    function uploadFile() {

        var formData = new FormData();
        formData.append("file", this.kmlfile);
        var xhttp = new XMLHttpRequest();

        // Set POST method and ajax file path
        xhttp.open("POST", "upload.php", true);

        // call on request changes state
        xhttp.onreadystatechange = function () {
            if (this.readyState == 4 && this.status == 200) {

                var response = this.responseText;
                if (response == 1) {
                    alert("upload réussi");
                } else {
                    alert("échec de l'upload");
                }
            }
        };

        // Send request with data
        xhttp.send(formData);
    }
    async function extractGoogleCoords(plainText) {

        let parser = new DOMParser()
        let xmlDoc = parser.parseFromString(plainText, "text/xml")

        if (xmlDoc.documentElement.nodeName == "kml") {

            for (const item of xmlDoc.getElementsByTagName('Placemark')) {

                let markers = item.getElementsByTagName('Point')

                /** MARKER PARSE **/
                for (const marker of markers) {

                    var coords = marker.getElementsByTagName('coordinates')[0].childNodes[0].nodeValue.trim()

                    let coord = coords.split(",")
                    document.getElementById("lat").setAttribute("value",coord[1])
                    document.getElementById("long").setAttribute("value",coord[0])
                    return;
                }
            }
        } else {
            throw "error while parsing"
        }
    }
    function rotation() {

        var div = document.getElementById('draggable-map');
        var deg = (-rot) % 360;

        div.style.webkitTransform = 'rotate(' + deg + 'deg)';
        div.style.mozTransform = 'rotate(' + deg + 'deg)';
        div.style.msTransform = 'rotate(' + deg + 'deg)';
        div.style.oTransform = 'rotate(' + deg + 'deg)';
        div.style.transform = 'rotate(' + deg + 'deg)';
    }


    function timer(ms) {
        return new Promise(res => setTimeout(res, ms));
    }


    async  function lecture() {
        let n =0;
        do {
            n=(n+1)%10000;
            $.ajax({
                type: "GET",
                contentType: "application/json",
                url:"https://apex.oracle.com/pls/apex/bike/bike/infos",
                dataType: 'text',
                timeout: 100000}).
            done(function (data) {
                datas = data;
                console.log("SUCCESS: ", data);
                traitement();
            }).
            fail(function(jqXHR, textStatus, errorThrown) {
                    console.log("erreur de réception "+jqXHR.responseText+" "+errorThrown);
                }
            );
            await timer(1000);
        } while (true);
    }


    function traitement() {
        console.log('datas = '+datas);
        datas=datas.replace("{","");
        datas=datas.replace("}","");
        datas=datas.replaceAll("\"","");
        datas=datas.replace("infos:","");
        console.log('datas filtrees = '+datas);
        var arr = datas.split("_");
        vit = arr[0];
        dist = arr[1];
        gauche = arr[2];
        droite = arr[3];
        droite = droite.substring(0, 4);
        if(isNaN(vreelle))vreelle=vit;
        $("#draggable-infos").html("<h2>vitesse = " + parseFloat(vreelle).toFixed(0) + " km/h<br>distance = " + totdist.toFixed(0) + " m<br>deniv = " + deniv.toFixed(0) + "m</h2>");
        if (gauche === 'true' && droite === 'true') {

            vreelle = parseFloat(vreelle) - 5;
            if (vreelle < parseFloat(vit))
                vreelle = parseFloat(vit);
            return;
        }

        if (gauche === 'true')
            rot -= 3;

        if (droite === 'true')
            rot += 3;
        rotate();

    }



    async   function initialize() {

        lat = parseFloat(document.getElementById("lat").value);

        long= parseFloat(document.getElementById("long").value);
        pegman_position = {lat: lat, lng: long};
        console.log("latitude = " + lat);
        map = new google.maps.Map(document.getElementById('draggable-map'), {
            center: pegman_position,
            zoom: 15});

        ctaLayer = new google.maps.KmlLayer({

              map: map,
              url: "https://moodle.lestrade.be/velorue/"+this.kmlfile.name
          })


        pano = new google.maps.StreetViewPanorama(
            document.getElementById('street-view'),
            {
                position: pegman_position,
                pov: {heading: rot, pitch: 0},
                zoom: 1
            });
        pano.addListener('links_changed', function () {
            chgtok = true;
            map.setCenter(pegman_position);

        });


        map.setStreetView(pano);
    }

    function rotate() {
        rotation();
        pano.setPov({
            heading: rot,
            pitch: 0,

        });
    }




    function difference(link) {

        var diff = Math.abs(pano.pov.heading % 360 - link.heading);
        if (diff > 180)
            diff = Math.abs(360 - diff);
        return diff;
    }
    async  function moveForward() {
        var curr;
        if (parseFloat(vreelle) === 0.0)
            return;

        for (i = 0; i < pano.getLinks().length; i++) {

            var differ = difference(pano.links[i]);
            if (curr === undefined) {
                curr = pano.links[i];
            }

            if (difference(curr) > difference(pano.links[i])) {
                curr = pano.links[i];
            }
        }

        pano.setPano(curr.pano);

        try {
            while (chgtok === false) {
                await timer(100);
            }
            chgtok = false;
            lat = pano.getPosition().lat();
            long = pano.getPosition().lng();
            pegman_position = {lat: lat, lng: long};

            var elevator = new google.maps.ElevationService;
            var R = 6371000.0;
            var oldlatr = oldlat * Math.PI / 180;
            var oldlongr = oldlong * Math.PI / 180;
            var latr = lat * Math.PI / 180;
            var longr = long * Math.PI / 180;

            d = R * Math.acos(Math.cos(oldlatr) * Math.cos(latr) *
                Math.cos(longr - oldlongr) + Math.sin(oldlatr) *
                Math.sin(latr));
            if (prem === true)
                d = 10;
            if (isNaN(d))
                d = 10;
            totdist = parseFloat(totdist) + parseFloat(d);
            prem = false;
            oldlat = lat;
            oldlong = long;
            myDisplayLocationElevation(pano.getPosition(), elevator, d);
        } catch (err) {
        }
    }

    function myDisplayLocationElevation(location, elevator, d) {
        // Initiate the location request
        elevator.getElevationForLocations({
            'locations': [location]
        }, function (results, status) {
            if (status === 'OK') {
                // Retrieve the first result
                if (results[0]) {
                    // Open the infowindow indicating the elevation at the clicked position.
                    deltah = results[0].elevation - oldelev;
                    oldelev = results[0].elevation;

                    if (deltah > 0 && deltah < 20)
                        deniv += deltah;
                    var dh = Math.sqrt(d * d + deltah * deltah);
                    if (dh !== 0)
                        p = deltah / dh * 100;
                    //  $("#elevation").html(results[0].elevation + 'm '+d.toFixed(0)+" m "+p.toFixed(0)+"%");
                    dur = p / 2;

                    if (Math.abs(dur) < 20) {
                        tabdur[postab] = dur;
                        postab = (postab + 1) % nbrMesuresPente;
                        var index = 0;
                        var moydur = 0;
                        for (index = 0; index < nbrMesuresPente; index++) {
                            moydur += tabdur[index];
                        }
                        dur = moydur / nbrMesuresPente;
                        dur = Math.round(dur);
                        if (Math.abs(dur) < 1) dur = 1;
                        if (dur >= 1 && dur !== adur) {
                        try {
                            audio.play();
                        }catch (err){
                            console.log("erreur de fichier son : "+err);
                            }

                        }

                        //  $("#elevation").html(deltah.toFixed(1) + 'm ' + d.toFixed(0) + " m dureté = " + dur);
                        $("#draggable-elevation").html("<h1>&nbsp;&nbsp;&nbsp;&nbsp;" + dur + "&nbsp;&nbsp;"+Math.round(oldelev)+"</h1>");
                        if (dur < 0)
                            el.style.backgroundColor = "GREEN";
                        else
                            el.style.backgroundColor = "RED";
                        adur = dur;
                    }
                } else {
                    console.log('No results found');
                }
            } else {
                console.log('Elevation service failed due to: ' + status);
            }
        });
    }

    function listCookies() {
        var theCookies = document.cookie.split(';');
        var aString = '';
        for (var i = 1 ; i <= theCookies.length; i++) {
            aString += i + ' ' + theCookies[i-1] + "\n";
        }
        let ck = prompt("choisissez un numéro de lieu\n"+aString,"");

        return theCookies[ck-1];
    }

    function getCookie(cname) {

        var name = cname + "=";
        var decodedCookie = decodeURIComponent(document.cookie);
        var ca = decodedCookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == ' ') {
                c = c.substring(1);
            }
            if (c.indexOf(name) == 0) {
                return c.substring(name.length, c.length);
            }
        }
        return "";
    }
    function getCookie(name) {
        var value = "; " + document.cookie;
        var parts = value.split("; " + name + "=");
        if (parts.length === 2)
            return parts.pop().split(";").shift();
        else
            return "";
    }

    function reset() {
        let ck= listCookies();
        ck = ck.split("=")[1];
        let coord= ck.split(":");
        lat=coord[0];
        if (isNaN(lat))lat = 0;
        long = coord[1];
        if (isNaN(long))long = 0;
        document.getElementById("lat").value=lat;
        document.getElementById("long").value=long;

    }

    function save() {
        var nomold=getCookie(nomsave.value);
        if(nomold===""){
            document.cookie = nomsave.value+"=" + lat +":"+long+ "; Max-Age=2592000";
            alert("sauvegarde de :"+document.cookie);
        }
        else alert("nom déjà utilisé");
    }

    async function go() {
        initialize();
        date.setTime(date.getTime() + (100 * 24 * 60 * 60 * 1000));
        lat = parseFloat(document.getElementById("lat").value);
        long = parseFloat(document.getElementById("long").value);

        initialize();
        //debut positionnement

        inf.style.position = "absolute";
        inf.style.left = '800px';
        inf.style.top = '500px';
        inf.style.backgroundColor = "BLUE";
        inf.style.zIndex = "10";


        el.style.position = "absolute";
        el.style.left = '1000px';
        el.style.top = '500px';
        el.style.backgroundColor = "GREEN";
        el.style.zIndex = "10";

        carte.style.position = "absolute";
        carte.style.left = '1000px';
        carte.style.top = '800px';
        carte.style.zIndex = "10";

        //fin positionnement
        do {
            try {
                var tattente = 0.0;
                avancer();
                if (parseFloat(vreelle) > 0.1) {
                    tattente = (d * 3600) / (parseFloat(vreelle)) ;
                    moveForward();
                    await timer(tattente);
                } else
                    await timer(500);
            } catch (err) {
                alert(err);
            }
        } while (true);
    }


    function avancer() {
        vreelle = Math.sqrt(parseFloat(vreelle) / 3.6 * parseFloat(vreelle) / 3.6 - 20 * deltah) * 3.6 - parseFloat(vreelle) * 0.20;
        //cette formule tient compte du frottement proportionnel à la vitesse
        if (isNaN(vreelle))
            vreelle = vit;
        if (vreelle > 30)
            vreelle = 30;
        if (vreelle < parseFloat(vit))
            vreelle = parseFloat(vit);//on ne peut pas descendre sous la vitesse réelle
    }
</script>

<script async defer
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDPLZsTh4cya1n_CshNgzsH4OmKDLWK8xQ&callback=initialize">

</script>

</body>
</html>
