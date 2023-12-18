




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

function listCookies() {
  var theCookies = document.cookie.split(';');
  var aString = '';
  for (var i = 1 ; i <= theCookies.length; i++) {
    aString += i + ' ' + theCookies[i-1] + "\n";
  }
  let ck = prompt("choisissez un numéro de lieu\n"+aString,"");

  return theCookies[ck-1];
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
  var nomold=getCookie(nomsave.value+"_coord");
  if(nomold===""){
    document.cookie = nomsave.value+"_coord=" + lat + ":"+long+"; Max-Age=2592000";
    alert("sauvegarde de :"+document.cookie);
  }
  else alert("nom déjà utilisé");
}
