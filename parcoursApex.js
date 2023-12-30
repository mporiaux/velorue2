var tabdur = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
var tabelev = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
var tabdist = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
var postab = 0;
var deltadist=0;
var dist=0;
var deltah =0;
var oldelev = 0.0;
var oldlat = 0.0, oldlong = 0.0;
var totdist=0.0;
var prem = true;
var date=new Date();
var p=0;
var deniv = 0;
var moydur=0;
var nbrMesuresPente=5;
var rot = 0;
var vreelle = 0.0;
var d= 0;

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
   lectureInfos();
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
    await timer(150);
  } while (true);
}

function avancer() {
  vreelle = Math.sqrt(parseFloat(vreelle) / 3.6 * parseFloat(vreelle) / 3.6 - 20 * deltah) * 3.6 - parseFloat(vreelle) * 0.20;
  //cette formule tient compte du frottement proportionnel à la vitesse
  if (isNaN(vreelle))  vreelle = vit;
  if (vreelle > 30)   vreelle = 30;
  if (vreelle < parseFloat(vit)) vreelle = parseFloat(vit);//on ne peut pas descendre sous la vitesse réelle
}



function rotate() {
  rotation();
  pano.setPov({
    heading: rot,
    pitch: 0,
  });
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

        if(prem===true){
          prem=false;
          for(let i =0;i<nbrMesuresPente;i++){
            tabelev[i]=results[0].elevation;
            oldelev=tabelev[0];
          }
        }

        tabelev[postab]=results[0].elevation;
        var denivact=tabelev[postab]-oldelev;
        oldelev=tabelev[postab];
        tabdist[postab]=totdist;
        deltah = tabelev[postab]-tabelev[(postab+1)%nbrMesuresPente];
        deltadist = tabdist[postab]-tabdist[(postab+1)%nbrMesuresPente];
        if (denivact > 0) deniv += denivact;
        var dh = Math.sqrt(deltadist*deltadist+ deltah * deltah);
        if (dh !== 0) p = deltah / dh * 100;
        else p=0;
        console.log("distance = "+deltadist+"-deltah = "+deltah+"-pente = "+p);
        let apente = tabdur[postab];
        tabdur[postab] = p;
        moydur=moydur-apente+p;
        dur = moydur / nbrMesuresPente;
        dur = Math.round(dur*10)/10;
        postab = (postab + 1) % nbrMesuresPente;
        $("#draggable-elevation").html("<h1>&nbsp;" + dur + "&nbsp;</h1>");
        if (dur < 0)
          el.style.backgroundColor = "GREEN";
        else
          el.style.backgroundColor = "RED";
        adur = dur;
        if(simul===false) setPente();

      } else {
        console.log('No results found');
      }
    } else {
      console.log('Elevation service failed due to: ' + status);
    }
  });
}

async function go() {
  date.setTime(date.getTime() + (100 * 24 * 60 * 60 * 1000));
  lat = parseFloat(document.getElementById("lat").value);
  long = parseFloat(document.getElementById("long").value);

  initialize();
  //debut positionnement
  if(kml===true)   affTrack();
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
  carte.style.top = '600px';
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
