<?php
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

        #draggable-map { width: 250px; height: 250px; }
        #draggable-infos{ width: 15%; height: 20%; }
        #draggable-elevation { width: 15%; height: 20%; }

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
<button id="bt1">CONNECT TACX</button><button id="godir">CONNECT ARDUINO</button>
<div>
    Latitude = <input type="text" id="lat" value="<?= $lat ?>"> Longitude = <input type = "text" id="long" value="<?= $long ?>"><br>
</div>
<button onclick="go()" id="gobt">GO</button><br>
nom du point: <input type ="text"  id="nomreset"><button onclick="reset()">RESET</button><br>
nom du point: <input type ="text"  id="nomsave"><button onclick="save()">SAVE</button>
<div id="draggable-infos" class="ui-widget-content"></div>
<div id="draggable-map" class="circleBase type1 ui-widget-content"></div>
<div id="draggable-elevation"></div>
<div id="street-view"></div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
       lecture();
      }, false);
    var file;
    var pano;
    var rot = 0;
    var long = 4.3983;
    var lat = 44.40821;
    var vit = 0.0;
    var vreelle = 0.0;
    var dist;
    var p = 0;
    var oldlat = 0.0, oldlong = 0.0;
    var oldelev = 0.0;
    var deltah=0;
    var dur=0;
    var tabdur = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    var postab = 0;
    var inf = document.getElementById('draggable-infos');
    var el = document.getElementById('draggable-elevation');
    var carte = document.getElementById('draggable-map');
    var totdist = 0;
    var prem = true;
    var date = new Date();
    var deniv = 0
    var moydur=0;
    var infosdep;
    var nbrMesuresPente = 5;
    var d = 0;
    var chgtok = false;
    var pegman_position;
    var ctaLayer;
    var map;
    var nomreset = document.getElementById("nomreset");
    var nomsave = document.getElementById("nomsave");
    var filedata;
    var btgo = document.getElementById("gobt");
    var myService = null;
    var vitesse= document.getElementById("vitesse");
    var bt1 = document.getElementById("bt1");
    var myServiceDir = null;
    var dirCaracteric;
    var btgodir = document.getElementById("godir");
    var direction=0;
    var vitesseCharacteristic;
    var slopeCharacteristic;

    bt1.addEventListener('pointerup', function(event) {
        connect();
    });

    btgodir.addEventListener('pointerup', function(event) {
        connectDir();
    });


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
         do {
             dist += vit/3.6;
             if(vreelle<vit) vreelle=vit;
             else avancer();

             $("#draggable-infos").html("<h2>vitesse = " + parseFloat(vreelle).toFixed(1) + " km/h<br>distance = " + totdist.toFixed(0) + " m<br>deniv = " + deniv.toFixed(0) + "m</h2>");
             switch (direction){
                 case 0 : break;
                 case 1 : rot-=3;rotate();break;
                 case 2 : rot+=3;rotate();break;
                 case 3 : vreelle = parseFloat(vreelle) - 5;
                     if (vreelle < parseFloat(vit)) vreelle = parseFloat(vit);
             }
            await timer(100);
        } while (true);
    }

 function avancer() {
        vreelle = Math.sqrt(parseFloat(vreelle) / 3.6 * parseFloat(vreelle) / 3.6 - 20 * deltah) * 3.6 - parseFloat(vreelle) * 0.20;
        //cette formule tient compte du frottement proportionnel à la vitesse
        if (isNaN(vreelle))  vreelle = vit;
        if (vreelle > 30)   vreelle = 30;
        if (vreelle < parseFloat(vit)) vreelle = parseFloat(vit);//on ne peut pas descendre sous la vitesse réelle
    }


    async   function initialize() {
        lat = parseFloat(document.getElementById("lat").value);
        long= parseFloat(document.getElementById("long").value);
        pegman_position = {lat: lat, lng: long};
        map = new google.maps.Map(document.getElementById('draggable-map'), {
            center: pegman_position,
            zoom: 15});

        ctaLayer = new google.maps.KmlLayer(null);
        ctaLayer.setMap(null);
//le code suivant est à réactiver en déclarant src et le mettant en relation avec un fichier kml
     /*   ctaLayer = new google.maps.KmlLayer(src, {
            suppressInfoWindows: true,
            preserveViewport: false,
            map: map
        });*/

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
        let curr;
        if (parseFloat(vreelle) === 0.0) {
            return;
            console.log(" viterre nulle => pas moveforward");
        }

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

            document.getElementById("lat").value = lat;
            document.getElementById("long").value =long;

          const elevator = new google.maps.ElevationService;
          const R = 6371000.0;
          const oldlatr = oldlat * Math.PI / 180;
          const oldlongr = oldlong * Math.PI / 180;
          const latr = lat * Math.PI / 180;
          const longr = long * Math.PI / 180;

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
            console.log("erreur dans la fonction moveforward "+err);
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

                    if (deltah > 0 && deltah < 20) deniv += deltah;
                    const dh = Math.sqrt(d * d + deltah * deltah);
                    if (dh !== 0) p = deltah / dh * 100;
                    if (Math.abs(p) < 20) {
                        let apente = tabdur[postab];
                        tabdur[postab] = p;
                        moydur=moydur-apente+p;
                        postab = (postab + 1) % nbrMesuresPente;
                        dur = moydur / nbrMesuresPente;
                        dur = Math.round(dur*10)/10;
                       $("#draggable-elevation").html("<h1>&nbsp;" + dur + "&nbsp;</h1>");
                        if (dur < 0)
                            el.style.backgroundColor = "GREEN";
                        else
                            el.style.backgroundColor = "RED";
                        adur = dur;
                        setPente();
                    }
                } else {
                    console.log('No results found');
                }
            } else {
                console.log('Elevation service failed due to: ' + status);
            }
        });
    }

    function getCookie(cname) {
      const name = cname + "=";
      const decodedCookie = decodeURIComponent(document.cookie);
      const ca = decodedCookie.split(';');
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

/*
    function getCookie(name) {
        var value = "; " + document.cookie;
        var parts = value.split("; " + name + "=");
        if (parts.length === 2)  return parts.pop().split(";").shift();
        else  return "";
    }
   */

    function reset() {
        lat = getCookie(nomreset.value+"latitude");
        if (isNaN(lat))  lat = 0;
        long = getCookie(nomreset.value+"longitude");
        if (isNaN(long)) long = 0;
        infosdep = document.getElementById("infosdep");
        infosdep.setAttribute("value", lat + "," + long);
        document.getElementById("lat").value=lat;
        document.getElementById("long").value=long;
    }

    function save() {
        var nomold=getCookie(nomsave.value+"latitude");
        if(nomold===""){
            document.cookie = nomsave.value+"latitude=" + lat + "; Max-Age=2592000";
            document.cookie = nomsave.value+"longitude=" + long + "; Max-Age=2592000";
            alert("sauvegarde de :"+document.cookie);
        }
        else alert("nom déjà utilisé");
    }

    async function go() {
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
        let ncolor=0;
        const  colors=["blue","green"];
        //fin positionnement
        do {
            try {
                btgo.style.background=colors[ncolor];
                ncolor=(ncolor+1)%2;
                var tattente = 0.0;
                if (parseFloat(vreelle) > 5.0) {
                    tattente = (d * 3600) / (parseFloat(vreelle)) ;
                    moveForward();
                    await timer(tattente);
                } else {
                    await timer(500);
                    console.log("attente de départ , vreelle = "+vreelle);
                }
            } catch (err) {
                alert(err);
            }
        } while (true);
    }

   /*-------------------------------------------------------------------------------------------------------------------------*/
    const definitions = {
            windSpeed:      {resolution: 0.001,  unit: 'mps',  size: 2, min: -35.56, max: 35.56,  default: 2}, // 0,
            grade:          {resolution: 0.01,   unit: '%',    size: 2, min: -40,    max: 40,     default: 0},
            crr:            {resolution: 0.0001, unit: '',     size: 1, min: 0,      max: 0.0254, default: 0.004},
            windResistance: {resolution: 0.01,   unit: 'kg/m', size: 1, min: 0,      max: 1.86,   default: 0.51},
        };
        const opCode = 0x11;

        const flags = {
            InstantaneousSpeed:    { flagBit:  0, present: 0 },
            MoreData:              { flagBit:  0, present: 0 },
            InstantaneousCandence: { flagBit:  2, present: 1 }, // bit 1, present 0
            AverageSpeed:          { flagBit:  1, present: 1 }, // bit 2, present 1
            AverageCandence:       { flagBit:  3, present: 1 },
            TotalDistance:         { flagBit:  4, present: 1 },
            ResistanceLevel:       { flagBit:  5, present: 1 },
            InstantaneousPower:    { flagBit:  6, present: 1 },
            AveragePower:          { flagBit:  7, present: 1 },
            ExpendedEnergy:        { flagBit:  8, present: 1 },
            HeartRate:             { flagBit:  9, present: 1 },
            MetabolicEquivalent:   { flagBit: 10, present: 1 },
            ElapsedTime:           { flagBit: 11, present: 1 },
            RemainingTime:         { flagBit: 12, present: 1 }
            // Reserved:              { flagBit: 13-15, present: null }
        };
        const fields = {
            Flags:                 { type: 'Uint16', size: 2, resolution: 1,
                unit: 'bit'     },
            InstantaneousSpeed:    { type: 'Uint16', size: 2, resolution: 0.01,
                unit: 'kph'     },
            AverageSpeed:          { type: 'Uint16', size: 2, resolution: 0.01,
                unit: 'kph'     },
            InstantaneousCandence: { type: 'Uint16', size: 2, resolution: 0.5,
                unit: 'rpm'     },
            AverageCandence:       { type: 'Uint16', size: 2, resolution: 0.5,
                unit: 'rpm'     },
            TotalDistance:         { type: 'Uint24', size: 3, resolution: 1,
                unit: 'm'       },
            ResistanceLevel:       { type: 'Uint16', size: 2, resolution: 1,
                unit: 'unitless'},
            InstantaneousPower:    { type: 'Uint16', size: 2, resolution: 1,
                unit: 'W'       },
            AveragePower:          { type: 'Uint16', size: 2, resolution: 1,
                unit: 'W'       },
            TotalEnergy:           { type: 'Int16',  size: 2, resolution: 1,
                unit: 'kcal'    },
            EnergyPerHour:         { type: 'Int16',  size: 2, resolution: 1, unit: 'kcal'    },
            EnergyPerMinute:       { type: 'Uint8',  size: 1, resolution: 1,
                unit: 'kcal'    },
            HeartRate:             { type: 'Uint16', size: 2, resolution: 1,
                unit: 'bpm'     },
            MetabolicEquivalent:   { type: 'Uint8',  size: 1, resolution: 1,
                unit: 'me'      },
            ElapsedTime:           { type: 'Uint16', size: 2, resolution: 1,
                unit: 's'       },
            RemainingTime:         { type: 'Uint16', size: 2, resolution: 1,
                unit: 's'       }
        };

        async function connect() {
          if ('bluetooth' in navigator) {
            navigator.bluetooth.requestDevice({filters: [{services: ['00001826-0000-1000-8000-00805f9b34fb']}]})
              .then(device => {
                   return device.gatt.connect();
              })
              .then(server => {
                myService = server.getPrimaryService('00001826-0000-1000-8000-00805f9b34fb');
                return myService;
              })
              .then(service => {
                vitesseCharacteristic = service.getCharacteristic('00002ad2-0000-1000-8000-00805f9b34fb');
                alert("récupération de la caractéristique vitesse ");
                return vitesseCharacteristic;
              })
              .then(characteristic  => {
               return  characteristic.startNotifications();
              })
              .then(characteristic => {
                   characteristic.addEventListener('characteristicvaluechanged', event => {
                  let value = event.target.value;
                  vit = readSpeed(new DataView(value.buffer));
               });
                  return myService;
              })
              .then(service => {
                  return service.getCharacteristic('00002ad9-0000-1000-8000-00805f9b34fb');
              })
              .then(characteristic=> {
                slopeCharacteristic =characteristic;
                alert("caractéristique pente définie");
                return characteristic.startNotifications();
              })
              .then(characteristic => {
                      characteristic.addEventListener('characteristicvaluechanged', event => {
                      let value = event.target.value;
                });
                return characteristic;
              })
              .then(characteristic => {
                let data = RequestControl();
                characteristic.writeValueWithResponse(data);
                console.log("request control exécuté");
              })
              .catch(error => {
                console.error('Erreur :', error);
              });
          }
        }

      function encode(grade) {
            const buffer = new ArrayBuffer(7);
            const view   = new DataView(buffer);
            view.setUint8(0, opCode, true);
            view.setInt16(1, 2000, true);
            view.setInt16(3, grade, true);
            view.setUint8(5, 40, true);
            view.setUint8(6, 51, true);
            return view.buffer;
        }

        async function setPente() {
                let slopeValue = dur;
                let data = encode(Math.floor(slopeValue*100));
                var view1 = new DataView(data);
                console.log('appel de writeValueWithResponse avec pour valeur : '+view1);
                return  slopeCharacteristic.writeValueWithResponse(data);
        }
        function readSpeed(dataview) {
            const flags = dataview.getUint16(0, true);
            const speed = dataview.getUint16(speedIndex(flags), true);
            return (speed * fields.InstantaneousSpeed.resolution);
        }

        function speedIndex(flags) {
            let i = fields.Flags.size;
            return i;
        }

        function RequestControl() {
            const opCode = 0x00;
            const length = 1;
            const buffer = new ArrayBuffer(length);
            const view   = new DataView(buffer);
            view.setUint8(0, opCode);
            return view.buffer;
        }





    async function connectDir(){
        if(myServiceDir === null) {
            // Vérifier la compatibilité avec le Bluetooth
            if ('bluetooth' in navigator) {
                navigator.bluetooth.requestDevice({filters: [{services: ['0000ffe0-0000-1000-8000-00805f9b34fb']}]})
                    .then(device => {
                        // Connexion à l'appareil Bluetooth sélectionné
                        return device.gatt.connect();
                    })
                    .then(server => {
                        myService = server.getPrimaryService('0000ffe0-0000-1000-8000-00805f9b34fb');
                        // Récupération du service GATT

                        return myService;
                    })
                    .then(service => {
                        // Activation des notifications pour la caractéristique souhaitée
                        return service.getCharacteristic('0000ffe1-0000-1000-8000-00805f9b34fb');
                    })
                    .then(characteristic => {
                        // Activation des notifications
                        alert("récupération de la caractéristique de direction et activation des notifications");
                        return characteristic.startNotifications();
                    })
                    .then(characteristic => {
                        // Écoute des changements de valeur de la caractéristique

                        characteristic.addEventListener('characteristicvaluechanged', event => {
                            // Lecture de la valeur mise à jour
                            let value = event.target.value;

                            let dv = new DataView(value.buffer);
                            const etat = dv.getUint8(0, true);
                            console.log("état ="+etat);
                            direction=etat-48;
                        });
                    })
                    .catch(error => {
                        console.error('Erreur :', error);
                    });
            } else {
                console.log('Le Bluetooth n\'est pas supporté par ce navigateur.');
            }
        }
        else return myService;
    }

</script>
<script async defer
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDPLZsTh4cya1n_CshNgzsH4OmKDLWK8xQ&callback=initialize">
</script>

</body>
</html>
