function distance(point1,point2){
  const R = 6371000.0;
  const oldlatr = point1.lat * Math.PI / 180;
  const oldlongr = point1.long * Math.PI / 180;
  const latr = point2.lat * Math.PI / 180;
  const longr = point2.long * Math.PI / 180;
//console.log("pt = "+point1.lat);
  d = R * Math.acos(Math.cos(oldlatr) * Math.cos(latr) *
    Math.cos(longr - oldlongr) + Math.sin(oldlatr) *
    Math.sin(latr));
  //console.log("d="+d);
  return d;
}

function angle(point1,point2,point3){
  da=distance(point1,point2);
  db=distance(point2,point3);
  dc=distance(point3,point1);
  let angl = Math.acos(-(dc*dc-da*da-db*db)/ (2*da*db) )*180 /Math.PI;
  let deltaLat = point3.lat-point2.lat;
  let deltaLng= point3.lng - point2.lng;
  angl=180-angl;
  console.log('angl = '+angl);
  // if(deltaLat>0 && deltaLng>0) angl=-angl;
  return -angl;
}

function difference(link) {
  var diff = Math.abs(pano.pov.heading % 360 - link.heading);
  if (diff > 180)
    diff = Math.abs(360 - diff);
  return diff;
}
