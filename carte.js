var mrk=[];
var point=[];
async   function initialize() {
  lat = parseFloat(document.getElementById("lat").value);
  long= parseFloat(document.getElementById("long").value);
  pegman_position = {lat: lat, lng: long};


  map = new google.maps.Map(document.getElementById('draggable-map'), {
    center: pegman_position,
    zoom: 15});
  if(kml===false) {
    ctaLayer = new google.maps.KmlLayer(null);
    ctaLayer.setMap(null);
  }

  google.maps.event.addListener(map, 'click', function(event) {
    // Get the coordinates of the click event
    var latclick = event.latLng.lat();
    latclick= latclick.toFixed(6);
    var lngclick = event.latLng.lng();
    lngclick= lngclick.toFixed(6);
    pegman_position = {lat: latclick, lng: lngclick};
    alert("changement de localisation activé, appuyer sur le bouton GO");
    document.getElementById("lat").value=latclick;
    document.getElementById("long").value=lngclick;
  });



  pano = new google.maps.StreetViewPanorama(
    document.getElementById('street-view'),
    {
      position: pegman_position,
      pov: {heading:0, pitch: dur*0},
      zoom: 1
    });
  pano.addListener('links_changed', function () {
    chgtok = true;
    map.setCenter(pegman_position);

  });
  map.setStreetView(pano);


// Ajout d'un marqueur à la vue Street View
  var marker = new google.maps.Marker({
    position: pegman_position,
    map: pano // 'panorama' est l'objet Street View
  });

// Ajout d'une infobulle au marqueur
  var infowindow = new google.maps.InfoWindow({
    content: 'Ceci est un endroit spécial.'
  });

  marker.addListener('click', function() {
    infowindow.open(pano);
  })
  let coordVirg= coordBlank[0].split(",");
  point[0]={
    lat: Number(coordVirg[0]),
    long: Number(coordVirg[1]),
    alt:Number(coordVirg[2])
  }
  console.log(Number(coordVirg[0])+"-"+Number(coordVirg[1])+"-"+Number(coordVirg[2])+"/");
  for(i=1;i<coordBlank.length;i++){
    let coordVirg= coordBlank[i].split(",");
    console.log(Number(coordVirg[0])+"-"+Number(coordVirg[1])+"-"+Number(coordVirg[2])+"/");
    point[i]={
      lat: Number(coordVirg[0]),
      long: Number(coordVirg[1]),
      alt:Number(coordVirg[2])
    }
   var penteParcours = (point[i].alt - point[i-1].alt)/distance(point[i-1],point[i])*100;
    var icon = 'bkmblue.png';
    if(penteParcours <0) icon = 'bkmGreen.png';
     else if(penteParcours < 2) icon = 'bkmBlue.png';
         else if(penteParcours <5) icon = 'bkmYellow.png';
            else if(penteParcours < 8) icon = 'bkmOrange.png';
              else  if(penteParcours <10) icon = 'bkmRed.png';
                else icon = 'bkmBlack.png';
    icon=   'https://moodle.lestrade.be/velorue/'+icon;

   mrk[i] = new google.maps.Marker({
      position: {lat: Number(coordVirg[1]), lng: Number(coordVirg[0])},
      map: pano, // 'pano' est l'objet Street View
      icon: {
        url:  icon,
        scaledSize: new google.maps.Size(25, 25)
      }
    });
  }

}
